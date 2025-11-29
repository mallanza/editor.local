<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MceDocumentController;
use App\Http\Controllers\QuillChangeController;
use App\Http\Controllers\QuillCommentController;
use App\Http\Controllers\QuillPoCController;
use App\Http\Controllers\QuillLiteController;


Route::get('/dashboard', function () {
    return view('dashboard');

})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/quill', [QuillPoCController::class, 'show'])->name('quill.show');
    Route::post('/quill/save', [QuillPoCController::class, 'save'])->name('quill.save');

    Route::get('/quill/comments', [QuillCommentController::class, 'index'])->name('quill.comments.index');
    Route::post('/quill/comments', [QuillCommentController::class, 'store'])->name('quill.comments.store');
    Route::patch('/quill/comments/{quillComment}', [QuillCommentController::class, 'update'])->name('quill.comments.update');

    Route::post('/quill/changes', [QuillChangeController::class, 'store'])->name('quill.changes.store');
    Route::post('/quill/changes/{change_uuid}/accept', [QuillChangeController::class, 'accept'])->name('quill.changes.accept');
    Route::post('/quill/changes/{change_uuid}/reject', [QuillChangeController::class, 'reject'])->name('quill.changes.reject');
});


Route::get('/', [MceDocumentController::class, 'index'])->name('mce.editor');
Route::post('mce-store', [MceDocumentController::class, 'store'])->name('mce.store');
Route::get('mce-load/{document}', [MceDocumentController::class, 'load'])->name('mce.load');
Route::post('mce-update/{document}', [MceDocumentController::class, 'update'])->name('mce.update');

Route::post('/images/upload', function (Illuminate\Http\Request $req) {
    $req->validate(['file' => 'required|image|max:5120']);
    $path = $req->file('file')->store('public/uploads');
    $url  = Storage::url($path);
    return response()->json(['location' => $url]);
})->name('images.upload');

Route::get('/quill2', [QuillLiteController::class, 'show'])->name('quill2.show');
Route::post('/quill2/save', [QuillLiteController::class, 'save'])->name('quill2.save');
Route::delete('/quill2', [QuillLiteController::class, 'destroy'])->name('quill2.destroy');


Route::get('/track', function () {
    return view('track');
});



require __DIR__.'/auth.php';
