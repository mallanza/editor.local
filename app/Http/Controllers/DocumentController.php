<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;

class DocumentController extends Controller
{
    /**
     * Show the editor with optional document loaded.
     */
    public function index(Request $request)
    {

        $documents = Document::all();
        $selectedContent = null;

        if ($request->has('doc_id')) {
            $doc = Document::find($request->get('doc_id'));
            if ($doc && $doc->content) {
                $selectedContent = $doc->content;
            }
        }

        return view('qedit', [
            'documents' => $documents,
            'selectedContent' => $selectedContent
        ]);
    }

    /**
     * Store a new document.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        Document::create([
            'title'   => $data['title'],
            'content' => json_encode(['html' => $data['content']]),
        ]);

        return back()->with('success', 'Document saved!');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'doc_id' => 'required|exists:documents,id',
            'title'  => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $doc = Document::find($data['doc_id']);
        $doc->update([
            'title'   => $data['title'],
            'content' => $data['content'],
        ]);

        return redirect()->route('documents.load', ['doc_id' => $doc->id])
                         ->with('success', 'Document updated!');
    }

}
