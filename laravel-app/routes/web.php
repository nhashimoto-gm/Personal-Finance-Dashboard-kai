<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Dashboard (Home Page)
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Transaction Entry Form
Route::get('/transactions/entry', [TransactionController::class, 'entry'])->name('transactions.entry');

// Transaction CRUD Routes
// レート制限付き: 1分間に10リクエストまで
Route::resource('transactions', TransactionController::class)
    ->except(['create'])
    ->middleware('throttle:10,1');

// Shop Management Routes
Route::prefix('management/shops')->name('shops.')->middleware('throttle:10,1')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::post('/', [ShopController::class, 'store'])->name('store');
    Route::put('/{shop}', [ShopController::class, 'update'])->name('update');
    Route::delete('/{shop}', [ShopController::class, 'destroy'])->name('destroy');
});

// Category Management Routes
Route::prefix('management/categories')->name('categories.')->middleware('throttle:10,1')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::post('/', [CategoryController::class, 'store'])->name('store');
    Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
    Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
});

// Language Switcher
Route::post('/language', function (Illuminate\Http\Request $request) {
    $locale = $request->input('locale', 'en');
    if (in_array($locale, ['en', 'ja'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('language.switch');
