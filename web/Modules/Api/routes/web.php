<?php

use Illuminate\Support\Facades\Route;

use Modules\Api\Http\Controllers\ApiRegRequestController;
use Modules\Api\Http\Controllers\ApiRequestLogController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::prefix('api')->middleware(['auth', 'web'])->group(function () {
    // ApiRegRequestController
    Route::group(['prefix' => 'api-reg-requests'], function () {
        $name = 'api-reg-requests';
    });
    Route::resource('api-reg-requests', ApiRegRequestController::class);

    // ApiRequestLogController
    Route::group(['prefix' => 'api-request-logs'], function () {
        $name = 'api-request-logs';
    });
    Route::resource('api-request-logs', ApiRequestLogController::class);
});
