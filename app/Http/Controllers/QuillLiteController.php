<?php

namespace App\Http\Controllers;

use App\Models\QuillLiteDocument;
use App\Support\QuillHtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuillLiteController extends Controller
{
    private function normalizeDelta($payload): ?array
    {
        if (! is_array($payload)) {
            return $payload;
        }

        $ops = $payload['ops'] ?? null;
        if (! is_array($ops)) {
            return ['ops' => []];
        }

        $cleanOps = [];
        foreach ($ops as $op) {
            if (! is_array($op) || ! array_key_exists('insert', $op)) {
                continue;
            }

            // quill-table-better can emit ops with insert: null. Quill expects string/embeds;
            // treat null as a newline so table attributes remain attached to a valid insert.
            if (($op['insert'] ?? null) === null) {
                $op['insert'] = "\n";
            }

            $attributes = $op['attributes'] ?? null;
            if (is_array($attributes) && count($attributes) === 0) {
                unset($op['attributes']);
            } elseif ($attributes !== null) {
                $op['attributes'] = $attributes;
            }

            $cleanOps[] = $op;
        }

        return ['ops' => $cleanOps];
    }

    /**
     * Display the simplified Quill demo that performs inline tracking.
     */
    public function show(Request $request)
    {
        $seed = trim($request->query('seed', "Sample document\n\nType here..."));
        $document = QuillLiteDocument::query()->first();
        $initialContent = $document?->text ?? $seed;
        // Normalize stored deltas (e.g. table modules may emit ops with insert: null).
        // Quill expects inserts to be strings/embeds; null inserts will drop structure on reload.
        $initialDelta = $this->normalizeDelta($document?->delta);
        if (! is_array($initialDelta) || empty($initialDelta['ops'])) {
            $seedText = $initialContent === '' ? $seed : $initialContent;
            $seedText .= str_ends_with($seedText, "\n") ? '' : "\n";
            $initialDelta = [
                'ops' => [
                    ['insert' => $seedText],
                ],
            ];
        }
        $initialChanges = $document?->changes ?? [];
        $initialComments = $document?->comments ?? [];
        $initialHtml = QuillHtmlSanitizer::sanitize($document?->html);

        $viewer = $request->user();
        $quillUser = [
            'id' => (string) ($viewer?->getAuthIdentifier() ?? 'guest-user'),
            'name' => $viewer?->name ?? $viewer?->email ?? 'Guest User',
            'email' => $viewer?->email,
        ];

        return view('quill2', [
            'initialContent' => $initialContent,
            'initialDelta' => $initialDelta,
            'initialChanges' => $initialChanges,
            'initialComments' => $initialComments,
            'initialHtml' => $initialHtml,
            'quillUser' => $quillUser,
        ]);
    }

    /**
     * Persist the single Quill Lite document payload.
     */
    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'delta' => 'nullable|array',
            'changes' => 'nullable|array',
            'comments' => 'nullable|array',
            'text' => 'nullable|string',
            'html' => 'nullable|string',
        ]);

        $sanitizedDelta = $this->normalizeDelta($data['delta'] ?? null) ?? ['ops' => []];
        $sanitizedHtml = QuillHtmlSanitizer::sanitize($data['html'] ?? null);

        $document = QuillLiteDocument::query()->first();
        if (! $document) {
            $document = new QuillLiteDocument();
        }
        $document->fill([
            'delta' => $sanitizedDelta,
            'changes' => $data['changes'] ?? [],
            'comments' => $data['comments'] ?? [],
            'text' => $data['text'] ?? '',
            'html' => $sanitizedHtml,
        ]);
        $document->save();

        return response()->json([
            'ok' => true,
            'updated_at' => $document->updated_at,
        ]);
    }

    /**
     * Destroy the persisted document so the UI resets to an empty state.
     */
    public function destroy(): JsonResponse
    {
        QuillLiteDocument::query()->delete();

        return response()->json(['ok' => true]);
    }
}
