<?php

namespace App\Http\Controllers;

use App\Models\QuillChange;
use App\Models\QuillDocument;
use App\Support\Concerns\BuildsCleanQuillDelta;
use App\Support\Concerns\SanitizesQuillDelta;
use App\Support\Quill\DocumentRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QuillChangeController extends Controller
{
    use SanitizesQuillDelta;
    use BuildsCleanQuillDelta;

    public function __construct(private readonly DocumentRenderer $renderer)
    {
    }

    private const TRACK_ATTRIBUTES = [
        'tc-change-id',
        'tc-change-type',
        'tc-author-id',
        'tc-author-name',
        'tc-timestamp',
    ];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_id' => 'required|exists:quill_documents,id',
            'change_uuid' => 'nullable|uuid',
            'change_type' => ['required', Rule::in([
                QuillChange::TYPE_INSERT,
                QuillChange::TYPE_DELETE,
            ])],
            'delta' => 'required',
            'anchor_index' => 'nullable|integer|min:0',
            'anchor_length' => 'nullable|integer|min:0',
        ]);

        $delta = $this->sanitizeDelta($this->normalizeDelta($data['delta']));

        if (! is_array($delta)) {
            return response()->json([
                'message' => 'Invalid delta payload.',
            ], 422);
        }

        $deltaLength = $this->measureDeltaLength($delta);

        if ($deltaLength <= 0) {
            Log::warning('Rejected zero-length change delta', [
                'document_id' => $data['document_id'],
                'change_uuid' => $data['change_uuid'] ?? null,
                'change_type' => $data['change_type'],
            ]);

            return response()->json([
                'message' => 'Change delta must include content.',
            ], 422);
        }

        $user = Auth::user();
        $changeUuid = $data['change_uuid'] ?? (string) Str::uuid();

        $change = QuillChange::updateOrCreate(
            ['change_uuid' => $changeUuid],
            [
                'document_id' => $data['document_id'],
                'user_id' => $user?->getAuthIdentifier(),
                'user_name' => $user?->name,
                'change_type' => $data['change_type'],
                'status' => QuillChange::STATUS_PENDING,
                'anchor_index' => $this->normalizeAnchorIndex($data['anchor_index'] ?? 0),
                'anchor_length' => $this->normalizeAnchorLength($data['anchor_length'] ?? null, $delta),
                'delta' => $delta,
            ]
        );

        return response()->json([
            'change' => $change,
        ], 201);
    }

    public function accept(Request $request, string $change_uuid): JsonResponse
    {
        return $this->handleDecision($request, $change_uuid, 'accept');
    }

    public function reject(Request $request, string $change_uuid): JsonResponse
    {
        return $this->handleDecision($request, $change_uuid, 'reject');
    }

    private function handleDecision(Request $request, string $changeUuid, string $decision): JsonResponse
    {
        $change = QuillChange::with('document')->where('change_uuid', $changeUuid)->firstOrFail();
        $document = $change->document;

        if (! $document) {
            return response()->json([
                'message' => 'Document not initialized.',
            ], 422);
        }

        $requestedDocumentId = $request->input('document_id');

        if ($requestedDocumentId && (int) $requestedDocumentId !== (int) $document->id) {
            return response()->json([
                'message' => 'Mismatched document context.',
            ], 422);
        }

        $this->prepareDocumentSnapshot($document);

        $mutated = $this->applyChangeDecision($document, $change, $decision);

        if (! $mutated) {
            return response()->json([
                'message' => 'Tracked change not found in document.',
            ], 409);
        }

        $change->status = $decision === 'accept'
            ? QuillChange::STATUS_ACCEPTED
            : QuillChange::STATUS_REJECTED;
        $change->save();

        $this->refreshDocumentSnapshot($document);

        return response()->json([
            'change' => $change,
            'document' => [
                'id' => $document->id,
                'version' => $document->version,
                'content_delta' => $document->content_delta,
                'base_delta' => $document->base_delta,
                'clean_delta' => $document->clean_delta,
                'updated_at' => optional($document->updated_at)->toIso8601String(),
            ],
        ]);
    }

    private function applyChangeDecision(QuillDocument $document, QuillChange $change, string $decision): bool
    {
        $content = $document->content_delta ?? ['ops' => []];
        $ops = $content['ops'] ?? [];

        if (! is_array($ops) || empty($ops)) {
            return false;
        }

        $mutated = false;

        foreach ($ops as $index => $op) {
            if (! isset($op['insert'])) {
                continue;
            }

            $attributes = $op['attributes'] ?? [];

            if (($attributes['tc-change-id'] ?? null) !== $change->change_uuid) {
                continue;
            }

            $mutated = true;
            $changeType = $change->change_type;
            $isAccept = $decision === 'accept';

            if ($changeType === QuillChange::TYPE_INSERT) {
                if ($isAccept) {
                    $ops[$index] = $this->stripTrackAttributes($op);
                } else {
                    unset($ops[$index]);
                }
            } else {
                if ($isAccept) {
                    unset($ops[$index]);
                } else {
                    $ops[$index] = $this->stripTrackAttributes($op);
                }
            }
        }

        if (! $mutated) {
            return false;
        }

        $content['ops'] = array_values($ops);
        $document->content_delta = $content;
        $cleanDelta = $this->buildCleanDelta($content);
        $document->clean_delta = $cleanDelta;
        $document->base_delta = $cleanDelta;
        $document->version = $document->version + 1;
        $document->save();

        return true;
    }

    private function stripTrackAttributes(array $op): array
    {
        if (! isset($op['attributes'])) {
            return $op;
        }

        foreach (self::TRACK_ATTRIBUTES as $attribute) {
            unset($op['attributes'][$attribute]);
        }

        if (empty($op['attributes'])) {
            unset($op['attributes']);
        }

        return $op;
    }

    private function normalizeDelta($payload): ?array
    {
        if (is_null($payload)) {
            return null;
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $payload = $decoded;
            }
        }

        return is_array($payload) ? $payload : null;
    }

    private function refreshDocumentSnapshot(QuillDocument $document): void
    {
        $pendingChanges = $document->changes()
            ->where('status', QuillChange::STATUS_PENDING)
            ->orderBy('anchor_index')
            ->get();

        $document->content_delta = $this->renderer->buildRedlineDelta($document, $pendingChanges);
        $document->save();
    }

    private function prepareDocumentSnapshot(QuillDocument $document): void
    {
        $cleanDelta = $this->sanitizeDelta($document->clean_delta ?? null);

        if (! is_array($cleanDelta) || ! array_key_exists('ops', $cleanDelta)) {
            $fallback = $this->sanitizeDelta($document->content_delta ?? ['ops' => []]) ?? ['ops' => []];
            $cleanDelta = $this->buildCleanDelta($fallback);
            $document->clean_delta = $cleanDelta;
            $document->base_delta = $cleanDelta;
            $document->save();
        }

        $pendingChanges = $document->changes()
            ->where('status', QuillChange::STATUS_PENDING)
            ->orderBy('anchor_index')
            ->get();

        $document->content_delta = $this->renderer->buildRedlineDelta($document, $pendingChanges);
    }

    private function normalizeAnchorIndex($value): int
    {
        return max(0, (int) $value);
    }

    private function normalizeAnchorLength($value, array $delta): int
    {
        if (is_numeric($value)) {
            return max(0, (int) $value);
        }

        return $this->measureDeltaLength($delta);
    }

    private function measureDeltaLength(array $delta): int
    {
        $ops = $delta['ops'] ?? [];
        $length = 0;

        foreach ($ops as $op) {
            if (! array_key_exists('insert', $op)) {
                continue;
            }

            $insert = $op['insert'];
            $length += is_string($insert) ? mb_strlen($insert) : 1;
        }

        return $length;
    }
}
