<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'store_id',
        'payment_method_id',
        'invoice_id',
        'customer_name',
        'customer_email',
        'amount',
        'expected_amount',
        'currency',
        'status',
        'payment_link',
        'callback_url',
        'cancel_url',
        'meta_data',
        'expires_at',
        'paid_at',
        'is_sandbox',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'float',
        'expected_amount' => 'float',
        'is_sandbox' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    protected static function booted()
    {
        static::created(function ($invoice) {
            try {
                \App\Services\MailNotificationService::sendInvoiceCreated($invoice);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send created email notification: " . $e->getMessage());
            }
        });

        static::updated(function ($invoice) {
            if ($invoice->isDirty('status')) {
                try {
                    if ($invoice->status === 'paid') {
                        \App\Services\MailNotificationService::sendInvoicePaid($invoice);
                    } elseif ($invoice->status === 'expired') {
                        \App\Services\MailNotificationService::sendInvoiceExpired($invoice);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to send status updated email notification: " . $e->getMessage());
                }

                if ($invoice->callback_url) {
                    try {
                        $payload = [
                            'invoice_id' => $invoice->invoice_id,
                            'amount' => $invoice->amount,
                            'expected_amount' => $invoice->expected_amount,
                            'currency' => $invoice->currency,
                            'status' => $invoice->status,
                            'paid_at' => $invoice->paid_at ? $invoice->paid_at->toDateTimeString() : null,
                            'timestamp' => time(),
                            'meta_data' => $invoice->meta_data,
                        ];

                        $secret = $invoice->store->api_key;
                        $signature = hash_hmac('sha256', json_encode($payload), $secret);

                        \Illuminate\Support\Facades\Http::timeout(5)
                            ->withHeaders(['X-OMNIPAY-SIGNATURE' => $signature])
                            ->post($invoice->callback_url, $payload);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send webhook callback: " . $e->getMessage());
                    }
                }
            }
        });
    }
}
