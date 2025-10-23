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
// resourceメソッドを使う場合は、他のtransactions.*名前のルートと衝突しないようにする
Route::resource('transactions', TransactionController::class)->except(['create']);

// Shop Management Routes
Route::prefix('management/shops')->name('shops.')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::post('/', [ShopController::class, 'store'])->name('store');
    Route::put('/{shop}', [ShopController::class, 'update'])->name('update');
    Route::delete('/{shop}', [ShopController::class, 'destroy'])->name('destroy');
});

// Category Management Routes
Route::prefix('management/categories')->name('categories.')->group(function () {
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

// Database Connection Test (remove in production)
Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();
        $shopCount = DB::table('shops')->count();
        $categoryCount = DB::table('categories')->count();
        $transactionCount = DB::table('transactions')->count();
        
        return "Database connection OK!<br>" .
               "Shops: {$shopCount}<br>" .
               "Categories: {$categoryCount}<br>" .
               "Transactions: {$transactionCount}";
    } catch (\Exception $e) {
        return 'Database connection failed: ' . $e->getMessage();
    }
});
