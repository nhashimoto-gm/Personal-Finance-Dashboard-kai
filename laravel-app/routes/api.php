<?php

use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// V1 API Routes
Route::prefix('v1')->group(function () {

    // Transaction Statistics
    Route::get('/transactions/statistics', [TransactionController::class, 'statistics']);

    // Transaction CRUD
    Route::apiResource('transactions', TransactionController::class);

    // Shop CRUD
    Route::apiResource('shops', ShopController::class);

    // Category CRUD
    Route::apiResource('categories', CategoryController::class);

});

// Authenticated User (for future auth implementation)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
