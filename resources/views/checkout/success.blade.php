@extends('checkout.layout')

@section('title', 'Payment Success | OmniPay')

@section('styles')
<style>
    .success-panel {
        padding: 40px 30px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .success-icon {
        font-size: 4rem;
        color: var(--success);
        background: rgba(16, 185, 129, 0.1);
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
    }

    .success-title {
        font-size: 1.6rem;
        font-weight: 700;
    }

    .success-desc {
        color: var(--gray);
        font-size: 0.95rem;
        max-width: 400px;
        line-height: 1.6;
    }

    .invoice-card {
        width: 100%;
        background: rgba(255, 255, 255, 0.4);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 20px;
        margin: 10px 0;
    }

    [data-theme="dark"] .invoice-card {
        background: rgba(0, 0, 0, 0.15);
    }

    .invoice-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px dashed var(--glass-border);
        font-size: 0.92rem;
    }

    .invoice-row:last-child {
        border-bottom: none;
    }

    .invoice-label {
        color: var(--gray);
    }

    .invoice-value {
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="success-panel">
    <div class="success-icon">
        <i class="fa-solid fa-circle-check"></i>
    </div>
    
    <h2 class="success-title">Payment Confirmed</h2>
    <p class="success-desc">Thank you! Your payment has been processed and verified successfully. The merchant has been notified.</p>
    
    <div class="invoice-card">
        <div class="invoice-row">
            <span class="invoice-label">Invoice ID</span>
            <span class="invoice-value">{{ $invoice->invoice_id }}</span>
        </div>
        <div class="invoice-row">
            <span class="invoice-label">Paid Amount</span>
            <span class="invoice-value">{{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}</span>
        </div>
        <div class="invoice-row">
            <span class="invoice-label">Store</span>
            <span class="invoice-value">{{ $invoice->store->name }}</span>
        </div>
        <div class="invoice-row">
            <span class="invoice-label">Status</span>
            <span class="invoice-value badge badge-success">PAID</span>
        </div>
    </div>

    @php
        // WHMCS and other systems use cancel_url/return_url for the browser return, 
        // and callback_url for the background server-to-server POST webhook.
        $returnUrl = $invoice->cancel_url;
        
        // Fallback checks
        if (!$returnUrl && $invoice->callback_url) {
            // If only callback_url is available and it's not a webhook, use it
            if (!str_contains($invoice->callback_url, '/callback') && !str_contains($invoice->callback_url, 'callback.php')) {
                $returnUrl = $invoice->callback_url;
            } else {
                // Parse base domain from callback_url to return the customer home
                $parsed = parse_url($invoice->callback_url);
                $returnUrl = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'localhost') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
            }
        }
    @endphp

    @if($returnUrl)
        <p class="redirect-text" style="font-size: 0.85rem; color: var(--gray); margin-top: 10px;">
            Redirecting you back to the merchant website in <span id="countdown" style="font-weight: bold; color: var(--primary);">5</span> seconds...
        </p>
        <a href="{{ $returnUrl }}" class="btn btn-primary" style="margin-top: 10px;">
            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i> Return to Website
        </a>

        <script>
            let count = 5;
            const countdownEl = document.getElementById('countdown');
            const interval = setInterval(() => {
                count--;
                if (countdownEl) countdownEl.textContent = count;
                if (count <= 0) {
                    clearInterval(interval);
                    window.location.href = "{{ $returnUrl }}";
                }
            }, 1000);
        </script>
    @endif
</div>
@endsection
