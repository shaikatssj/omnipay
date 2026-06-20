<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GatewayCompatibilityController extends Controller
{
    /**
     * Compatibility endpoint for creating payments.
     */
    public function createCharge(Request $request)
    {
        // Extract key from any possible header
        $apiKey = $request->header('X-API-KEY') 
               ?? $request->header('mh-piprapay-api-key') 
               ?? $request->header('MHS-PIPRAPAY-API-KEY') 
               ?? $request->input('api_key');

        if (empty($apiKey)) {
            return response()->json(['error' => ['message' => 'API Key is missing']], 401);
        }

        $store = Store::where('api_key', $apiKey)->where('is_active', true)->first();
        if (!$store) {
            return response()->json(['error' => ['message' => 'Invalid or inactive API Key']], 401);
        }

        if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1' && $store->user->role !== 'admin') {
            return response()->json(['error' => ['message' => 'Merchant system is disabled. Only Admin is authorized.']], 403);
        }

        // Parse legacy fields
        $fullName = $request->input('full_name') ?? $request->input('customer_name') ?? 'Client';
        
        $email = $request->input('email_mobile') 
              ?? $request->input('email_address') 
              ?? $request->input('customer_email');
              
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = 'customer@omnipay.com'; // fallback
        }

        $amount = floatval($request->input('amount', 0));
        if ($amount <= 0) {
            return response()->json(['error' => ['message' => 'Amount must be greater than zero']], 400);
        }

        $currency = $request->input('currency') ?? 'USDT';
        
        $callbackUrl = $request->input('webhook_url') 
                    ?? $request->input('redirect_url') 
                    ?? $request->input('return_url');
                    
        $cancelUrl = $request->input('cancel_url');
        
        $metaData = $request->input('metadata') ?? $request->input('meta_data') ?? [];

        // Generate a random fractional offset
        $randomOffset = mt_rand(100, 999) / 1000000;
        $expectedAmount = round($amount + $randomOffset, 6);

        while (Invoice::where('expected_amount', $expectedAmount)->whereIn('status', ['pending'])->exists()) {
            $randomOffset = mt_rand(100, 999) / 1000000;
            $expectedAmount = round($amount + $randomOffset, 6);
        }

        $invoiceId = 'INV-' . strtoupper(Str::random(10));
        
        $token = base64_encode("{$expectedAmount}|" . time() . "|{$invoiceId}");
        $paymentLink = route('checkout.show', ['token' => $token]);

        $invoice = Invoice::create([
            'store_id' => $store->id,
            'invoice_id' => $invoiceId,
            'customer_name' => $fullName,
            'customer_email' => $email,
            'amount' => $amount,
            'expected_amount' => $expectedAmount,
            'currency' => $currency,
            'status' => 'pending',
            'is_sandbox' => false,
            'payment_link' => $paymentLink,
            'callback_url' => $callbackUrl,
            'cancel_url' => $cancelUrl,
            'meta_data' => $metaData,
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json([
            'status' => true,
            'success' => true,
            'pp_url' => $invoice->payment_link,
            'payment_link' => $invoice->payment_link,
            'invoice_id' => $invoice->invoice_id,
            'pp_id' => $invoice->invoice_id,
            'amount' => $invoice->amount,
            'expected_amount' => $invoice->expected_amount,
            'currency' => $invoice->currency,
        ], 200);
    }

    /**
     * Compatibility endpoint for verifying payments.
     */
    public function verifyPayment(Request $request)
    {
        $apiKey = $request->header('X-API-KEY') 
               ?? $request->header('mh-piprapay-api-key') 
               ?? $request->header('MHS-PIPRAPAY-API-KEY') 
               ?? $request->input('api_key');

        if (empty($apiKey)) {
            return response()->json(['error' => ['message' => 'API Key is missing']], 401);
        }

        $store = Store::where('api_key', $apiKey)->where('is_active', true)->first();
        if (!$store) {
            return response()->json(['error' => ['message' => 'Invalid or inactive API Key']], 401);
        }

        if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1' && $store->user->role !== 'admin') {
            return response()->json(['error' => ['message' => 'Merchant system is disabled. Only Admin is authorized.']], 403);
        }

        $ppId = $request->input('pp_id') ?? $request->input('invoice_id');
        if (empty($ppId)) {
            return response()->json(['error' => ['message' => 'pp_id is required']], 400);
        }

        $invoice = Invoice::where('store_id', $store->id)->where('invoice_id', $ppId)->first();
        if (!$invoice) {
            return response()->json(['error' => ['message' => 'Invoice not found']], 404);
        }

        $status = $invoice->status === 'paid' ? 'completed' : $invoice->status;

        return response()->json([
            'status' => $status,
            'transaction_id' => $invoice->invoice_id,
            'pp_id' => $invoice->invoice_id,
            'amount' => $invoice->amount,
            'metadata' => $invoice->meta_data ?? [],
        ], 200);
    }
}
