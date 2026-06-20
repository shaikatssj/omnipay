<?php

namespace App\Plugins;

use App\Contracts\PaymentDriverInterface;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Exception;

class BybitDriver implements PaymentDriverInterface
{
    public function getCode(): string
    {
        return 'bybit';
    }

    public function getName(): string
    {
        return 'Bybit Pay';
    }

    public function initiatePayment(Invoice $invoice, array $settings): array
    {
        return [
            'instructions' => 'Send the exact amount of USDT to the merchant Bybit Funding Account (FUND). Do not change the decimal digits.',
            'coin' => 'USDT',
            'amount' => $invoice->expected_amount,
            'qr_code' => isset($settings['qr_code']) ? asset($settings['qr_code']) : null
        ];
    }

    public function verifyPayment(Invoice $invoice, array $settings, array $requestData): array
    {
        $apiKey = $settings['api_key'] ?? '';
        $apiSecret = $settings['api_secret'] ?? '';

        if (empty($apiKey) || empty($apiSecret)) {
            return ['status' => 'error', 'message' => 'Merchant Bybit API keys are not configured.'];
        }

        try {
            $currentBalance = $this->getBybitBalance($apiKey, $apiSecret);
        } catch (Exception $e) {
            return ['status' => 'pending', 'message' => 'Waiting for API connection: ' . $e->getMessage()];
        }

        $meta = $invoice->meta_data ?? [];

        if (!isset($meta['bybit_initial_balance'])) {
            // First time polling: record baseline in metadata
            $meta['bybit_initial_balance'] = $currentBalance;
            $invoice->update(['meta_data' => $meta]);
            $initialBalance = $currentBalance;
        } else {
            $initialBalance = floatval($meta['bybit_initial_balance']);
        }

        $balanceDifference = round($currentBalance - $initialBalance, 6);
        $expectedAmount = round($invoice->expected_amount, 6);
        $tolerance = 0.000001;

        if (abs($balanceDifference - $expectedAmount) <= $tolerance) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now()
            ]);

            return [
                'status' => 'success',
                'amount_received' => $expectedAmount,
                'balance_increase' => $balanceDifference,
            ];
        } elseif ($balanceDifference < 0) {
            // Reset snapshot baseline if withdrawal happened
            $meta['bybit_initial_balance'] = $currentBalance;
            $invoice->update(['meta_data' => $meta]);
        }

        return [
            'status' => 'pending',
            'message' => 'Waiting for payment',
            'required_amount' => $expectedAmount,
            'current_difference' => $balanceDifference
        ];
    }

    /**
     * Call Bybit API to get FUND balance of USDT.
     */
    protected function getBybitBalance(string $apiKey, string $apiSecret): float
    {
        // For testing purposes, if credentials are dummy mock keys, return simulated balance
        if ($apiKey === 'd41d8cd98f00b204e9800998ecf8427e12345678901234567890123456789012') {
            return floatval(session('simulated_bybit_balance', 150.00));
        }

        $endpoint = '/v5/asset/transfer/query-account-coins-balance';
        $baseUrl = 'https://api.bybit.com';

        $params = [
            'accountType' => 'FUND',
            'coin' => 'USDT',
        ];

        $queryString = http_build_query($params);
        $timestamp = (string)round(microtime(true) * 1000);
        $recvWindow = '5000';

        $signaturePayload = $timestamp . $apiKey . $recvWindow . $queryString;
        $signature = hash_hmac('sha256', $signaturePayload, $apiSecret);

        $url = $baseUrl . $endpoint . '?' . $queryString;

        $response = Http::withHeaders([
            'X-BAPI-API-KEY' => $apiKey,
            'X-BAPI-SIGN' => $signature,
            'X-BAPI-TIMESTAMP' => $timestamp,
            'X-BAPI-RECV-WINDOW' => $recvWindow,
            'Content-Type' => 'application/json',
        ])->get($url);

        if ($response->failed()) {
            throw new Exception("Bybit API error: " . $response->body());
        }

        $data = $response->json();
        if (isset($data['retCode']) && $data['retCode'] === 0 && !empty($data['result']['balance'])) {
            foreach ($data['result']['balance'] as $coin) {
                if (($coin['coin'] ?? '') === 'USDT') {
                    return (float)($coin['transferBalance'] ?? 0);
                }
            }
        }

        return 0.0;
    }

    public function refund(Invoice $invoice, array $settings, array $refundData): array
    {
        return [
            'status' => 'manual',
            'message' => 'Manual refund required. Please transfer the funds manually back to the customer\'s Bybit account.'
        ];
    }
}
