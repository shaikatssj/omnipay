<?php

namespace App\Http\Controllers;

use App\Models\PaymentLink;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicPaymentLinkController extends Controller
{
    public function show($identifier)
    {
        $link = PaymentLink::where('identifier', $identifier)->where('is_active', true)->firstOrFail();
        
        if (!$link->store->is_active) {
            abort(404, 'Store is inactive.');
        }

        return view('payment-links.public', compact('link'));
    }

    public function process(Request $request, $identifier)
    {
        $link = PaymentLink::where('identifier', $identifier)->where('is_active', true)->firstOrFail();

        if (!$link->store->is_active) {
            abort(404, 'Store is inactive.');
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'amount' => $link->amount ? 'nullable' : 'required|numeric|gt:0',
        ]);

        $amount = $link->amount ?: floatval($request->amount);

        $randomOffset = mt_rand(100, 999) / 1000000;
        $expectedAmount = round($amount + $randomOffset, 6);

        while (Invoice::where('expected_amount', $expectedAmount)->whereIn('status', ['pending'])->exists()) {
            $randomOffset = mt_rand(100, 999) / 1000000;
            $expectedAmount = round($amount + $randomOffset, 6);
        }

        $invoiceId = 'INV-' . strtoupper(Str::random(10));
        
        $token = base64_encode("{$expectedAmount}|" . time() . "|{$invoiceId}");
        $checkoutUrl = route('checkout.show', ['token' => $token]);

        Invoice::create([
            'store_id' => $link->store_id,
            'invoice_id' => $invoiceId,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'amount' => $amount,
            'expected_amount' => $expectedAmount,
            'currency' => $link->currency,
            'status' => 'pending',
            'is_sandbox' => false,
            'payment_link' => $checkoutUrl,
            'expires_at' => now()->addMinutes(30),
        ]);

        return redirect($checkoutUrl);
    }
}
