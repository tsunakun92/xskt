<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\FileUploadController;
use Modules\Api\Http\Controllers\ApiController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

// Placeholder routes for future use
// Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
//     Route::apiResource('api', ApiController::class)->names('api');
// });

// File upload routes
Route::prefix('files/tmp')->group(function () {
    Route::post('/upload', [FileUploadController::class, 'uploadTmp'])->name('files.tmp.upload');
    Route::delete('/{filename}', [FileUploadController::class, 'removeTmp'])->name('files.tmp.remove');
    Route::delete('/existing/{id}', [FileUploadController::class, 'removeExisting'])->name('files.existing.remove');
});
