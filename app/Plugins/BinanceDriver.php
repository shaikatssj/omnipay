<?php

namespace App\Plugins;

use App\Contracts\PaymentDriverInterface;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Exception;

class BinanceDriver implements PaymentDriverInterface
{
    public function getCode(): string
    {
        return 'binance';
    }

    public function getName(): string
    {
        return 'Binance Pay';
    }

    public function initiatePayment(Invoice $invoice, array $settings): array
    {
        $meta = $invoice->meta_data ?? [];
        if (empty($meta['payment_note'])) {
            $payment_note = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $meta['payment_note'] = $payment_note;
            $invoice->update(['meta_data' => $meta]);
        } else {
            $payment_note = $meta['payment_note'];
        }

        return [
            'instructions' => 'Send USDT to the merchant Binance account. You MUST include the exact 4-digit Payment Note in the transfer note/ref field.',
            'coin' => 'USDT',
            'amount' => $invoice->amount,
            'payment_note' => $payment_note,
            'qr_code' => isset($settings['qr_code']) ? asset($settings['qr_code']) : null
        ];
    }

    public function verifyPayment(Invoice $invoice, array $settings, array $requestData): array
    {
        $apiKey = $settings['api_key'] ?? '';
        $apiSecret = $settings['api_secret'] ?? '';

        if (empty($apiKey) || empty($apiSecret)) {
            return ['status' => 'error', 'message' => 'Merchant Binance API keys are not configured.'];
        }

        $meta = $invoice->meta_data ?? [];
        $payment_note = $meta['payment_note'] ?? '';

        if (empty($payment_note)) {
            return ['status' => 'error', 'message' => 'Payment note is not generated.'];
        }

        // Mock mode for local tests
        if ($apiKey === 'd41d8cd98f00b204e9800998ecf8427e12345678901234567890123456789012') {
            if (session('simulated_binance_paid_' . $invoice->invoice_id)) {
                $data = [
                    'success' => true,
                    'data' => [
                        [
                            'transactionId' => 'SIM-BIN-' . $invoice->invoice_id,
                            'amount' => number_format($invoice->amount, 8, '.', ''),
                            'note' => $payment_note,
                            'counterpartyId' => '123456',
                            'transactionTime' => time() * 1000
                        ]
                    ]
                ];
            } else {
                $data = ['success' => true, 'data' => []];
            }
        } else {
            try {
                $data = $this->getBinanceTransactions($apiKey, $apiSecret);
            } catch (Exception $e) {
                return ['status' => 'pending', 'message' => 'Waiting for API connection: ' . $e->getMessage()];
            }
        }

        $found = false;
        $paymentDetails = null;

        if (isset($data['success']) && $data['success'] && !empty($data['data'])) {
            foreach ($data['data'] as $transaction) {
                // Binance amounts are strings, and can have trailing zeroes (e.g. 10.00000000)
                $txAmount = floatval($transaction['amount'] ?? 0);
                $expectedAmount = floatval($invoice->amount);
                
                if (abs($txAmount - $expectedAmount) < 0.000001 && ($transaction['note'] ?? '') === $payment_note) {
                    // Replay protection: Check if this transaction ID was already used
                    $txId = $transaction['transactionId'];
                    $alreadyUsed = Invoice::where('status', 'paid')
                        ->where('meta_data->binance_transaction_id', $txId)
                        ->exists();

                    if (!$alreadyUsed) {
                        $found = true;
                        $paymentDetails = $transaction;
                        break;
                    }
                }
            }
        }

        if ($found && $paymentDetails) {
            $meta['binance_transaction_id'] = $paymentDetails['transactionId'];
            $meta['binance_counterparty_id'] = $paymentDetails['counterpartyId'] ?? null;
            
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'meta_data' => $meta
            ]);

            return [
                'status' => 'success',
                'transaction_id' => $paymentDetails['transactionId'],
                'amount_received' => $paymentDetails['amount']
            ];
        }

        return [
            'status' => 'pending',
            'message' => 'Waiting for payment',
            'required_amount' => $invoice->amount,
            'payment_note' => $payment_note
        ];
    }

    /**
     * Call Binance API to get recent Pay transactions.
     */
    protected function getBinanceTransactions(string $apiKey, string $apiSecret): array
    {
        $url = 'https://api.binance.com/sapi/v1/pay/transactions';
        $timestamp = round(microtime(true) * 1000);

        $params = [
            'timestamp' => $timestamp,
            'startTime' => (time() - 86400) * 1000, // Look back 1 day
            'limit' => 100
        ];

        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $apiSecret);
        $params['signature'] = $signature;

        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $apiKey
        ])->get($url, $params);

        if ($response->failed()) {
            throw new Exception("Binance API error: " . $response->body());
        }

        return $response->json();
    }

    public function refund(Invoice $invoice, array $settings, array $refundData): array
    {
        return [
            'status' => 'manual',
            'message' => 'Manual refund required. Please transfer the funds manually back to the customer\'s Binance account.'
        ];
    }
}
