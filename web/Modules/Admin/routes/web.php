<?php

use Illuminate\Support\Facades\Route;

use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Controllers\ChangelogController;
use Modules\Admin\Http\Controllers\MunicipalityController;
use Modules\Admin\Http\Controllers\PermissionController;
use Modules\Admin\Http\Controllers\PersonalAccessTokenController;
use Modules\Admin\Http\Controllers\PostNumberController;
use Modules\Admin\Http\Controllers\PrefectureController;
use Modules\Admin\Http\Controllers\RoleController;
use Modules\Admin\Http\Controllers\SettingController;
use Modules\Admin\Http\Controllers\UserController;

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

Route::prefix('admin')->middleware(['auth', 'web', 'module_permission:admin', 'permission'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin');

    // New routes
    // SettingController
    Route::group(['prefix' => 'settings'], function () {
        $name = 'settings';
    });
    Route::resource('settings', SettingController::class);

    // PermissionController
    Route::group(['prefix' => 'permissions'], function () {
        $name = 'permissions';
    });
    Route::resource('permissions', PermissionController::class);

    // RoleController
    Route::group(['prefix' => 'roles'], function () {
        $name = 'roles';
        Route::get('/{id}/permission', [RoleController::class, 'permission'])->name("$name.permission");
        Route::post('/{id}/permission', [RoleController::class, 'updatePermission'])->name("$name.permission");
    });
    Route::resource('roles', RoleController::class);

    // UserController
    Route::group(['prefix' => 'users'], function () {
        $name = 'users';
        Route::get('/{id}/permission', [UserController::class, 'permission'])->name("$name.permission");
        Route::post('/{id}/permission', [UserController::class, 'updatePermission'])->name("$name.permission");
        Route::get('/{id}/setting', [UserController::class, 'setting'])->name("$name.setting");
        Route::post('/{id}/setting', [UserController::class, 'updateSetting'])->name("$name.setting");
    });
    Route::resource('users', UserController::class);

    // PrefectureController
    Route::group(['prefix' => 'prefectures'], function () {
        $name = 'prefectures';
    });
    Route::resource('prefectures', PrefectureController::class);

    // MunicipalityController
    Route::group(['prefix' => 'municipalities'], function () {
        $name = 'municipalities';
    });
    Route::resource('municipalities', MunicipalityController::class);

    // PostNumberController
    Route::group(['prefix' => 'post-numbers'], function () {
        $name = 'post-numbers';
    });
    Route::resource('post-numbers', PostNumberController::class);

    // PersonalAccessTokenController
    Route::group(['prefix' => 'personal-access-tokens'], function () {
        $name = 'personal-access-tokens';
    });
    Route::resource('personal-access-tokens', PersonalAccessTokenController::class);

    // ChangelogController
    Route::group(['prefix' => 'changelog'], function () {
        $name = 'changelog';
        Route::get('/', [ChangelogController::class, 'index'])->name("$name.index");
        Route::get('ajax-get-changelog-by-version/{version}', [ChangelogController::class, 'ajaxGetChangelogByVersion'])->name('ajax-get-changelog-by-version');
    });
});
