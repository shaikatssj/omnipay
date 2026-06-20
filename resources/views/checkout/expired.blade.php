@extends('checkout.layout')

@section('title', 'Payment Link Expired | OmniPay')

@section('styles')
<style>
    .expired-panel {
        padding: 40px 30px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .expired-icon {
        font-size: 4rem;
        color: var(--danger);
        background: rgba(239, 68, 68, 0.1);
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.15);
    }

    .expired-title {
        font-size: 1.6rem;
        font-weight: 700;
    }

    .expired-desc {
        color: var(--gray);
        font-size: 0.95rem;
        max-width: 400px;
        line-height: 1.6;
    }

    .info-card {
        width: 100%;
        background: rgba(255, 255, 255, 0.4);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 20px;
        margin: 10px 0;
        text-align: left;
    }

    [data-theme="dark"] .info-card {
        background: rgba(0, 0, 0, 0.15);
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        font-size: 0.9rem;
    }
</style>
@endsection

@section('content')
<div class="expired-panel">
    <div class="expired-icon">
        <i class="fa-solid fa-clock-rotate-left"></i>
    </div>
    
    <h2 class="expired-title">Payment Link Expired</h2>
    <p class="expired-desc">This payment checkout link has expired. The 30-minute validation window is closed. Please create a new payment invoice on the merchant's site.</p>
    
    <div class="info-card">
        <div class="info-row">
            <span style="color: var(--gray)">Invoice ID</span>
            <span style="font-weight: 600">{{ $invoice->invoice_id }}</span>
        </div>
        <div class="info-row">
            <span style="color: var(--gray)">Expected Amount</span>
            <span style="font-weight: 600">{{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}</span>
        </div>
    </div>

    @if($invoice->cancel_url)
        <a href="{{ $invoice->cancel_url }}" class="btn btn-secondary" style="margin-top: 15px;">
            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i> Go Back
        </a>
    @endif
</div>
@endsection
