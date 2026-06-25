<?php

namespace App\Plugins;

use App\Contracts\PaymentDriverInterface;
use App\Models\Invoice;
use App\Models\SyncedTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class bKashDriver implements PaymentDriverInterface
{
    public function getCode(): string
    {
        return 'bkash';
    }

    public function getName(): string
    {
        return 'bKash (MFS)';
    }

    public function initiatePayment(Invoice $invoice, array $settings): array
    {
        $isBDT = strtoupper($invoice->currency) === 'BDT';
        $rate = $isBDT ? 1.0 : floatval($settings['conversion_rate'] ?? 130);
        $totalBDT = ceil($invoice->amount * $rate);
        
        $meta = $invoice->meta_data ?? [];
        $receivedBDT = floatval($meta['bkash_received_amount_bdt'] ?? 0);
        $remainingBDT = $totalBDT - $receivedBDT;

        $isApiMode = !empty($settings['app_key']) && !empty($settings['username']);

        if ($isApiMode) {
            // API Tokenized mode info
            return [
                'mode' => 'api',
                'conversion_rate' => $rate,
                'total_bdt' => $totalBDT,
                'received_bdt' => $receivedBDT,
                'remaining_bdt' => $remainingBDT,
                'instructions' => 'Redirecting to bKash payment gateway...',
                'redirect_url' => route('checkout.status', ['invoice' => $invoice->invoice_id, 'initiate_api' => true])
            ];
        }

        return [
            'mode' => 'personal',
            'phone' => $settings['phone'] ?? '',
            'conversion_rate' => $rate,
            'total_bdt' => $totalBDT,
            'received_bdt' => $receivedBDT,
            'remaining_bdt' => $remainingBDT,
            'instructions' => 'Send Send-Money to the personal number and input the Transaction ID.',
            'qr_code' => isset($settings['qr_code']) ? asset($settings['qr_code']) : null
        ];
    }

    public function verifyPayment(Invoice $invoice, array $settings, array $requestData): array
    {
        $isBDT = strtoupper($invoice->currency) === 'BDT';
        $rate = $isBDT ? 1.0 : floatval($settings['conversion_rate'] ?? 130);
        $totalBDT = ceil($invoice->amount * $rate);
        
        $meta = $invoice->meta_data ?? [];
        $receivedBDT = floatval($meta['bkash_received_amount_bdt'] ?? 0);
        $remainingBDT = $totalBDT - $receivedBDT;

        $isApiMode = !empty($settings['app_key']) && !empty($settings['username']);

        // Handle API checkout initiation redirect
        if ($isApiMode && isset($requestData['initiate_api'])) {
            $baseUrl = (($settings['mode'] ?? 'sandbox') === 'live') 
                ? 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized' 
                : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized';

            try {
                // 1. Grant Token
                $tokenResponse = Http::withHeaders([
                    'username' => $settings['username'],
                    'password' => $settings['password'],
                ])->post($baseUrl . '/checkout/token/grant', [
                    'app_key' => $settings['app_key'],
                    'app_secret' => $settings['app_secret_key'],
                ]);

                if (!$tokenResponse->successful() || empty($tokenResponse->json('id_token'))) {
                    return ['status' => 'error', 'message' => 'Failed to obtain bKash API Token: ' . $tokenResponse->body()];
                }

                $token = $tokenResponse->json('id_token');
                $meta['bkash_id_token'] = $token;

                // 2. Create Payment
                $createResponse = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => $token,
                    'X-APP-Key' => $settings['app_key']
                ])->post($baseUrl . '/checkout/create', [
                    'mode' => '0011',
                    'amount' => (string)$remainingBDT,
                    'currency' => 'BDT',
                    'intent' => 'sale',
                    'payerReference' => 'OmniPay',
                    'merchantInvoiceNumber' => rand(1000, 9999) . '-OP-' . $invoice->invoice_id,
                    'callbackURL' => route('checkout.status', ['invoice' => $invoice->invoice_id, 'bkash_callback' => true])
                ]);

                if (!$createResponse->successful() || empty($createResponse->json('bkashURL'))) {
                    return ['status' => 'error', 'message' => 'Failed to create bKash checkout: ' . $createResponse->body()];
                }

                $meta['bkash_payment_id'] = $createResponse->json('paymentID');
                $invoice->update(['meta_data' => $meta]);

                return [
                    'status' => 'redirect',
                    'redirect' => $createResponse->json('bkashURL')
                ];
            } catch (\Exception $e) {
                Log::error("bKash API Checkout init error: " . $e->getMessage());
                return ['status' => 'error', 'message' => 'bKash API error: ' . $e->getMessage()];
            }
        }

        // Handle API checkout callback
        if ($isApiMode && isset($requestData['bkash_callback'])) {
            $status = $requestData['status'] ?? '';
            $paymentID = $requestData['paymentID'] ?? '';

            if ($status === 'success') {
                $baseUrl = (($settings['mode'] ?? 'sandbox') === 'live') 
                    ? 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized' 
                    : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized';

                $token = $meta['bkash_id_token'] ?? '';

                try {
                    $executeResponse = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => $token,
                        'X-APP-Key' => $settings['app_key']
                    ])->post($baseUrl . '/checkout/execute', [
                        'paymentID' => $paymentID
                    ]);

                    $obj = $executeResponse->json();

                    if ($executeResponse->successful() && isset($obj['statusMessage']) && $obj['statusMessage'] === 'Successful') {
                        $meta['bkash_payments'][] = [
                            'trxid' => $obj['trxID'],
                            'amount' => floatval($obj['amount']),
                            'payment_id' => $obj['paymentID'],
                            'time' => time()
                        ];
                        $meta['bkash_received_amount_bdt'] = floatval($obj['amount']);

                        $invoice->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'meta_data' => $meta
                        ]);

                        return [
                            'status' => 'success',
                            'message' => 'Payment successful',
                            'redirect' => route('checkout.success', ['invoice' => $invoice->invoice_id])
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error("bKash API Execute Callback error: " . $e->getMessage());
                }
            }

            return [
                'status' => 'error',
                'message' => 'bKash payment flow cancelled or failed.'
            ];
        }

        // Personal Mode (SMS Synced)
        // 1. Check if checking for presence of matching transaction (GET polling)
        if (isset($requestData['poll']) && $requestData['poll']) {
            $minAmt = $remainingBDT - 1;
            $maxAmt = $remainingBDT + 1;

            $transaction = SyncedTransaction::where('user_id', $invoice->store->user_id)
                ->where('sender', 'bkash')
                ->whereBetween('amount', [$minAmt, $maxAmt])
                ->where('created_at', '>=', now()->subMinutes(10))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($transaction) {
                $trxid = $transaction->trxid;
                $trxList = [$trxid];
                $len = strlen($trxid);

                if ($len >= 6) {
                    $prefix = substr($trxid, 0, 3);
                    $suffix = substr($trxid, -3);
                    $middle = substr($trxid, 3, $len - 6);

                    for ($i = 0; $i < 4; $i++) {
                        $fake = $prefix . str_shuffle($middle) . $suffix;
                        while (in_array($fake, $trxList)) {
                            $fake = $prefix . str_shuffle($middle) . $suffix;
                        }
                        $trxList[] = $fake;
                    }
                }
                shuffle($trxList);

                return [
                    'status' => 'found',
                    'transactions' => $trxList,
                    'amount' => $remainingBDT,
                    'received' => $receivedBDT,
                    'total' => $totalBDT
                ];
            }

            return [
                'status' => 'not_found',
                'amount' => $remainingBDT,
                'received' => $receivedBDT,
                'total' => $totalBDT
            ];
        }

        // 2. Submit Transaction ID (POST verification)
        if (empty($requestData['trx_id'])) {
            return ['status' => 'error', 'message' => 'Transaction ID is required'];
        }

        $trx_id = strtoupper(trim($requestData['trx_id']));

        // Replay check on current invoice
        $payments = $meta['bkash_payments'] ?? [];
        foreach ($payments as $pay) {
            if ($pay['trxid'] === $trx_id) {
                return ['status' => 'error', 'message' => 'This Transaction ID has already been verified on this invoice'];
            }
        }

        // Find the transaction in synced transaction logs
        $transaction = SyncedTransaction::where('user_id', $invoice->store->user_id)
            ->where('trxid', $trx_id)
            ->where('sender', 'bkash')
            ->first();

        if ($transaction) {
            $paidAmountBDT = floatval($transaction->amount);
            $newReceivedBDT = $receivedBDT + $paidAmountBDT;

            // Log the payment details
            $meta['bkash_payments'][] = [
                'trxid' => $trx_id,
                'amount' => $paidAmountBDT,
                'time' => time()
            ];
            $meta['bkash_received_amount_bdt'] = $newReceivedBDT;

            // Delete to prevent double-use
            $transaction->delete();

            if ($newReceivedBDT >= $totalBDT) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'meta_data' => $meta
                ]);

                return [
                    'status' => 'success',
                    'message' => 'Payment verified. Invoice fully paid.',
                    'paid_amount' => $paidAmountBDT,
                    'total_received' => $newReceivedBDT,
                    'total_expected' => $totalBDT,
                    'remaining' => 0
                ];
            } else {
                $invoice->update([
                    'meta_data' => $meta
                ]);

                return [
                    'status' => 'partial',
                    'message' => 'Partial payment verified.',
                    'paid_amount' => $paidAmountBDT,
                    'total_received' => $newReceivedBDT,
                    'total_expected' => $totalBDT,
                    'remaining' => $totalBDT - $newReceivedBDT
                ];
            }
        } else {
            // Not found - alert merchant if not sent already
            if (!isset($meta['emails_sent'][$trx_id])) {
                $meta['emails_sent'][$trx_id] = true;
                $invoice->update(['meta_data' => $meta]);

                // Send email alert to merchant user
                \App\Services\MailNotificationService::sendManualVerificationWaiting($invoice, 'bKash', $trx_id, $totalBDT, $receivedBDT);
            }

            return [
                'status' => 'waiting_verification',
                'message' => 'Transaction not uploaded by reader yet. We have alerted the merchant. Please try again in a few moments.'
            ];
        }
    }

    public function refund(Invoice $invoice, array $settings, array $refundData): array
    {
        $meta = $invoice->meta_data ?? [];
        $isApiMode = !empty($settings['app_key']) && !empty($settings['username']);

        if (!$isApiMode) {
            return [
                'status' => 'manual',
                'message' => 'Manual MFS refund required. Please refund ' . ($refundData['amount'] ?? $invoice->amount) . ' BDT manually to the user.'
            ];
        }

        // Tokenized API Refund
        $baseUrl = (($settings['mode'] ?? 'sandbox') === 'live') 
            ? 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized' 
            : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized';

        try {
            // Find the payment transaction ID and paymentID from invoice meta_data
            $bkashPayments = $meta['bkash_payments'] ?? [];
            if (empty($bkashPayments)) {
                return ['status' => 'error', 'message' => 'No payment information found to refund.'];
            }

            $paymentDetails = end($bkashPayments); // Get the latest payment details
            $paymentId = $paymentDetails['payment_id'] ?? ($meta['bkash_payment_id'] ?? '');
            $trxId = $paymentDetails['trxid'] ?? '';

            if (empty($paymentId) || empty($trxId)) {
                return ['status' => 'error', 'message' => 'bKash paymentId or trxId not found.'];
            }

            // 1. Grant Token
            $tokenResponse = Http::withHeaders([
                'username' => $settings['username'],
                'password' => $settings['password'],
            ])->post($baseUrl . '/checkout/token/grant', [
                'app_key' => $settings['app_key'],
                'app_secret' => $settings['app_secret_key'],
            ]);

            if (!$tokenResponse->successful() || empty($tokenResponse->json('id_token'))) {
                return ['status' => 'error', 'message' => 'Failed to obtain token for refund: ' . $tokenResponse->body()];
            }

            $token = $tokenResponse->json('id_token');

            // 2. Execute Refund API
            $refundAmount = $refundData['amount'] ?? ($paymentDetails['amount'] ?? 0);
            $refundResponse = Http::withHeaders([
                'Authorization' => $token,
                'X-APP-Key' => $settings['app_key']
            ])->post($baseUrl . '/checkout/refund', [
                'paymentID' => $paymentId,
                'amount' => (string)ceil($refundAmount),
                'trxID' => $trxId,
                'sku' => $refundData['sku'] ?? 'refund',
                'reason' => $refundData['reason'] ?? 'Merchant refund request',
            ]);

            $res = $refundResponse->json();
            if ($refundResponse->successful() && isset($res['refundTransactionStatus']) && strtolower($res['refundTransactionStatus']) === 'completed') {
                $meta['refund_logs'][] = [
                    'trxid' => $res['trxID'],
                    'refund_trxid' => $res['refundTrxID'],
                    'amount' => $res['amount'],
                    'time' => time(),
                ];
                $invoice->update([
                    'status' => 'refunded',
                    'meta_data' => $meta
                ]);

                return [
                    'status' => 'success',
                    'message' => 'Refund processed successfully via bKash API.',
                    'refund_trxid' => $res['refundTrxID']
                ];
            }

            $errMsg = $res['errorMessageEn'] ?? ($res['statusMessage'] ?? 'bKash API Refund failed.');
            return ['status' => 'error', 'message' => $errMsg];

        } catch (\Exception $e) {
            Log::error("bKash API Refund error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Refund error: ' . $e->getMessage()];
        }
    }
}
