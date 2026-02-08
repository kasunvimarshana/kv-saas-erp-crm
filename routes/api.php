<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Tenant\TenantController;
use App\Http\Controllers\Api\Tenant\OrganizationController;
use App\Http\Controllers\Api\Tenant\BranchController;
use App\Http\Controllers\Api\Shared\CurrencyController;
use App\Http\Controllers\Api\Shared\LanguageController;
use App\Http\Controllers\Api\Shared\UnitOfMeasureController;
use App\Http\Controllers\Api\Accounting\AccountController;
use App\Http\Controllers\Api\Accounting\JournalEntryController;
use App\Http\Controllers\Api\Sales\CustomerController;
use App\Http\Controllers\Api\Sales\SalesOrderController;
use App\Http\Controllers\Api\Inventory\ProductController;
use App\Http\Controllers\Api\Inventory\StockMovementController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Tenant Management (no tenant context required)
Route::apiResource('tenants', TenantController::class);

// All routes below require tenant context
Route::middleware(['tenant.context'])->group(function () {
    
    // Tenant Domain Resources
    Route::apiResource('organizations', OrganizationController::class);
    Route::apiResource('branches', BranchController::class);
    
    // Shared Domain Resources
    Route::apiResource('currencies', CurrencyController::class);
    Route::apiResource('languages', LanguageController::class);
    Route::apiResource('unit-of-measures', UnitOfMeasureController::class);
    
    // Accounting Domain Resources
    Route::apiResource('accounts', AccountController::class);
    Route::apiResource('journal-entries', JournalEntryController::class);
    
    // Sales Domain Resources
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('sales-orders', SalesOrderController::class);
    
    // Inventory Domain Resources
    Route::apiResource('products', ProductController::class);
    Route::apiResource('stock-movements', StockMovementController::class);
});
