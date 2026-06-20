<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\StorePaymentConfig;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    protected PaymentGatewayManager $pluginManager;

    public function __construct(PaymentGatewayManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    /**
     * Show checkout screen from invoice token.
     */
    public function show($token)
    {
        try {
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);
            if (count($parts) < 3) {
                return view('checkout.error', ['message' => 'Invalid invoice link payload']);
            }

            list($expectedAmount, $time, $invoiceId) = $parts;

            $invoice = Invoice::with(['store', 'paymentMethod'])->where('invoice_id', $invoiceId)->first();
            if (!$invoice) {
                return view('checkout.error', ['message' => 'Invoice not found']);
            }

            // Expiration checks
            if ($invoice->status === 'expired' || $invoice->expires_at->isPast()) {
                if ($invoice->status === 'pending') {
                    $invoice->update(['status' => 'expired']);
                }
                return view('checkout.expired', ['invoice' => $invoice]);
            }

            if ($invoice->status === 'paid') {
                return view('checkout.success', ['invoice' => $invoice]);
            }

            // Get store active payment methods
            $configs = StorePaymentConfig::with('paymentMethod')
                ->where('store_id', $invoice->store_id)
                ->where('is_active', true)
                ->get();

            $timeLeft = max(0, $invoice->expires_at->timestamp - time());

            return view('checkout.index', [
                'invoice' => $invoice,
                'configs' => $configs,
                'timeLeft' => $timeLeft,
                'token' => $token
            ]);
        } catch (\Exception $e) {
            Log::error("Checkout show error: " . $e->getMessage());
            return view('checkout.error', ['message' => 'Could not load checkout details']);
        }
    }

    /**
     * Set selected payment method for invoice.
     */
    public function selectMethod(Request $request, $invoiceId)
    {
        $request->validate([
            'method_code' => 'required|string',
            'network' => 'nullable|string'
        ]);

        $invoice = Invoice::where('invoice_id', $invoiceId)->where('status', 'pending')->firstOrFail();
        
        $method = PaymentMethod::where('code', $request->method_code)->firstOrFail();
        $config = StorePaymentConfig::where('store_id', $invoice->store_id)
            ->where('payment_method_id', $method->id)
            ->where('is_active', true)
            ->firstOrFail();

        $invoice->update([
            'payment_method_id' => $method->id
        ]);

        // Get driver and initiate payment
        $driver = $this->pluginManager->getDriver($method->code);
        $initData = $driver->initiatePayment($invoice, $config->settings ?? []);
        $customLogo = $config->settings['logo'] ?? null;
        $initData['gateway_logo'] = $customLogo ? asset($customLogo) : ($method->logo ? asset($method->logo) : null);
        $initData['gateway_name'] = $method->name;

        return response()->json([
            'success' => true,
            'init_data' => $initData
        ]);
    }

    /**
     * Poll payment status check.
     */
    public function checkStatus(Request $request, $invoiceId)
    {
        $invoice = Invoice::where('invoice_id', $invoiceId)->first();
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        if ($invoice->status === 'paid') {
            return response()->json(['status' => 'success', 'redirect' => route('checkout.success', ['invoice' => $invoice->invoice_id])]);
        }

        if ($invoice->status === 'expired' || $invoice->expires_at->isPast()) {
            if ($invoice->status === 'pending') {
                $invoice->update(['status' => 'expired']);
            }
            return response()->json(['status' => 'expired']);
        }

        if (!$invoice->paymentMethod) {
            return response()->json(['status' => 'pending', 'message' => 'Waiting for payment method selection']);
        }

        // Get active config and driver
        $config = StorePaymentConfig::where('store_id', $invoice->store_id)
            ->where('payment_method_id', $invoice->payment_method_id)
            ->first();

        if (!$config || !$config->is_active) {
            return response()->json(['error' => 'Selected payment configuration is disabled'], 400);
        }

        $driver = $this->pluginManager->getDriver($invoice->paymentMethod->code);
        $result = $driver->verifyPayment($invoice, $config->settings ?? [], $request->all());

        if (isset($result['status']) && $result['status'] === 'success') {
            $result['redirect'] = route('checkout.success', ['invoice' => $invoice->invoice_id]);
        }

        return response()->json($result);
    }

    /**
     * Success page.
     */
    public function success($invoiceId)
    {
        $invoice = Invoice::where('invoice_id', $invoiceId)->firstOrFail();
        return view('checkout.success', ['invoice' => $invoice]);
    }

    /**
     * Simulate a successful sandbox payment.
     */
    public function simulateSandboxPayment(Request $request, $invoiceId)
    {
        $invoice = Invoice::where('invoice_id', $invoiceId)->firstOrFail();

        if (!$invoice->is_sandbox) {
            return response()->json(['success' => false, 'error' => 'This invoice is not in sandbox mode.'], 400);
        }

        if ($invoice->status !== 'pending') {
            return response()->json(['success' => false, 'error' => 'Only pending sandbox invoices can be simulated.'], 400);
        }

        // Set status to paid
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sandbox payment simulated successfully.',
            'redirect' => route('checkout.success', ['invoice' => $invoice->invoice_id])
        ]);
    }
}
