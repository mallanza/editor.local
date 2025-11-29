<?php

namespace App\Http\Controllers;

use App\Models\QuillLiteDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuillLiteController extends Controller
{
    /**
     * Display the simplified Quill demo that performs inline tracking.
     */
    public function show(Request $request)
    {
        $seed = trim($request->query('seed', "Sample document\n\nType here..."));
        $document = QuillLiteDocument::query()->first();
        $initialContent = $document?->text ?? $seed;
        $initialDelta = $document?->delta;
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
        $initialHtml = $document?->html;

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

        $document = QuillLiteDocument::query()->first();
        if (! $document) {
            $document = new QuillLiteDocument();
        }
        $document->fill([
            'delta' => $data['delta'] ?? ['ops' => []],
            'changes' => $data['changes'] ?? [],
            'comments' => $data['comments'] ?? [],
            'text' => $data['text'] ?? '',
            'html' => $data['html'] ?? null,
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
