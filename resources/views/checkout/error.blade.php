@extends('checkout.layout')

@section('title', 'Error | OmniPay')

@section('styles')
<style>
    .error-panel {
        padding: 40px 30px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .error-icon {
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

    .error-title {
        font-size: 1.6rem;
        font-weight: 700;
    }

    .error-desc {
        color: var(--gray);
        font-size: 0.95rem;
        max-width: 400px;
        line-height: 1.6;
    }
</style>
@endsection

@section('content')
<div class="error-panel">
    <div class="error-icon">
        <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    
    <h2 class="error-title">An Error Occurred</h2>
    <p class="error-desc">{{ $message ?? 'The request could not be processed. Please verify your payment link.' }}</p>
    
    <a href="/" class="btn btn-secondary" style="margin-top: 15px;">
        Return Home
    </a>
</div>
@endsection
