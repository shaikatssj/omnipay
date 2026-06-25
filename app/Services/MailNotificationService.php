<?php

namespace App\Services;

use App\Models\User;
use App\Models\Invoice;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailNotificationService
{
    /**
     * Send a templated email.
     */
    public static function sendTemplatedEmail(string $to, string $templateKey, array $data, string $fallbackSubject, string $fallbackBody): void
    {
        try {
            $template = EmailTemplate::where('key', $templateKey)->first();

            if ($template) {
                $subject = $template->subject;
                $body = $template->body;

                foreach ($data as $key => $value) {
                    $search = '{' . $key . '}';
                    $subject = str_replace($search, $value, $subject);
                    $body = str_replace($search, $value, $body);
                }
            } else {
                $subject = $fallbackSubject;
                $body = $fallbackBody;
            }

            self::sendEmail($to, $subject, $body);
        } catch (\Exception $e) {
            Log::warning("Failed to send templated email '{$templateKey}' to '{$to}': " . $e->getMessage());
        }
    }

    /**
     * Send email notification when an invoice is created.
     */
    public static function sendInvoiceCreated(Invoice $invoice): void
    {
        $store = $invoice->store;
        if (!$store) {
            return;
        }

        $merchant = $store->user;
        if (!$merchant || !$merchant->email || !$merchant->notify_invoice_created) {
            return;
        }

        $data = [
            'merchant_name' => $merchant->name,
            'store_name' => $store->name,
            'invoice_id' => $invoice->invoice_id,
            'amount' => number_format($invoice->amount, 2) . ' ' . $invoice->currency,
            'customer_name' => $invoice->customer_name,
            'customer_email' => $invoice->customer_email,
            'expires_at' => $invoice->expires_at->toDateTimeString(),
            'payment_link' => $invoice->payment_link,
        ];

        $fallbackSubject = "New Invoice Created - " . $invoice->invoice_id;
        $fallbackBody = "Hello " . $merchant->name . ",\n\n" .
            "A new invoice has been successfully created in your store '" . $store->name . "'.\n\n" .
            "Invoice details:\n" .
            "- Invoice ID: " . $invoice->invoice_id . "\n" .
            "- Amount: " . number_format($invoice->amount, 2) . " " . $invoice->currency . "\n" .
            "- Customer: " . $invoice->customer_name . " (" . $invoice->customer_email . ")\n" .
            "- Expires At: " . $invoice->expires_at->toDateTimeString() . "\n\n" .
            "Payment Link: " . $invoice->payment_link . "\n\n" .
            "Thank you for using OmniPay.";

        self::sendTemplatedEmail($merchant->email, 'invoice_created', $data, $fallbackSubject, $fallbackBody);
    }

    /**
     * Send email notification when an invoice is paid.
     */
    public static function sendInvoicePaid(Invoice $invoice): void
    {
        $store = $invoice->store;
        if (!$store) {
            return;
        }

        $merchant = $store->user;
        if (!$merchant || !$merchant->email || !$merchant->notify_invoice_paid) {
            return;
        }

        $data = [
            'merchant_name' => $merchant->name,
            'store_name' => $store->name,
            'invoice_id' => $invoice->invoice_id,
            'amount' => number_format($invoice->amount, 2) . ' ' . $invoice->currency,
            'customer_name' => $invoice->customer_name,
            'customer_email' => $invoice->customer_email,
            'paid_at' => ($invoice->paid_at ? $invoice->paid_at->toDateTimeString() : now()->toDateTimeString()),
        ];

        $fallbackSubject = "Invoice Paid - " . $invoice->invoice_id;
        $fallbackBody = "Hello " . $merchant->name . ",\n\n" .
            "An invoice in your store '" . $store->name . "' has been paid successfully.\n\n" .
            "Invoice details:\n" .
            "- Invoice ID: " . $invoice->invoice_id . "\n" .
            "- Amount: " . number_format($invoice->amount, 2) . " " . $invoice->currency . "\n" .
            "- Customer: " . $invoice->customer_name . " (" . $invoice->customer_email . ")\n" .
            "- Paid At: " . ($invoice->paid_at ? $invoice->paid_at->toDateTimeString() : now()->toDateTimeString()) . "\n\n" .
            "Thank you for using OmniPay.";

        self::sendTemplatedEmail($merchant->email, 'invoice_paid', $data, $fallbackSubject, $fallbackBody);
    }

    /**
     * Send email notification when an invoice expires.
     */
    public static function sendInvoiceExpired(Invoice $invoice): void
    {
        $store = $invoice->store;
        if (!$store) {
            return;
        }

        $merchant = $store->user;
        if (!$merchant || !$merchant->email || !$merchant->notify_invoice_expired) {
            return;
        }

        $data = [
            'merchant_name' => $merchant->name,
            'store_name' => $store->name,
            'invoice_id' => $invoice->invoice_id,
            'amount' => number_format($invoice->amount, 2) . ' ' . $invoice->currency,
            'customer_name' => $invoice->customer_name,
            'customer_email' => $invoice->customer_email,
            'expires_at' => $invoice->expires_at->toDateTimeString(),
        ];

        $fallbackSubject = "Invoice Expired - " . $invoice->invoice_id;
        $fallbackBody = "Hello " . $merchant->name . ",\n\n" .
            "An invoice in your store '" . $store->name . "' has expired without being paid.\n\n" .
            "Invoice details:\n" .
            "- Invoice ID: " . $invoice->invoice_id . "\n" .
            "- Amount: " . number_format($invoice->amount, 2) . " " . $invoice->currency . "\n" .
            "- Customer: " . $invoice->customer_name . " (" . $invoice->customer_email . ")\n" .
            "- Expired At: " . $invoice->expires_at->toDateTimeString() . "\n\n" .
            "Thank you for using OmniPay.";

        self::sendTemplatedEmail($merchant->email, 'invoice_expired', $data, $fallbackSubject, $fallbackBody);
    }

    /**
     * Send email notification when a login occurs.
     */
    public static function sendLoginNotification(User $user, string $ipAddress): void
    {
        if (!$user->email || !$user->notify_login) {
            return;
        }

        $data = [
            'merchant_name' => $user->name,
            'ip_address' => $ipAddress,
            'time' => now()->toDateTimeString(),
            'user_agent' => request()->userAgent(),
        ];

        $fallbackSubject = "New Account Login Detected";
        $fallbackBody = "Hello " . $user->name . ",\n\n" .
            "A successful login to your OmniPay merchant account was detected.\n\n" .
            "Login Details:\n" .
            "- IP Address: " . $ipAddress . "\n" .
            "- Time: " . now()->toDateTimeString() . "\n" .
            "- User-Agent: " . request()->userAgent() . "\n\n" .
            "If you did not initiate this login, please change your password immediately.";

        self::sendTemplatedEmail($user->email, 'login_notification', $data, $fallbackSubject, $fallbackBody);
    }

    /**
     * Send email notification with the 2FA OTP code.
     */
    public static function send2faCode(User $user, string $code): void
    {
        if (!$user->email) {
            return;
        }

        $data = [
            'merchant_name' => $user->name,
            'code' => $code,
        ];

        $fallbackSubject = "Your Two-Factor Verification Code";
        $fallbackBody = "Hello " . $user->name . ",\n\n" .
            "Your Two-Factor Authentication (2FA) verification code is: " . $code . "\n\n" .
            "This code will expire in 10 minutes. If you did not attempt to sign in to OmniPay, please secure your credentials.";

        self::sendTemplatedEmail($user->email, '2fa_code', $data, $fallbackSubject, $fallbackBody);
    }

    /**
     * Send email notification for manual MFS verification waiting.
     */
    public static function sendManualVerificationWaiting(Invoice $invoice, string $gateway, string $trx_id, float $totalBDT, float $receivedBDT): void
    {
        $merchant = $invoice->store->user;
        if (!$merchant || !$merchant->email) {
            return;
        }

        $data = [
            'merchant_name' => $merchant->name,
            'gateway' => $gateway,
            'invoice_id' => $invoice->invoice_id,
            'trx_id' => $trx_id,
            'total_bdt' => $totalBDT,
            'received_bdt' => $receivedBDT,
        ];

        $fallbackSubject = "{$gateway} Verification Pending - Invoice #{$invoice->invoice_id}";
        $fallbackBody = "<h3>{$gateway} Verification Waiting</h3>\n" .
            "<p>Hello {$merchant->name},</p>\n" .
            "<p>A customer submitted a {$gateway} Transaction ID for manual verification. It has not yet been synced by the transaction reader app.</p>\n" .
            "<ul>\n" .
            "<li><b>Invoice ID:</b> #{$invoice->invoice_id}</li>\n" .
            "<li><b>Submitted Transaction ID:</b> {$trx_id}</li>\n" .
            "<li><b>Expected Total:</b> {$totalBDT} BDT</li>\n" .
            "<li><b>Received So Far:</b> {$receivedBDT} BDT</li>\n" .
            "</ul>\n" .
            "<p>Please verify this transaction manually in your dashboard once you receive the SMS.</p>";

        self::sendTemplatedEmail($merchant->email, 'manual_verification_waiting', $data, $fallbackSubject, $fallbackBody);
    }

    /**
     * Direct mail delivery wrapper.
     */
    protected static function sendEmail(string $to, string $subject, string $body): void
    {
        try {
            Mail::to($to)->send(new \App\Mail\DynamicNotificationMail($subject, $body));
        } catch (\Exception $e) {
            Log::warning("SMTP notification failed for recipient '{$to}' with error: " . $e->getMessage());
        }
    }
}
