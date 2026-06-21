<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentLinkController;
use App\Http\Controllers\PublicPaymentLinkController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Root / Home Redirect
Route::get('/', function () {
    return redirect()->route('login');
});

// 2. Public Checkout routes
Route::prefix('checkout')->group(function () {
    Route::get('/{token}', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/{invoice}/select', [CheckoutController::class, 'selectMethod'])->name('checkout.select');
    Route::post('/{invoice}/status', [CheckoutController::class, 'checkStatus'])->name('checkout.status');
    Route::get('/{invoice}/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::post('/{invoice}/simulate-sandbox', [CheckoutController::class, 'simulateSandboxPayment'])->name('checkout.simulate-sandbox');
});

// Public payment link routes
Route::get('/pay/{identifier}', [PublicPaymentLinkController::class, 'show'])->name('payment-links.public.show');
Route::post('/pay/{identifier}', [PublicPaymentLinkController::class, 'process'])->name('payment-links.public.process');

// 3. User Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/login/2fa', [AuthController::class, 'show2fa'])->name('auth.2fa');
Route::post('/login/2fa', [AuthController::class, 'verify2fa']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', \App\Http\Middleware\CheckMerchantSystem::class])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Store management
    Route::get('/stores', [DashboardController::class, 'storesIndex'])->name('stores.index');
    Route::get('/stores/create', [DashboardController::class, 'createStore'])->name('stores.create');
    Route::post('/stores', [DashboardController::class, 'storeStore'])->name('stores.store');
    Route::get('/stores/{store}/edit', [DashboardController::class, 'editStore'])->name('stores.edit');
    Route::put('/stores/{store}', [DashboardController::class, 'updateStore'])->name('stores.update');
    Route::post('/stores/{store}/toggle-status', [DashboardController::class, 'toggleStoreStatus'])->name('stores.toggle-status');
    Route::post('/stores/{store}/regenerate-key', [DashboardController::class, 'regenerateStoreKey'])->name('stores.regenerate-key');
    Route::delete('/stores/{store}', [DashboardController::class, 'deleteStore'])->name('stores.delete');
    
    // Payment Links
    Route::resource('payment-links', PaymentLinkController::class)->except(['show', 'edit', 'update']);
    
    // Payment config settings per store
    Route::get('/stores/{store}/configs', [DashboardController::class, 'editConfigs'])->name('stores.configs.edit');
    Route::post('/stores/{store}/configs', [DashboardController::class, 'updateConfigs'])->name('stores.configs.update');
    
    // Invoices / Transactions list
    Route::get('/invoices', [DashboardController::class, 'invoices'])->name('dashboard.invoices');
    Route::get('/invoices/create', [DashboardController::class, 'createInvoice'])->name('dashboard.invoices.create');
    Route::post('/invoices', [DashboardController::class, 'storeInvoice'])->name('dashboard.invoices.store');
    Route::post('/invoices/{invoice}/refund', [DashboardController::class, 'refund'])->name('dashboard.invoices.refund');
    Route::post('/invoices/bulk-delete', [DashboardController::class, 'bulkDeleteInvoices'])->name('dashboard.invoices.bulk-delete');
    Route::get('/invoices/bulk-delete', function() {
        return redirect()->route('dashboard.invoices');
    });
    Route::delete('/invoices/{invoice}', [DashboardController::class, 'deleteInvoice'])->name('dashboard.invoices.delete');
    Route::get('/docs', [DashboardController::class, 'docs'])->name('dashboard.docs');
    Route::get('/api-logs', [DashboardController::class, 'apiLogs'])->name('dashboard.api-logs');
    Route::get('/activity-logs', [DashboardController::class, 'activityLogs'])->name('dashboard.activity-logs');
    
    // QR Code Management
    Route::get('/qr-codes', [DashboardController::class, 'qrIndex'])->name('dashboard.qr');
    Route::post('/qr-codes', [DashboardController::class, 'qrUpload'])->name('dashboard.qr.upload');
    Route::post('/qr-codes/check', [DashboardController::class, 'qrCheck'])->name('dashboard.qr.check');
    Route::delete('/qr-codes/{qrCode}', [DashboardController::class, 'qrDelete'])->name('dashboard.qr.delete');
    Route::post('/qr-codes/blacklist', [DashboardController::class, 'qrBlocklistStore'])->name('dashboard.qr.blacklist.store');
    Route::delete('/qr-codes/blacklist/{id}', [DashboardController::class, 'qrBlocklistDelete'])->name('dashboard.qr.blacklist.delete');
    
    // Security & Notifications Settings
    Route::get('/settings/security', [DashboardController::class, 'showSecuritySettings'])->name('settings.security');
    Route::post('/settings/security/notifications', [DashboardController::class, 'updateNotificationSettings'])->name('settings.security.notifications');
    Route::post('/settings/security/2fa', [DashboardController::class, 'update2faSettings'])->name('settings.security.2fa');
    
    // Admin routes
    Route::middleware(['can:admin'])->prefix('admin')->group(function () {
        Route::get('/gateways', [DashboardController::class, 'adminGateways'])->name('admin.gateways');
        Route::post('/gateways/{id}/toggle', [DashboardController::class, 'adminToggleGateway'])->name('admin.gateways.toggle');
        Route::post('/gateways/{id}/logo', [DashboardController::class, 'adminUpdateGatewayLogo'])->name('admin.gateways.logo');
        Route::post('/settings/toggle-merchant-system', [DashboardController::class, 'adminToggleMerchantSystem'])->name('admin.settings.toggle-merchant');
        Route::post('/settings/smtp', [DashboardController::class, 'adminSaveSmtpSettings'])->name('admin.settings.smtp');
        Route::post('/settings/toggle-captcha', [DashboardController::class, 'adminToggleCaptcha'])->name('admin.settings.toggle-captcha');
    });
});

// Legacy compatibility web routes (for WHMCS V3 without api/ prefix)
Route::post('/checkout/redirect', [\App\Http\Controllers\Api\GatewayCompatibilityController::class, 'createCharge']);
Route::post('/verify-payment', [\App\Http\Controllers\Api\GatewayCompatibilityController::class, 'verifyPayment']);

// Web Installer routes
use App\Http\Controllers\InstallController;

Route::prefix('install')->group(function () {
    Route::get('/', [InstallController::class, 'welcome'])->name('install.welcome');
    Route::get('/database', [InstallController::class, 'database'])->name('install.database');
    Route::post('/database', [InstallController::class, 'saveDatabase'])->name('install.database.save');
    Route::post('/database/test', [InstallController::class, 'testDb'])->name('install.database.test');
    Route::get('/admin', [InstallController::class, 'admin'])->name('install.admin');
    Route::post('/admin', [InstallController::class, 'saveAdmin'])->name('install.admin.save');
    Route::get('/run', [InstallController::class, 'run'])->name('install.run');
    Route::post('/run', [InstallController::class, 'runInstall'])->name('install.run-action');
    Route::get('/complete', [InstallController::class, 'complete'])->name('install.complete');
});
