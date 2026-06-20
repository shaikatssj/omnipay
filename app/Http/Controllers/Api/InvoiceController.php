<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * Create a new payment invoice.
     */
    public function store(Request $request)
    {
        $apiKey = $request->header('X-API-KEY');
        if (empty($apiKey)) {
            return response()->json(['error' => 'API key is required in X-API-KEY header'], 401);
        }

        $store = Store::where('api_key', $apiKey)->where('is_active', true)->first();
        if (!$store) {
            return response()->json(['error' => 'Invalid or inactive API key'], 401);
        }

        if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1' && $store->user->role !== 'admin') {
            return response()->json(['error' => 'Merchant system is disabled. Only Admin is authorized.'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'currency' => 'nullable|string|max:10',
            'callback_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
            'meta_data' => 'nullable|array',
            'is_sandbox' => 'nullable|boolean',
        ]);

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
            'currency' => $request->currency ?? 'USDT',
            'status' => 'pending',
            'is_sandbox' => $request->boolean('is_sandbox', false),
            'payment_link' => $paymentLink,
            'callback_url' => $request->callback_url,
            'cancel_url' => $request->cancel_url,
            'meta_data' => $request->meta_data,
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json([
            'success' => true,
            'invoice_id' => $invoice->invoice_id,
            'amount' => $invoice->amount,
            'expected_amount' => $invoice->expected_amount,
            'currency' => $invoice->currency,
            'payment_link' => $invoice->payment_link,
            'expires_at' => $invoice->expires_at->toDateTimeString(),
        ], 201);
    }
}
