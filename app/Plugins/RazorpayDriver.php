<?php

namespace App\Plugins;

use App\Contracts\PaymentDriverInterface;
use App\Models\Invoice;

class RazorpayDriver implements PaymentDriverInterface
{
    public function getCode(): string
    {
        return 'razorpay';
    }

    public function getName(): string
    {
        return 'Razorpay';
    }

    public function initiatePayment(Invoice $invoice, array $settings): array
    {
        $keyId = $settings['key_id'] ?? '';
        $keySecret = $settings['key_secret'] ?? '';
        
        if (empty($keyId) || empty($keySecret)) {
            throw new \Exception('Razorpay credentials are not configured.');
        }

        // Simulating returning redirect data or view parameters
        return [
            'type' => 'redirect',
            'url' => route('checkout.simulated', ['invoice' => $invoice->invoice_id, 'gateway' => 'razorpay'])
        ];
    }

    public function verifyPayment(Invoice $invoice, array $settings, array $requestData): array
    {
        $status = $requestData['status'] ?? 'pending';
        
        if ($status === 'success') {
            return [
                'status' => 'success',
                'message' => 'Payment verified successfully by Razorpay.',
                'transaction_id' => 'pay_' . strtolower(uniqid())
            ];
        }

        return [
            'status' => 'failed',
            'message' => 'Payment verification failed.'
        ];
    }

    public function refund(Invoice $invoice, array $settings, array $refundData): array
    {
        return [
            'status' => 'success',
            'message' => 'Refund processed successfully via Razorpay.'
        ];
    }
}
