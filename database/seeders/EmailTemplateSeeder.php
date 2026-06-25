<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'key' => 'invoice_created',
                'name' => 'Invoice Created',
                'subject' => 'New Invoice Created - {invoice_id}',
                'body' => "Hello {merchant_name},\n\nA new invoice has been successfully created in your store '{store_name}'.\n\nInvoice details:\n- Invoice ID: {invoice_id}\n- Amount: {amount}\n- Customer: {customer_name} ({customer_email})\n- Expires At: {expires_at}\n\nPayment Link: {payment_link}\n\nThank you for using OmniPay.",
                'variables' => ['merchant_name', 'store_name', 'invoice_id', 'amount', 'customer_name', 'customer_email', 'expires_at', 'payment_link']
            ],
            [
                'key' => 'invoice_paid',
                'name' => 'Invoice Paid',
                'subject' => 'Invoice Paid - {invoice_id}',
                'body' => "Hello {merchant_name},\n\nAn invoice in your store '{store_name}' has been paid successfully.\n\nInvoice details:\n- Invoice ID: {invoice_id}\n- Amount: {amount}\n- Customer: {customer_name} ({customer_email})\n- Paid At: {paid_at}\n\nThank you for using OmniPay.",
                'variables' => ['merchant_name', 'store_name', 'invoice_id', 'amount', 'customer_name', 'customer_email', 'paid_at']
            ],
            [
                'key' => 'invoice_expired',
                'name' => 'Invoice Expired',
                'subject' => 'Invoice Expired - {invoice_id}',
                'body' => "Hello {merchant_name},\n\nAn invoice in your store '{store_name}' has expired without being paid.\n\nInvoice details:\n- Invoice ID: {invoice_id}\n- Amount: {amount}\n- Customer: {customer_name} ({customer_email})\n- Expired At: {expires_at}\n\nThank you for using OmniPay.",
                'variables' => ['merchant_name', 'store_name', 'invoice_id', 'amount', 'customer_name', 'customer_email', 'expires_at']
            ],
            [
                'key' => 'login_notification',
                'name' => 'Login Notification',
                'subject' => 'New Account Login Detected',
                'body' => "Hello {merchant_name},\n\nA successful login to your OmniPay merchant account was detected.\n\nLogin Details:\n- IP Address: {ip_address}\n- Time: {time}\n- User-Agent: {user_agent}\n\nIf you did not initiate this login, please change your password immediately.",
                'variables' => ['merchant_name', 'ip_address', 'time', 'user_agent']
            ],
            [
                'key' => '2fa_code',
                'name' => '2FA Verification Code',
                'subject' => 'Your Two-Factor Verification Code',
                'body' => "Hello {merchant_name},\n\nYour Two-Factor Authentication (2FA) verification code is: {code}\n\nThis code will expire in 10 minutes. If you did not attempt to sign in to OmniPay, please secure your credentials.",
                'variables' => ['merchant_name', 'code']
            ],
            [
                'key' => 'manual_verification_waiting',
                'name' => 'Manual Verification Waiting (MFS)',
                'subject' => '{gateway} Verification Pending - Invoice #{invoice_id}',
                'body' => "<h3>{gateway} Verification Waiting</h3>\n<p>Hello {merchant_name},</p>\n<p>A customer submitted a {gateway} Transaction ID for manual verification. It has not yet been synced by the transaction reader app.</p>\n<ul>\n    <li><b>Invoice ID:</b> #{invoice_id}</li>\n    <li><b>Submitted Transaction ID:</b> {trx_id}</li>\n    <li><b>Expected Total:</b> {total_bdt} BDT</li>\n    <li><b>Received So Far:</b> {received_bdt} BDT</li>\n</ul>\n<p>Please verify this transaction manually in your dashboard once you receive the SMS.</p>",
                'variables' => ['merchant_name', 'gateway', 'invoice_id', 'trx_id', 'total_bdt', 'received_bdt']
            ],
        ];

        foreach ($templates as $t) {
            EmailTemplate::updateOrCreate(['key' => $t['key']], $t);
        }
    }
}
