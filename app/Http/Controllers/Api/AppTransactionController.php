<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;

class AppTransactionController extends Controller
{
    /**
     * Helper to authenticate requests using X-API-KEY header or api_key parameter.
     * Supports both Store Keys and User Keys (Merchant/Admin).
     */
    private function authenticate(Request $request)
    {
        $apiKey = $request->header('X-API-KEY') ?? $request->input('api_key');
        if (empty($apiKey)) {
            return [
                'success' => false,
                'response' => response()->json(['error' => 'API key is required in X-API-KEY header or api_key parameter'], 401)
            ];
        }

        // 1. Check User API Key or SMS Sync Key
        $user = User::where('api_key', $apiKey)
            ->orWhere('sms_sync_key', $apiKey)
            ->first();

        if ($user) {
            if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1' && $user->role !== 'admin') {
                return [
                    'success' => false,
                    'response' => response()->json(['error' => 'Merchant system is disabled'], 403)
                ];
            }
            return [
                'success' => true,
                'type' => 'user',
                'user' => $user
            ];
        }

        // 2. Check Store API Key
        $store = Store::where('api_key', $apiKey)->where('is_active', true)->first();
        if ($store) {
            if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1' && $store->user->role !== 'admin') {
                return [
                    'success' => false,
                    'response' => response()->json(['error' => 'Merchant system is disabled'], 403)
                ];
            }
            return [
                'success' => true,
                'type' => 'store',
                'store' => $store,
                'user' => $store->user
            ];
        }

        return [
            'success' => false,
            'response' => response()->json(['error' => 'Invalid or inactive API key'], 401)
        ];
    }

    /**
     * Retrieve transactions and invoices for a merchant store or user.
     */
    public function index(Request $request)
    {
        $auth = $this->authenticate($request);
        if (!$auth['success']) {
            return $auth['response'];
        }

        $query = Invoice::query();
        if ($auth['type'] === 'user') {
            $storeIds = Store::where('user_id', $auth['user']->id)->pluck('id');
            $query->whereIn('store_id', $storeIds);
        } else {
            $query->where('store_id', $auth['store']->id);
        }

        // Filter by status (e.g. paid, pending, expired, cancelled)
        if ($request->has('status')) {
            $status = $request->status;
            if (strtolower($status) === 'canceled') {
                $status = 'cancelled';
            }
            $query->where('status', $status);
        }

        // Filter by invoice_id
        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        // Filter by customer email
        if ($request->has('customer_email')) {
            $query->where('customer_email', $request->customer_email);
        }

        // Order by latest
        $invoices = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'invoices' => $invoices
        ]);
    }

    /**
     * Retrieve details of a specific invoice.
     */
    public function show(Request $request, $invoiceId)
    {
        $auth = $this->authenticate($request);
        if (!$auth['success']) {
            return $auth['response'];
        }

        $query = Invoice::where('invoice_id', $invoiceId);
        if ($auth['type'] === 'user') {
            $storeIds = Store::where('user_id', $auth['user']->id)->pluck('id');
            $query->whereIn('store_id', $storeIds);
        } else {
            $query->where('store_id', $auth['store']->id);
        }

        $invoice = $query->first();
        
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        return response()->json([
            'success' => true,
            'invoice' => $invoice
        ]);
    }

    /**
     * Mark invoice as paid.
     */
    public function markPaid(Request $request, $invoiceId)
    {
        $auth = $this->authenticate($request);
        if (!$auth['success']) {
            return $auth['response'];
        }

        $query = Invoice::where('invoice_id', $invoiceId);
        if ($auth['type'] === 'user') {
            $storeIds = Store::where('user_id', $auth['user']->id)->pluck('id');
            $query->whereIn('store_id', $storeIds);
        } else {
            $query->where('store_id', $auth['store']->id);
        }

        $invoice = $query->first();
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => true,
                'message' => 'Invoice is already paid',
                'invoice' => $invoice
            ]);
        }

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        \App\Models\ActivityLog::log('invoice_mark_paid', "Marked invoice '{$invoice->invoice_id}' as paid (API)", $invoice->store_id);

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as paid successfully',
            'invoice' => $invoice
        ]);
    }

    /**
     * Mark invoice as cancelled.
     */
    public function markCancelled(Request $request, $invoiceId)
    {
        $auth = $this->authenticate($request);
        if (!$auth['success']) {
            return $auth['response'];
        }

        $query = Invoice::where('invoice_id', $invoiceId);
        if ($auth['type'] === 'user') {
            $storeIds = Store::where('user_id', $auth['user']->id)->pluck('id');
            $query->whereIn('store_id', $storeIds);
        } else {
            $query->where('store_id', $auth['store']->id);
        }

        $invoice = $query->first();
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        if ($invoice->status === 'cancelled') {
            return response()->json([
                'success' => true,
                'message' => 'Invoice is already cancelled',
                'invoice' => $invoice
            ]);
        }

        $invoice->update([
            'status' => 'cancelled'
        ]);

        \App\Models\ActivityLog::log('invoice_mark_cancelled', "Marked invoice '{$invoice->invoice_id}' as cancelled (API)", $invoice->store_id);

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as cancelled successfully',
            'invoice' => $invoice
        ]);
    }

    /**
     * Delete an invoice.
     */
    public function destroy(Request $request, $invoiceId)
    {
        $auth = $this->authenticate($request);
        if (!$auth['success']) {
            return $auth['response'];
        }

        $query = Invoice::where('invoice_id', $invoiceId);
        if ($auth['type'] === 'user') {
            $storeIds = Store::where('user_id', $auth['user']->id)->pluck('id');
            $query->whereIn('store_id', $storeIds);
        } else {
            $query->where('store_id', $auth['store']->id);
        }

        $invoice = $query->first();
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $invoiceIdSaved = $invoice->invoice_id;
        $storeIdSaved = $invoice->store_id;

        $invoice->delete();

        \App\Models\ActivityLog::log('invoice_delete', "Deleted invoice '{$invoiceIdSaved}' (API)", $storeIdSaved);

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully'
        ]);
    }

    /**
     * Retrieve synced transactions for the authenticated user/merchant.
     */
    public function syncedTransactions(Request $request)
    {
        $auth = $this->authenticate($request);
        if (!$auth['success']) {
            return $auth['response'];
        }

        $query = \App\Models\SyncedTransaction::query();
        
        if ($auth['type'] === 'user') {
            $query->where('user_id', $auth['user']->id);
        } else {
            $query->where('user_id', $auth['store']->user_id);
        }

        // Optional filter by MFS sender (e.g. bkash, nagad)
        if ($request->has('sender')) {
            $query->where('sender', $request->sender);
        }

        // Optional filter by is_used status
        if ($request->has('is_used')) {
            $query->where('is_used', $request->boolean('is_used'));
        }

        // Order by latest
        $transactions = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'transactions' => $transactions
        ]);
    }
}
