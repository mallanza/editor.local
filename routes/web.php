<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MceDocumentController;


Route::get('/dashboard', function () {
    return view('dashboard');

})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/', [MceDocumentController::class, 'index'])->name('mce.editor');
    Route::get('mce-editor', [MceDocumentController::class, 'index'])->name('mce.editor');
    Route::post('mce-store', [MceDocumentController::class, 'store'])->name('mce.store');
    Route::get('mce-load/{document}', [MceDocumentController::class, 'load'])->name('mce.load');
    Route::post('mce-update/{document}', [MceDocumentController::class, 'update'])->name('mce.update');

    Route::post('/images/upload', function (Illuminate\Http\Request $req) {
        $req->validate(['file' => 'required|image|max:5120']);
        $path = $req->file('file')->store('public/uploads');
        $url  = Storage::url($path);
        return response()->json(['location' => $url]);
    })->name('images.upload');


    Route::get('/track', function () {
        return view('track');
    });



require __DIR__.'/auth.php';
