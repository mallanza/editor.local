<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class MceDocumentController extends Controller
{
    public function index()
    {
        return view('mce-editor');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $document = Document::create([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return redirect()->back()->with('status', 'Document saved with ID: ' . $document->id);
    }

    public function load(Document $document)
    {
        return response()->json([
            'id' => $document->id,
            'title' => $document->title,
            'content' => $document->content,
        ]);
    }

    public function update(Request $request, Document $document)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $document->update([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return response()->json(['status' => 'updated']);
    }
}
