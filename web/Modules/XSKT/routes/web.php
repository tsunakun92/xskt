<?php

use Illuminate\Support\Facades\Route;

use Modules\XSKT\Http\Controllers\DrawController;
use Modules\XSKT\Http\Controllers\ResultController;

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

Route::prefix('xskt')->middleware(['auth', 'web'])->group(function () {
    // DrawController
    Route::group(['prefix' => 'draws'], function () {
        $name = 'draws';
    });
    Route::resource('draws', DrawController::class);

    // ResultController
    Route::group(['prefix' => 'results'], function () {
        $name = 'results';
    });
    Route::resource('results', ResultController::class);
});
