<?php

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/documents/{id}', fn($id) => Document::findOrFail($id));

Route::post('/documents/{id}', function(Request $request, $id) {
    $doc = Document::findOrFail($id);
    $doc->content = $request->input('content'); // serialized Yjs state
    $doc->save();
    return response()->json(['status' => 'saved']);
});
