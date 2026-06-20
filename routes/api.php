<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\SmsSyncController;
use App\Http\Controllers\Api\AppTransactionController;
use App\Http\Controllers\Api\GatewayCompatibilityController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Legacy compatibility routes mapped directly at /api/ prefix
Route::post('/create-charge', [GatewayCompatibilityController::class, 'createCharge']);
Route::post('/verify-payments', [GatewayCompatibilityController::class, 'verifyPayment']);

Route::prefix('v1')->group(function () {
    // Auth login endpoint for mobile apps
    Route::post('/auth/login', [SmsSyncController::class, 'apiLogin'])->name('api.auth.login');

    // 1. Invoice creation API (used by merchant website backend)
    Route::post('/payment', [InvoiceController::class, 'store'])->name('api.payment.create');

    // 2. Mobile Transaction Sync API (used by mobile SMS reader app)
    Route::post('/sync-sms', [SmsSyncController::class, 'sync'])->name('api.sync.sms');

    // API key verification (used by client apps and shortcuts to test configuration)
    Route::get('/verify-key', [SmsSyncController::class, 'verifyKey'])->name('api.verify.key');

    // 3. Transactions reading API (used by merchant app/frontend)
    Route::get('/transactions', [AppTransactionController::class, 'index'])->name('api.transactions.list');
    Route::get('/transactions/{id}', [AppTransactionController::class, 'show'])->name('api.transactions.detail');
    Route::post('/transactions/{id}/mark-paid', [AppTransactionController::class, 'markPaid'])->name('api.transactions.mark-paid');
    Route::post('/transactions/{id}/mark-cancelled', [AppTransactionController::class, 'markCancelled'])->name('api.transactions.mark-cancelled');
    Route::delete('/transactions/{id}', [AppTransactionController::class, 'destroy'])->name('api.transactions.delete');

    // Invoice aliases for frontend client compatibility
    Route::get('/invoices', [AppTransactionController::class, 'index'])->name('api.invoices.list');
    Route::get('/invoices/{id}', [AppTransactionController::class, 'show'])->name('api.invoices.detail');
    Route::post('/invoices/{id}/mark-paid', [AppTransactionController::class, 'markPaid'])->name('api.invoices.mark-paid');
    Route::post('/invoices/{id}/mark-cancelled', [AppTransactionController::class, 'markCancelled'])->name('api.invoices.mark-cancelled');
    Route::delete('/invoices/{id}', [AppTransactionController::class, 'destroy'])->name('api.invoices.delete');

    // Synced MFS SMS transactions pool
    Route::get('/synced-transactions', [AppTransactionController::class, 'syncedTransactions'])->name('api.synced-transactions.list');
});

