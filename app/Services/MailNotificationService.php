<?php

namespace App\Services;

use App\Models\User;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailNotificationService
{
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

        self::sendEmail(
            $merchant->email,
            "New Invoice Created - " . $invoice->invoice_id,
            "Hello " . $merchant->name . ",\n\n" .
            "A new invoice has been successfully created in your store '" . $store->name . "'.\n\n" .
            "Invoice details:\n" .
            "- Invoice ID: " . $invoice->invoice_id . "\n" .
            "- Amount: " . number_format($invoice->amount, 2) . " " . $invoice->currency . "\n" .
            "- Customer: " . $invoice->customer_name . " (" . $invoice->customer_email . ")\n" .
            "- Expires At: " . $invoice->expires_at->toDateTimeString() . "\n\n" .
            "Payment Link: " . $invoice->payment_link . "\n\n" .
            "Thank you for using OmniPay."
        );
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

        self::sendEmail(
            $merchant->email,
            "Invoice Paid - " . $invoice->invoice_id,
            "Hello " . $merchant->name . ",\n\n" .
            "An invoice in your store '" . $store->name . "' has been paid successfully.\n\n" .
            "Invoice details:\n" .
            "- Invoice ID: " . $invoice->invoice_id . "\n" .
            "- Amount: " . number_format($invoice->amount, 2) . " " . $invoice->currency . "\n" .
            "- Customer: " . $invoice->customer_name . " (" . $invoice->customer_email . ")\n" .
            "- Paid At: " . ($invoice->paid_at ? $invoice->paid_at->toDateTimeString() : now()->toDateTimeString()) . "\n\n" .
            "Thank you for using OmniPay."
        );
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

        self::sendEmail(
            $merchant->email,
            "Invoice Expired - " . $invoice->invoice_id,
            "Hello " . $merchant->name . ",\n\n" .
            "An invoice in your store '" . $store->name . "' has expired without being paid.\n\n" .
            "Invoice details:\n" .
            "- Invoice ID: " . $invoice->invoice_id . "\n" .
            "- Amount: " . number_format($invoice->amount, 2) . " " . $invoice->currency . "\n" .
            "- Customer: " . $invoice->customer_name . " (" . $invoice->customer_email . ")\n" .
            "- Expired At: " . $invoice->expires_at->toDateTimeString() . "\n\n" .
            "Thank you for using OmniPay."
        );
    }

    /**
     * Send email notification when a login occurs.
     */
    public static function sendLoginNotification(User $user, string $ipAddress): void
    {
        if (!$user->email || !$user->notify_login) {
            return;
        }

        self::sendEmail(
            $user->email,
            "New Account Login Detected",
            "Hello " . $user->name . ",\n\n" .
            "A successful login to your OmniPay merchant account was detected.\n\n" .
            "Login Details:\n" .
            "- IP Address: " . $ipAddress . "\n" .
            "- Time: " . now()->toDateTimeString() . "\n" .
            "- User-Agent: " . request()->userAgent() . "\n\n" .
            "If you did not initiate this login, please change your password immediately."
        );
    }

    /**
     * Send email notification with the 2FA OTP code.
     */
    public static function send2faCode(User $user, string $code): void
    {
        if (!$user->email) {
            return;
        }

        self::sendEmail(
            $user->email,
            "Your Two-Factor Verification Code",
            "Hello " . $user->name . ",\n\n" .
            "Your Two-Factor Authentication (2FA) verification code is: " . $code . "\n\n" .
            "This code will expire in 10 minutes. If you did not attempt to sign in to OmniPay, please secure your credentials."
        );
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
