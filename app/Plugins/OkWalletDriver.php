<?php

namespace App\Plugins;

use App\Contracts\PaymentDriverInterface;
use App\Models\Invoice;
use App\Models\SyncedTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OkWalletDriver implements PaymentDriverInterface
{
    public function getCode(): string
    {
        return 'okwallet';
    }

    public function getName(): string
    {
        return 'OK Wallet (MFS)';
    }

    public function initiatePayment(Invoice $invoice, array $settings): array
    {
        $isBDT = strtoupper($invoice->currency) === 'BDT';
        $rate = $isBDT ? 1.0 : floatval($settings['conversion_rate'] ?? 130);
        $totalBDT = ceil($invoice->amount * $rate);
        
        $meta = $invoice->meta_data ?? [];
        $receivedBDT = floatval($meta['okwallet_received_amount_bdt'] ?? 0);
        $remainingBDT = $totalBDT - $receivedBDT;

        return [
            'phone' => $settings['phone'] ?? '',
            'conversion_rate' => $rate,
            'total_bdt' => $totalBDT,
            'received_bdt' => $receivedBDT,
            'remaining_bdt' => $remainingBDT,
            'instructions' => 'Send Send-Money to the OK Wallet personal number and input the Transaction ID.',
            'qr_code' => isset($settings['qr_code']) ? asset($settings['qr_code']) : null
        ];
    }

    public function verifyPayment(Invoice $invoice, array $settings, array $requestData): array
    {
        $isBDT = strtoupper($invoice->currency) === 'BDT';
        $rate = $isBDT ? 1.0 : floatval($settings['conversion_rate'] ?? 130);
        $totalBDT = ceil($invoice->amount * $rate);
        
        $meta = $invoice->meta_data ?? [];
        $receivedBDT = floatval($meta['okwallet_received_amount_bdt'] ?? 0);
        $remainingBDT = $totalBDT - $receivedBDT;

        // 1. Check if checking for presence of matching transaction (GET polling)
        if (isset($requestData['poll']) && $requestData['poll']) {
            $minAmt = $remainingBDT - 1;
            $maxAmt = $remainingBDT + 1;

            $transaction = SyncedTransaction::where('user_id', $invoice->store->user_id)
                ->where('sender', 'okwallet')
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
        $payments = $meta['okwallet_payments'] ?? [];
        foreach ($payments as $pay) {
            if ($pay['trxid'] === $trx_id) {
                return ['status' => 'error', 'message' => 'This Transaction ID has already been verified on this invoice'];
            }
        }

        // Find the transaction in synced transaction logs
        $transaction = SyncedTransaction::where('user_id', $invoice->store->user_id)
            ->where('trxid', $trx_id)
            ->where('sender', 'okwallet')
            ->first();

        if ($transaction) {
            $paidAmountBDT = floatval($transaction->amount);
            $newReceivedBDT = $receivedBDT + $paidAmountBDT;

            // Log the payment details
            $meta['okwallet_payments'][] = [
                'trxid' => $trx_id,
                'amount' => $paidAmountBDT,
                'time' => time()
            ];
            $meta['okwallet_received_amount_bdt'] = $newReceivedBDT;

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
                \App\Services\MailNotificationService::sendManualVerificationWaiting($invoice, 'OK Wallet', $trx_id, $totalBDT, $receivedBDT);
            }

            return [
                'status' => 'waiting_verification',
                'message' => 'Transaction not uploaded by reader yet. We have alerted the merchant. Please try again in a few moments.'
            ];
        }
    }

    public function refund(Invoice $invoice, array $settings, array $refundData): array
    {
        return [
            'status' => 'manual',
            'message' => 'Manual MFS refund required. Please refund the customer ' . ($refundData['amount'] ?? $invoice->amount) . ' BDT manually via OK Wallet.'
        ];
    }
}
