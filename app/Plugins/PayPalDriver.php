<?php

namespace App\Plugins;

use App\Contracts\PaymentDriverInterface;
use App\Models\Invoice;

class PayPalDriver implements PaymentDriverInterface
{
    public function getCode(): string
    {
        return 'paypal';
    }

    public function getName(): string
    {
        return 'PayPal';
    }

    public function initiatePayment(Invoice $invoice, array $settings): array
    {
        $clientId = $settings['client_id'] ?? '';
        $secret = $settings['client_secret'] ?? '';
        
        if (empty($clientId) || empty($secret)) {
            throw new \Exception('PayPal credentials are not configured.');
        }

        // Simulating redirect to PayPal checkout
        return [
            'type' => 'redirect',
            'url' => route('checkout.simulated', ['invoice' => $invoice->invoice_id, 'gateway' => 'paypal'])
        ];
    }

    public function verifyPayment(Invoice $invoice, array $settings, array $requestData): array
    {
        $status = $requestData['status'] ?? 'pending';
        
        if ($status === 'success') {
            return [
                'status' => 'success',
                'message' => 'Payment verified successfully by PayPal.',
                'transaction_id' => 'PAYID-' . strtoupper(uniqid())
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
            'message' => 'Refund processed successfully via PayPal.'
        ];
    }
}
