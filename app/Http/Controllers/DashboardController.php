<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\StorePaymentConfig;
use App\Models\ActivityLog;
use App\Models\QrChecksum;
use App\Models\QrRegistry;
use App\Models\QrBlacklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    /**
     * Show core merchant dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        if (empty($user->api_key)) {
            $user->api_key = 'usr_' . Str::random(30);
            $user->save();
        }
        if (empty($user->sms_sync_key)) {
            $user->sms_sync_key = 'sync_' . Str::random(30);
            $user->save();
        }
        $startDate = now()->subDays(29)->startOfDay();

        if ($user->role === 'admin') {
            $storesCount = Store::count();
            $invoicesCount = Invoice::count();
            $paidInvoices = Invoice::where('status', 'paid')->get();
            $volume = $paidInvoices->sum('amount');
            $recentInvoices = Invoice::with('store')->latest()->take(10)->get();
            $stores = Store::with('user')->latest()->get();

            // Stats calculations
            $totalInvoicesCount = Invoice::count();
            $paidInvoicesCount = Invoice::where('status', 'paid')->count();
            $pendingInvoicesCount = Invoice::where('status', 'pending')->count();
            $refundedVolume = Invoice::where('status', 'refunded')->sum('amount');

            // Chart data
            $revenueQuery = Invoice::where('status', 'paid')
                ->where('paid_at', '>=', $startDate)
                ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            $transactionsQuery = Invoice::where('status', 'paid')
                ->where('paid_at', '>=', $startDate)
                ->selectRaw('DATE(paid_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->get()
                ->keyBy('date');
        } else {
            $storeIds = Store::where('user_id', $user->id)->pluck('id');
            $storesCount = $storeIds->count();
            $invoicesCount = Invoice::whereIn('store_id', $storeIds)->count();
            $paidInvoices = Invoice::whereIn('store_id', $storeIds)->where('status', 'paid')->get();
            $volume = $paidInvoices->sum('amount');
            $recentInvoices = Invoice::whereIn('store_id', $storeIds)->latest()->take(10)->get();
            $stores = Store::where('user_id', $user->id)->latest()->get();

            // Stats calculations
            $totalInvoicesCount = Invoice::whereIn('store_id', $storeIds)->count();
            $paidInvoicesCount = Invoice::whereIn('store_id', $storeIds)->where('status', 'paid')->count();
            $pendingInvoicesCount = Invoice::whereIn('store_id', $storeIds)->where('status', 'pending')->count();
            $refundedVolume = Invoice::whereIn('store_id', $storeIds)->where('status', 'refunded')->sum('amount');

            // Chart data
            $revenueQuery = Invoice::whereIn('store_id', $storeIds)
                ->where('status', 'paid')
                ->where('paid_at', '>=', $startDate)
                ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            $transactionsQuery = Invoice::whereIn('store_id', $storeIds)
                ->where('status', 'paid')
                ->where('paid_at', '>=', $startDate)
                ->selectRaw('DATE(paid_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->get()
                ->keyBy('date');
        }

        // Fill chart timeline
        $chartLabels = [];
        $chartRevenue = [];
        $chartCount = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateKeyYmd = $date->format('Y-m-d');
            $chartLabels[] = $date->format('M d');

            // Find matching data or default to 0
            $rev = 0;
            if ($revenueQuery->has($dateKeyYmd)) {
                $rev = (float) $revenueQuery->get($dateKeyYmd)->total;
            }
            $chartRevenue[] = round($rev, 2);

            $count = 0;
            if ($transactionsQuery->has($dateKeyYmd)) {
                $count = (int) $transactionsQuery->get($dateKeyYmd)->count;
            }
            $chartCount[] = $count;
        }

        $successRate = $totalInvoicesCount > 0 ? round(($paidInvoicesCount / $totalInvoicesCount) * 100, 1) : 0;
        $avgTicketSize = $paidInvoicesCount > 0 ? round($volume / $paidInvoicesCount, 2) : 0;

        return view('dashboard.index', [
            'storesCount' => $storesCount,
            'invoicesCount' => $invoicesCount,
            'volume' => $volume,
            'recentInvoices' => $recentInvoices,
            'stores' => $stores,
            'pendingInvoicesCount' => $pendingInvoicesCount,
            'refundedVolume' => $refundedVolume,
            'successRate' => $successRate,
            'avgTicketSize' => $avgTicketSize,
            'chartLabels' => $chartLabels,
            'chartRevenue' => $chartRevenue,
            'chartCount' => $chartCount,
        ]);
    }

    /**
     * Show create store form.
     */
    public function createStore()
    {
        return view('dashboard.stores.create');
    }

    /**
     * Store a new store.
     */
    public function storeStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
        ]);

        $store = Store::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'domain' => $request->domain,
            'api_key' => 'st_' . Str::random(32),
            'is_active' => true,
        ]);

        ActivityLog::log('store_create', "Created store '{$store->name}'", $store->id);

        return redirect()->route('dashboard')->with('success', 'Store created successfully.');
    }



    /**
     * Edit configs for a store.
     */
    public function editConfigs(Store $store)
    {
        // Authorize
        if (Auth::user()->role !== 'admin' && $store->user_id !== Auth::id()) {
            abort(403);
        }

        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        
        $configs = StorePaymentConfig::where('store_id', $store->id)
            ->get()
            ->keyBy('payment_method_id');

        return view('dashboard.stores.configs', [
            'store' => $store,
            'paymentMethods' => $paymentMethods,
            'configs' => $configs
        ]);
    }

    /**
     * Update configs for a store.
     */
    public function updateConfigs(Request $request, Store $store)
    {
        if (Auth::user()->role !== 'admin' && $store->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'settings.*.logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'settings.*.qr_code' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $activeMethods = $request->input('active_methods', []);
        $settings = $request->input('settings', []);

        $paymentMethods = PaymentMethod::all();

        // Pre-validate all uploaded QR codes before doing any database updates or file moves
        foreach ($paymentMethods as $method) {
            $methodSettings = $settings[$method->id] ?? [];
            $wantsRemoveQr = isset($methodSettings['remove_qr_code']) && $methodSettings['remove_qr_code'] == '1';

            if ($wantsRemoveQr) {
                continue;
            }

            if ($request->hasFile("settings.{$method->id}.qr_code")) {
                $qrData = $methodSettings['qr_code_data'] ?? null;
                if (empty($qrData)) {
                    return redirect()->back()->with('error', 'Anti-Hijack Block: Failed to extract payload from QR Code image. Update rejected.');
                }
                
                $qrData = trim($qrData);
                $qrDataHash = hash('sha256', $qrData);

                // 1. Check if the QR code data is blacklisted (Security Risk)
                if (QrBlacklist::where('qr_data_hash', $qrDataHash)->exists()) {
                    return redirect()->back()->with('error', 'Anti-Hijack Block: The uploaded QR code payload matches a blacklisted entry. Update rejected.');
                }

                // 2. Check if the QR code data belongs to another user (Ownership Theft)
                $existingRegistry = QrRegistry::where('qr_data_hash', $qrDataHash)->first();
                if ($existingRegistry && $existingRegistry->owner_user_id !== Auth::id()) {
                    return redirect()->back()->with('error', 'Anti-Hijack Block: The uploaded QR code payload belongs to another store owner. Update rejected.');
                }
            }
        }

        foreach ($paymentMethods as $method) {
            $isActive = in_array($method->id, $activeMethods);
            $methodSettings = $settings[$method->id] ?? [];

            // Retrieve existing config to preserve old files/values if not re-uploaded
            $existingConfig = StorePaymentConfig::where('store_id', $store->id)
                ->where('payment_method_id', $method->id)
                ->first();
            $existingSettings = $existingConfig ? ($existingConfig->settings ?? []) : [];

            // Handle QR code removal
            $wantsRemoveQr = isset($methodSettings['remove_qr_code']) && $methodSettings['remove_qr_code'] == '1';

            // Check if a file is uploaded for this method
            if ($wantsRemoveQr) {
                if (isset($existingSettings['qr_code'])) {
                    $oldPath = public_path($existingSettings['qr_code']);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                unset($methodSettings['qr_code']);
                unset($methodSettings['remove_qr_code']);
            } elseif ($request->hasFile("settings.{$method->id}.qr_code")) {
                $file = $request->file("settings.{$method->id}.qr_code");
                
                // Retrieve and validate decoded payload (anti-hijacking registry integration)
                $qrData = $methodSettings['qr_code_data'] ?? null;
                if (empty($qrData)) {
                    return redirect()->back()->with('error', 'Anti-Hijack Block: Failed to extract payload from QR Code image. Update rejected.');
                }
                
                $qrData = trim($qrData);
                $qrDataHash = hash('sha256', $qrData);

                // 1. Check if the QR code data is blacklisted (Security Risk)
                if (QrBlacklist::where('qr_data_hash', $qrDataHash)->exists()) {
                    return redirect()->back()->with('error', 'Anti-Hijack Block: The uploaded QR code payload matches a blacklisted entry. Update rejected.');
                }

                // 2. Check if the QR code data belongs to another user (Ownership Theft)
                $existingRegistry = QrRegistry::where('qr_data_hash', $qrDataHash)->first();
                if ($existingRegistry && $existingRegistry->owner_user_id !== Auth::id()) {
                    return redirect()->back()->with('error', 'Anti-Hijack Block: The uploaded QR code payload belongs to another store owner. Update rejected.');
                }

                $fileName = time() . '_' . $method->code . '_qr.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/configs'), $fileName);
                $methodSettings['qr_code'] = 'uploads/configs/' . $fileName;

                // 3. Register it if not registered yet
                if (!$existingRegistry) {
                    QrRegistry::create([
                        'qr_data_hash' => $qrDataHash,
                        'owner_user_id' => Auth::id(),
                        'status' => 'active'
                    ]);
                }

                // 4. Save checksum log
                $fileChecksum = md5_file(public_path($methodSettings['qr_code']));
                QrChecksum::create([
                    'user_id' => Auth::id(),
                    'file_path' => $methodSettings['qr_code'],
                    'checksum' => $fileChecksum,
                    'qr_data' => $qrData,
                    'qr_data_hash' => $qrDataHash,
                ]);

                ActivityLog::log('qr_upload', "Uploaded and verified QR Code image in store config with checksum '{$fileChecksum}'");
            } elseif (isset($existingSettings['qr_code'])) {
                $methodSettings['qr_code'] = $existingSettings['qr_code'];
            }

            // Clean up temporary decoding input so it doesn't pollute database JSON columns
            unset($methodSettings['qr_code_data']);
            unset($methodSettings['remove_qr_code']);

            // Handle custom logo removal
            if (isset($methodSettings['remove_logo']) && $methodSettings['remove_logo'] == '1') {
                if (isset($existingSettings['logo'])) {
                    $oldPath = public_path($existingSettings['logo']);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                unset($methodSettings['logo']);
                unset($methodSettings['remove_logo']);
            } else {
                // Check if a custom logo is uploaded for this method
                if ($request->hasFile("settings.{$method->id}.logo")) {
                    $file = $request->file("settings.{$method->id}.logo");
                    $fileName = time() . '_' . $method->code . '_store_logo.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/logos'), $fileName);
                    $methodSettings['logo'] = 'uploads/logos/' . $fileName;

                    // Delete old custom logo file if exists
                    if (isset($existingSettings['logo'])) {
                        $oldPath = public_path($existingSettings['logo']);
                        if (file_exists($oldPath) && $oldPath !== public_path($methodSettings['logo'])) {
                            @unlink($oldPath);
                        }
                    }
                } elseif (isset($existingSettings['logo'])) {
                    $methodSettings['logo'] = $existingSettings['logo'];
                }
            }

            StorePaymentConfig::updateOrCreate(
                [
                    'store_id' => $store->id,
                    'payment_method_id' => $method->id,
                ],
                [
                    'settings' => $methodSettings,
                    'is_active' => $isActive,
                ]
            );
        }

        ActivityLog::log('store_config_update', "Updated payment configurations for store '{$store->name}'", $store->id);

        return redirect()->route('stores.configs.edit', ['store' => $store->id])->with('success', 'Payment configurations updated successfully.');
    }

    /**
     * List invoices/transactions.
     */
    public function invoices(Request $request)
    {
        $user = Auth::user();
        $query = Invoice::with(['store', 'paymentMethod']);

        if ($user->role !== 'admin') {
            $storeIds = Store::where('user_id', $user->id)->pluck('id');
            $query->whereIn('store_id', $storeIds);
        }

        // Calculate summary counts
        $statsQuery = Invoice::query();
        if ($user->role !== 'admin') {
            $storeIds = Store::where('user_id', $user->id)->pluck('id');
            $statsQuery->whereIn('store_id', $storeIds);
        }
        $statusCounts = $statsQuery->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $paidCount = isset($statusCounts['paid']) ? $statusCounts['paid'] : 0;
        $pendingCount = isset($statusCounts['pending']) ? $statusCounts['pending'] : 0;
        $refundedCount = isset($statusCounts['refunded']) ? $statusCounts['refunded'] : 0;
        $expiredCount = isset($statusCounts['expired']) ? $statusCounts['expired'] : 0;

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $perPage = intval($request->query('per_page', 50));
        if (!in_array($perPage, [50, 100, 200, 500])) {
            $perPage = 50;
        }

        $invoices = $query->latest()->paginate($perPage);

        return view('dashboard.invoices', [
            'invoices' => $invoices,
            'paidCount' => $paidCount,
            'pendingCount' => $pendingCount,
            'refundedCount' => $refundedCount,
            'expiredCount' => $expiredCount,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Admin: Global Gateways configuration list.
     */
    public function adminGateways()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $gateways = PaymentMethod::all();
        return view('dashboard.admin.gateways', ['gateways' => $gateways]);
    }

    /**
     * Admin: Toggle gateway status.
     */
    public function adminToggleGateway($id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $gateway = PaymentMethod::findOrFail($id);
        $gateway->update([
            'is_active' => !$gateway->is_active
        ]);

        return redirect()->back()->with('success', "Gateway [{$gateway->name}] status toggled successfully.");
    }

    /**
     * Admin: Update gateway logo.
     */
    public function adminUpdateGatewayLogo(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048'
        ]);

        $gateway = PaymentMethod::findOrFail($id);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $fileName = time() . '_' . $gateway->code . '_logo.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/logos'), $fileName);

            // Delete old logo file if exists
            if ($gateway->logo) {
                $oldPath = public_path($gateway->logo);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $gateway->update([
                'logo' => 'uploads/logos/' . $fileName
            ]);
        }

        return redirect()->back()->with('success', "Logo for gateway [{$gateway->name}] updated successfully.");
    }

    /**
     * Refund a paid invoice.
     */
    public function refund(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $invoice->store->user_id !== $user->id) {
            abort(403);
        }

        if ($invoice->status !== 'paid') {
            return redirect()->back()->with('error', 'Only paid invoices can be refunded.');
        }

        if (!$invoice->paymentMethod) {
            return redirect()->back()->with('error', 'Payment method not found for this invoice.');
        }

        $config = StorePaymentConfig::where('store_id', $invoice->store_id)
            ->where('payment_method_id', $invoice->payment_method_id)
            ->first();

        if (!$config) {
            return redirect()->back()->with('error', 'Payment config not found.');
        }

        try {
            $manager = app(\App\Services\PaymentGatewayManager::class);
            $driver = $manager->getDriver($invoice->paymentMethod->code);

            $result = $driver->refund($invoice, $config->settings ?? [], [
                'amount' => $invoice->amount,
                'reason' => $request->input('reason', 'Merchant dashboard refund request')
            ]);

            if ($result['status'] === 'success') {
                ActivityLog::log('invoice_refund', "Successfully refunded invoice '{$invoice->invoice_id}'", $invoice->store_id);
                return redirect()->back()->with('success', $result['message']);
            } elseif ($result['status'] === 'manual') {
                $invoice->update(['status' => 'refunded']);
                ActivityLog::log('invoice_refund', "Marked invoice '{$invoice->invoice_id}' as refunded (manual processing)", $invoice->store_id);
                return redirect()->back()->with('warning', $result['message']);
            } else {
                return redirect()->back()->with('error', $result['message'] ?? 'Refund failed.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete an invoice.
     */
    public function deleteInvoice(Invoice $invoice)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $invoice->store->user_id !== $user->id) {
            abort(403);
        }

        ActivityLog::log('invoice_delete', "Deleted invoice '{$invoice->invoice_id}'", $invoice->store_id);
        $invoice->delete();

        return redirect()->route('dashboard.invoices')->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Bulk delete selected invoices.
     */
    public function bulkDeleteInvoices(Request $request)
    {
        $user = Auth::user();
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('dashboard.invoices')->with('error', 'No invoices selected.');
        }

        $invoices = Invoice::whereIn('id', $ids)->get();
        $deletedCount = 0;

        foreach ($invoices as $invoice) {
            // Check authorization
            if ($user->role !== 'admin' && (!isset($invoice->store) || $invoice->store->user_id !== $user->id)) {
                continue;
            }

            ActivityLog::log('invoice_delete', "Deleted invoice '{$invoice->invoice_id}' via bulk delete", $invoice->store_id);
            $invoice->delete();
            $deletedCount++;
        }

        return redirect()->route('dashboard.invoices')->with('success', "Successfully deleted {$deletedCount} invoices.");
    }

    /**
     * Show the API documentation page.
     */
    public function docs()
    {
        return view('dashboard.docs');
    }

    /**
     * Show the manual invoice creation form.
     */
    public function createInvoice()
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            $stores = Store::where('is_active', true)->get();
        } else {
            $stores = Store::where('user_id', $user->id)->where('is_active', true)->get();
        }

        return view('dashboard.invoices.create', ['stores' => $stores]);
    }

    /**
     * Store a manually created invoice.
     */
    public function storeInvoice(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|gt:0',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'currency' => 'required|string|max:10',
            'callback_url' => 'nullable|url',
            'is_sandbox' => 'nullable|boolean',
        ]);

        $store = Store::findOrFail($request->store_id);

        // Authorize that the store belongs to the logged-in merchant (or user is admin)
        if ($user->role !== 'admin' && $store->user_id !== $user->id) {
            abort(403);
        }

        $amount = floatval($request->amount);
        
        // Generate a random fractional offset to make the expected amount unique
        $randomOffset = mt_rand(100, 999) / 1000000;
        $expectedAmount = round($amount + $randomOffset, 6);

        // Ensure expected_amount is truly unique in the database
        while (Invoice::where('expected_amount', $expectedAmount)->whereIn('status', ['pending'])->exists()) {
            $randomOffset = mt_rand(100, 999) / 1000000;
            $expectedAmount = round($amount + $randomOffset, 6);
        }

        $invoiceId = 'INV-' . strtoupper(Str::random(10));
        
        // Generate base64 invoice token for checkout link
        $token = base64_encode("{$expectedAmount}|" . time() . "|{$invoiceId}");
        $paymentLink = route('checkout.show', ['token' => $token]);

        $invoice = Invoice::create([
            'store_id' => $store->id,
            'invoice_id' => $invoiceId,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'amount' => $amount,
            'expected_amount' => $expectedAmount,
            'currency' => $request->currency,
            'status' => 'pending',
            'is_sandbox' => $request->boolean('is_sandbox', false),
            'payment_link' => $paymentLink,
            'callback_url' => $request->callback_url,
            'expires_at' => now()->addMinutes(30),
        ]);

        $sandboxStr = $invoice->is_sandbox ? ' (Sandbox)' : '';
        ActivityLog::log('invoice_create', "Manually created invoice '{$invoiceId}' for {$amount} {$request->currency}{$sandboxStr}", $store->id);

        return redirect()->route('dashboard.invoices')->with('success', 'Invoice created successfully.');
    }

    /**
     * Show QR Code manager page.
     */
    public function qrIndex()
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            $qrCodes = QrChecksum::with('user')->latest()->get();
        } else {
            $qrCodes = QrChecksum::where('user_id', $user->id)->latest()->get();
        }

        $blacklist = QrBlacklist::latest()->get();

        return view('dashboard.qr.index', [
            'qrCodes' => $qrCodes,
            'blacklist' => $blacklist
        ]);
    }

    /**
     * Upload and verify QR code file (anti-hijack system).
     */
    public function qrUpload(Request $request)
    {
        $request->validate([
            'qr_image' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
            'qr_data' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $file = $request->file('qr_image');

        // Calculate file MD5 checksum
        $fileChecksum = md5_file($file->getRealPath());

        // Calculate SHA256 of QR code contents
        $qrData = trim($request->qr_data);
        $qrDataHash = hash('sha256', $qrData);

        // 1. Check if the QR code data is blacklisted (Security Risk)
        if (QrBlacklist::where('qr_data_hash', $qrDataHash)->exists()) {
            return redirect()->back()->with('error', 'Anti-Hijack Block: This QR code payload matches a blacklisted entry. Verification rejected.');
        }

        // 2. Check if the QR code data belongs to another user (Ownership Theft)
        $existingRegistry = QrRegistry::where('qr_data_hash', $qrDataHash)->first();
        if ($existingRegistry && $existingRegistry->owner_user_id !== $user->id) {
            return redirect()->back()->with('error', 'Anti-Hijack Block: This QR code content is registered to another store owner. Verification rejected.');
        }

        // 3. Register it if not registered yet
        if (!$existingRegistry) {
            QrRegistry::create([
                'qr_data_hash' => $qrDataHash,
                'owner_user_id' => $user->id,
                'status' => 'active'
            ]);
        }

        // 4. Save file
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('uploads/qrcodes'), $fileName);
        $filePath = 'uploads/qrcodes/' . $fileName;

        // 5. Save Checksum log
        QrChecksum::create([
            'user_id' => $user->id,
            'file_path' => $filePath,
            'checksum' => $fileChecksum,
            'qr_data' => $qrData,
            'qr_data_hash' => $qrDataHash,
        ]);

        ActivityLog::log('qr_upload', "Uploaded and verified QR Code image with checksum '{$fileChecksum}'");

        return redirect()->back()->with('success', 'QR Code successfully uploaded and verified. Registries updated.');
    }

    /**
     * Check if a QR code payload is allowed (not blacklisted and not owned by another merchant).
     */
    public function qrCheck(Request $request)
    {
        $request->validate([
            'qr_data' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $qrData = trim($request->qr_data);
        $qrDataHash = hash('sha256', $qrData);

        // 1. Check if the QR code data is blacklisted
        if (QrBlacklist::where('qr_data_hash', $qrDataHash)->exists()) {
            return response()->json([
                'allowed' => false,
                'message' => 'Anti-Hijack Block: This QR code payload matches a blacklisted entry.'
            ]);
        }

        // 2. Check if the QR code data belongs to another user
        $existingRegistry = QrRegistry::where('qr_data_hash', $qrDataHash)->first();
        if ($existingRegistry && $existingRegistry->owner_user_id !== $user->id) {
            return response()->json([
                'allowed' => false,
                'message' => 'Anti-Hijack Block: This QR code belongs to another store owner.'
            ]);
        }

        return response()->json([
            'allowed' => true,
            'message' => 'QR Code payload is valid and available.'
        ]);
    }

    /**
     * Delete QR code.
     */
    public function qrDelete(QrChecksum $qrCode)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $qrCode->user_id !== $user->id) {
            abort(403);
        }

        // Remove from file system if exists
        $absolutePath = public_path($qrCode->file_path);
        if (file_exists($absolutePath)) {
            @unlink($absolutePath);
        }

        ActivityLog::log('qr_delete', "Deleted QR Code image with checksum '{$qrCode->checksum}'");
        $qrCode->delete();

        return redirect()->back()->with('success', 'QR Code deleted successfully.');
    }

    /**
     * Add a QR code payload data to blocklist.
     */
    public function qrBlocklistStore(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'qr_data' => 'required|string|max:1000',
            'note' => 'nullable|string|max:255',
        ]);

        $qrData = trim($request->qr_data);
        $qrDataHash = hash('sha256', $qrData);

        // 1. Create or update the blocklist entry
        QrBlacklist::updateOrCreate(
            ['qr_data_hash' => $qrDataHash],
            ['note' => $request->note]
        );

        // 2. Deregister/delete any existing matching QR codes from registry and checksums
        \App\Models\QrRegistry::where('qr_data_hash', $qrDataHash)->delete();
        
        $checksums = QrChecksum::where('qr_data_hash', $qrDataHash)->get();
        foreach ($checksums as $qrCode) {
            $absolutePath = public_path($qrCode->file_path);
            if (file_exists($absolutePath)) {
                @unlink($absolutePath);
            }
            $qrCode->delete();
        }

        ActivityLog::log('qr_blacklist_add', "Added QR Code payload hash '{$qrDataHash}' to blocklist");

        return redirect()->back()->with('success', 'QR Code successfully blacklisted and corresponding registries removed.');
    }

    /**
     * Remove a QR code payload hash from blocklist.
     */
    public function qrBlocklistDelete($id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $entry = QrBlacklist::findOrFail($id);
        $hash = $entry->qr_data_hash;
        $entry->delete();

        ActivityLog::log('qr_blacklist_remove', "Removed QR Code payload hash '{$hash}' from blocklist");

        return redirect()->back()->with('success', 'QR Code successfully removed from blocklist.');
    }

    /**
     * Display all stores for the merchant/admin.
     */
    public function storesIndex()
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            $stores = Store::with('user')->latest()->get();
        } else {
            $stores = Store::where('user_id', $user->id)->latest()->get();
        }

        return view('dashboard.stores.index', compact('stores'));
    }

    /**
     * Edit specific store details.
     */
    public function editStore(Store $store)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $store->user_id !== $user->id) {
            abort(403);
        }

        return view('dashboard.stores.edit', compact('store'));
    }

    /**
     * Update specific store details.
     */
    public function updateStore(Request $request, Store $store)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $store->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'theme_color' => 'nullable|string|max:20',
            'custom_css' => 'nullable|string|max:5000',
            'hide_branding' => 'nullable|boolean',
            'checkout_layout' => 'nullable|string|in:left,right',
        ]);

        $store->update([
            'name' => $request->name,
            'domain' => $request->domain,
            'theme_color' => $request->theme_color,
            'custom_css' => $request->custom_css,
            'hide_branding' => $request->boolean('hide_branding', false),
            'checkout_layout' => $request->checkout_layout ?? 'right',
        ]);

        ActivityLog::log('store_update', "Updated store details for '{$store->name}'", $store->id);

        return redirect()->route('stores.index')->with('success', 'Store details updated successfully.');
    }

    /**
     * Toggle active status of a store.
     */
    public function toggleStoreStatus(Store $store)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $store->user_id !== $user->id) {
            abort(403);
        }

        $store->update([
            'is_active' => !$store->is_active
        ]);

        $status = $store->is_active ? 'activated' : 'deactivated';
        ActivityLog::log('store_toggle_status', "Toggled store active status for '{$store->name}' to " . ucfirst($status), $store->id);

        return redirect()->route('stores.index')->with('success', "Store successfully {$status}.");
    }

    /**
     * Regenerate Store API key.
     */
    public function regenerateStoreKey(Store $store)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $store->user_id !== $user->id) {
            abort(403);
        }

        $store->update([
            'api_key' => 'st_' . Str::random(32)
        ]);

        ActivityLog::log('store_key_regenerate', "Regenerated API Key for store '{$store->name}'", $store->id);

        return redirect()->route('stores.index')->with('success', 'Store API Key regenerated successfully.');
    }

    /**
     * Delete a store and cascade delete configurations and invoices.
     */
    public function deleteStore(Store $store)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $store->user_id !== $user->id) {
            abort(403);
        }

        ActivityLog::log('store_delete', "Deleted store '{$store->name}' along with configs and invoices");
        $store->delete();

        return redirect()->route('stores.index')->with('success', 'Store and all associated configuration parameters deleted successfully.');
    }

    /**
     * Display API request/response logs.
     */
    public function apiLogs(Request $request)
    {
        $user = Auth::user();
        $query = \App\Models\ApiLog::with(['store', 'user']);

        if ($user->role !== 'admin') {
            $storeIds = Store::where('user_id', $user->id)->pluck('id');
            $query->whereIn('store_id', $storeIds);
        }

        // Apply filters
        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('status')) {
            $query->where('response_status', $request->status);
        }

        if ($request->filled('url')) {
            $query->where('url', 'like', '%' . $request->url . '%');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ip_address', 'like', '%' . $search . '%')
                  ->orWhere('request_body', 'like', '%' . $search . '%')
                  ->orWhere('response_body', 'like', '%' . $search . '%');
            });
        }

        $logs = $query->latest()->paginate(25);

        return view('dashboard.api-logs', compact('logs'));
    }

    /**
     * Display dashboard activity logs.
     */
    public function activityLogs(Request $request)
    {
        $user = Auth::user();
        $query = \App\Models\ActivityLog::with(['store', 'user']);

        if ($user->role !== 'admin') {
            $storeIds = Store::where('user_id', $user->id)->pluck('id');
            $query->where(function($q) use ($storeIds, $user) {
                $q->whereIn('store_id', $storeIds)
                  ->orWhere('user_id', $user->id);
            });
        }

        // Apply filters
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', '%' . $search . '%')
                  ->orWhere('ip_address', 'like', '%' . $search . '%');
            });
        }

        $logs = $query->latest()->paginate(25);

        // Fetch distinct action types for filter dropdown
        $actionQuery = \App\Models\ActivityLog::query();
        if ($user->role !== 'admin') {
            $storeIds = Store::where('user_id', $user->id)->pluck('id');
            $actionQuery->where(function($q) use ($storeIds, $user) {
                $q->whereIn('store_id', $storeIds)
                  ->orWhere('user_id', $user->id);
            });
        }
        $actions = $actionQuery->distinct()->pluck('action');

        return view('dashboard.activity-logs', compact('logs', 'actions'));
    }

    /**
     * Toggle Multi-Merchant System globally.
     */
    public function adminToggleMerchantSystem()
    {
        $current = \App\Models\Setting::get('merchant_system_enabled', '1');
        $newVal = $current === '1' ? '0' : '1';
        \App\Models\Setting::set('merchant_system_enabled', $newVal);
        
        $statusStr = $newVal === '1' ? 'enabled' : 'disabled';
        \App\Models\ActivityLog::log('system_settings', "Merchant system has been {$statusStr}");
        
        return redirect()->back()->with('success', "Merchant system successfully {$statusStr}.");
    }

    /**
     * Show merchant notification & security settings.
     */
    public function showSecuritySettings()
    {
        $user = Auth::user();
        
        // Generate TOTP secret and QR if not already set, for Authenticator App setup
        $totpSecret = $user->two_factor_secret;
        if (empty($totpSecret)) {
            $totpSecret = \App\Services\TotpService::generateSecret();
        }

        $qrCodeUrl = \App\Services\TotpService::getQrCodeUrl($user->email, $totpSecret, 'OmniPay');

        return view('dashboard.security', compact('user', 'totpSecret', 'qrCodeUrl'));
    }

    /**
     * Update merchant notification preferences.
     */
    public function updateNotificationSettings(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'notify_invoice_created' => $request->has('notify_invoice_created'),
            'notify_invoice_paid' => $request->has('notify_invoice_paid'),
            'notify_invoice_expired' => $request->has('notify_invoice_expired'),
            'notify_login' => $request->has('notify_login'),
        ]);

        ActivityLog::log('settings_update', 'Updated email notification preferences');

        return redirect()->back()->with('success', 'Notification preferences updated successfully.');
    }

    /**
     * Setup or update Two-Factor Authentication method.
     */
    public function update2faSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'two_factor_method' => 'required|in:none,email,totp',
            'totp_secret' => 'nullable|string|size:16',
            'totp_code' => 'nullable|string|size:6',
        ]);

        $method = $request->two_factor_method;

        if ($method === 'totp') {
            // Must verify a code to enable TOTP!
            $secret = $request->totp_secret ?: $user->two_factor_secret;
            $code = $request->totp_code;

            if (empty($secret) || empty($code) || !\App\Services\TotpService::verifyCode($secret, $code)) {
                return redirect()->back()->with('error', 'Failed to enable Authenticator 2FA. The provided code is invalid.');
            }

            $user->update([
                'two_factor_method' => 'totp',
                'two_factor_secret' => $secret,
            ]);
        } elseif ($method === 'email') {
            $user->update([
                'two_factor_method' => 'email',
            ]);
        } else {
            $user->update([
                'two_factor_method' => 'none',
            ]);
        }

        ActivityLog::log('security_update', 'Updated 2FA method status to ' . strtoupper($method));

        return redirect()->back()->with('success', 'Two-Factor Authentication settings updated successfully.');
    }

    /**
     * Save global SMTP configuration settings (Admin only).
     */
    public function adminSaveSmtpSettings(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'required|in:tls,ssl,none',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
        ]);

        \App\Models\Setting::set('mail_host', $request->mail_host);
        \App\Models\Setting::set('mail_port', $request->mail_port);
        \App\Models\Setting::set('mail_username', $request->mail_username ?? '');
        \App\Models\Setting::set('mail_password', $request->mail_password ?? '');
        \App\Models\Setting::set('mail_encryption', $request->mail_encryption);
        \App\Models\Setting::set('mail_from_address', $request->mail_from_address);
        \App\Models\Setting::set('mail_from_name', $request->mail_from_name);

        ActivityLog::log('system_settings', 'Updated global SMTP mail configurations');

        return redirect()->back()->with('success', 'Global SMTP configurations updated successfully.');
    }

    /**
     * Toggle Dynamic Smart Captcha globally (Admin only).
     */
    public function adminToggleCaptcha()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $current = \App\Models\Setting::get('captcha_enabled', '0');
        $newVal = $current === '1' ? '0' : '1';
        \App\Models\Setting::set('captcha_enabled', $newVal);

        $statusStr = $newVal === '1' ? 'enabled' : 'disabled';
        ActivityLog::log('system_settings', "Global smart captcha has been {$statusStr}");

        return redirect()->back()->with('success', "Global captcha successfully {$statusStr}.");
    }
}

