<?php

namespace App\Http\Controllers;

use App\Models\QuillDocument;
use App\Models\QuillChange;
use App\Support\Concerns\SanitizesQuillDelta;
use App\Support\Concerns\BuildsCleanQuillDelta;
use App\Support\Quill\DocumentRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuillPoCController extends Controller
{
    use SanitizesQuillDelta, BuildsCleanQuillDelta;

    public function __construct(private readonly DocumentRenderer $renderer)
    {
    }

    public function show(): View
    {
        $document = QuillDocument::query()->with('comments')->first();

        if (! $document) {
            $document = QuillDocument::create([
                'title' => 'Quill Proof of Concept',
                'content_delta' => ['ops' => [[ 'insert' => "\n" ]]],
            ]);
            $document->load('comments');
        }

        $cleanDelta = $this->ensureCleanSnapshot($document);

        $pendingChanges = $document->changes()
            ->where('status', QuillChange::STATUS_PENDING)
            ->orderBy('anchor_index')
            ->get();

        $contentDelta = $this->renderer->buildRedlineDelta($document, $pendingChanges);
        $document->content_delta = $contentDelta;
        $document->save();
        $this->syncCommentAnchorsFromDelta($document, $contentDelta);
        $document->load('comments');

        return view('quill', [
            'document' => $document,
            'contentDelta' => $contentDelta,
            'cleanDelta' => $cleanDelta,
            'comments' => $document->comments,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_id' => 'required|exists:quill_documents,id',
            'comments' => 'sometimes|array',
            'comments.*.id' => 'required_with:comments|integer|exists:quill_comments,id',
            'comments.*.anchor_index' => 'nullable|integer|min:0',
            'comments.*.anchor_length' => 'nullable|integer|min:0',
        ]);

        $document = QuillDocument::with('comments')->findOrFail($data['document_id']);
        $cleanDelta = $this->ensureCleanSnapshot($document);

        if (! empty($data['comments'])) {
            foreach ($data['comments'] as $commentData) {
                $commentId = $commentData['id'] ?? null;
                if (! $commentId) {
                    continue;
                }

                $document->comments()
                    ->whereKey($commentId)
                    ->update([
                        'anchor_index' => max(0, (int) ($commentData['anchor_index'] ?? 0)),
                        'anchor_length' => max(0, (int) ($commentData['anchor_length'] ?? 0)),
                    ]);
            }
        }

        $pendingChanges = $document->changes()
            ->where('status', QuillChange::STATUS_PENDING)
            ->orderBy('anchor_index')
            ->get();

        $contentDelta = $this->renderer->buildRedlineDelta($document, $pendingChanges);
        $document->content_delta = $contentDelta;
        $document->save();
        $this->syncCommentAnchorsFromDelta($document, $contentDelta);
        $document->load('comments');

        return response()->json([
            'status' => 'ok',
            'document_id' => $document->id,
            'version' => $document->version,
            'content_delta' => $contentDelta,
            'clean_delta' => $cleanDelta,
            'saved_at' => now()->toIso8601String(),
        ]);
    }

    private function syncCommentAnchorsFromDelta(QuillDocument $document, array $delta): void
    {
        $ops = $delta['ops'] ?? null;

        if (! is_array($ops) || empty($ops)) {
            return;
        }

        $ranges = [];
        $cursor = 0;

        foreach ($ops as $op) {
            if (! is_array($op) || ! array_key_exists('insert', $op)) {
                continue;
            }

            $insert = $op['insert'];
            $length = is_string($insert) ? mb_strlen($insert) : 1;

            if ($length <= 0) {
                continue;
            }

            $commentId = $op['attributes']['comment'] ?? null;

            if ($commentId) {
                $ranges[$commentId] ??= ['index' => $cursor, 'length' => 0];
                $ranges[$commentId]['length'] += $length;
            }

            $cursor += $length;
        }

        if (empty($ranges)) {
            return;
        }

        foreach ($ranges as $commentId => $range) {
            $document->comments()
                ->whereKey((int) $commentId)
                ->update([
                    'anchor_index' => $range['index'],
                    'anchor_length' => $range['length'],
                ]);
        }
    }

    private function ensureCleanSnapshot(QuillDocument $document): array
    {
        $cleanDelta = $this->sanitizeDelta($document->clean_delta ?? null);

        if (! is_array($cleanDelta) || ! array_key_exists('ops', $cleanDelta)) {
            $fallback = $this->sanitizeDelta($document->content_delta ?? ['ops' => []]) ?? ['ops' => []];
            $cleanDelta = $this->buildCleanDelta($fallback);
            $document->clean_delta = $cleanDelta;
            $document->base_delta = $cleanDelta;
            $document->save();
        }

        return $cleanDelta;
    }
}
