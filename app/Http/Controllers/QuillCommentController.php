<?php

namespace App\Http\Controllers;

use App\Models\QuillComment;
use App\Models\QuillDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class QuillCommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documentId = $request->integer('document_id');

        if (! $documentId) {
            $documentId = QuillDocument::query()->value('id');
        }

        $query = QuillComment::query()
            ->when($documentId, fn ($q) => $q->where('document_id', $documentId))
            ->latest();

        $statuses = array_filter((array) $request->query('status'));

        if (! empty($statuses)) {
            $query->whereIn('status', $statuses);
        }

        return response()->json([
            'comments' => $query->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_id' => 'required|exists:quill_documents,id',
            'anchor_index' => 'required|integer|min:0',
            'anchor_length' => 'required|integer|min:1',
            'body' => 'required|string',
        ]);

        $user = Auth::user();

        $comment = QuillComment::create([
            'document_id' => $data['document_id'],
            'user_id' => $user?->getAuthIdentifier(),
            'user_name' => $user?->name,
            'anchor_index' => $data['anchor_index'],
            'anchor_length' => $data['anchor_length'],
            'body' => $data['body'],
            'status' => QuillComment::STATUS_ACTIVE,
        ]);

        return response()->json([
            'comment' => $comment,
        ], 201);
    }

    public function update(Request $request, QuillComment $quillComment): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                QuillComment::STATUS_ACTIVE,
                QuillComment::STATUS_RESOLVED,
                QuillComment::STATUS_CLOSED,
            ])],
        ]);

        $quillComment->update($data);

        return response()->json([
            'comment' => $quillComment->fresh(),
        ]);
    }
}
