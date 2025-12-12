<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MceDocumentController;
use App\Http\Controllers\QuillLiteController;
use App\Http\Controllers\ImageUploadController;


Route::get('/dashboard', function () {
    return view('dashboard');

})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/', [MceDocumentController::class, 'index'])->name('mce.editor');
Route::post('mce-store', [MceDocumentController::class, 'store'])->name('mce.store');
Route::get('mce-load/{document}', [MceDocumentController::class, 'load'])->name('mce.load');
Route::post('mce-update/{document}', [MceDocumentController::class, 'update'])->name('mce.update');

Route::post('/images/upload', ImageUploadController::class)
    ->middleware(['auth', 'throttle:30,1'])
    ->name('images.upload');

Route::get('/quill2', [QuillLiteController::class, 'show'])->name('quill2.show');
Route::post('/quill2/save', [QuillLiteController::class, 'save'])->name('quill2.save');
Route::delete('/quill2', [QuillLiteController::class, 'destroy'])->name('quill2.destroy');


Route::get('/track', function () {
    return view('track');
});



require __DIR__.'/auth.php';
