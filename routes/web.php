<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GeneratedVideoController;
use App\Http\Controllers\PredictOutputController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuestionUploadController;
use App\Http\Controllers\QuestionUploadDetailsController;
use App\Http\Controllers\UploadQueueController;
use App\Http\Controllers\VideoSettingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/questions');

Route::post('questions/predict', PredictOutputController::class)->name('questions.predict');
Route::resource('questions', QuestionController::class)->except('show');
Route::patch('questions/{question}/uploaded', QuestionUploadController::class)->name('questions.uploaded');
Route::post('questions/{question}/upload-details', QuestionUploadDetailsController::class)->name('questions.upload-details');
Route::post('questions/{question}/videos', [GeneratedVideoController::class, 'store'])->name('questions.videos.store');

Route::get('upload-queue', UploadQueueController::class)->name('queue.index');

Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);

Route::controller(VideoSettingController::class)->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', 'edit')->name('edit');
    Route::put('/', 'update')->name('update');
    Route::post('voice-preview', 'preview')->name('preview');
});

Route::controller(GeneratedVideoController::class)->prefix('videos')->name('videos.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{video}', 'show')->name('show');
    Route::get('{video}/status', 'status')->name('status');
    Route::get('{video}/file', 'file')->name('file');
    Route::get('{video}/download', 'download')->name('download');
    Route::delete('{video}', 'destroy')->name('destroy');
});
