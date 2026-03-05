<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PolicyController;

/**
 * Redirect to the admin panel.
 */
Route::get('/', function () {
    return redirect()->route('admin');
});

/* Change locale */
Route::get('change-locale/{locale}', function ($locale) {
    // Change config locale
    config(['app.locale' => $locale]);
    // Change session locale
    session(['locale' => $locale]);

    // Redirect back to previous page
    return redirect()->back();
})->name('locale.setting');

require __DIR__ . '/auth.php';
