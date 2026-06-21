@extends('checkout.layout')

@section('title', 'Pay Invoice ' . $invoice->invoice_id . ' | OmniPay')

@section('styles')
<style>
    .checkout-grid {
        display: grid;
        grid-template-columns: 1fr;
        min-height: 550px;
    }
    
    @media (min-width: 768px) {
        .checkout-grid {
            grid-template-columns: 310px 1fr;
        }
    }
    
    /* Left Sidebar */
    .checkout-summary-sidebar {
        background: linear-gradient(180deg, rgba(99, 102, 241, 0.03) 0%, rgba(99, 102, 241, 0.005) 100%);
        border-bottom: 1px solid var(--glass-border);
        padding: 35px 28px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 35px;
    }
    
    @media (min-width: 768px) {
        .checkout-summary-sidebar {
            border-bottom: none;
            border-right: 1px solid var(--glass-border);
        }
    }
    
    .sidebar-top {
        display: flex;
        flex-direction: column;
        gap: 28px;
    }
    
    .sidebar-store-title {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 1.8px;
        font-weight: 700;
        color: var(--gray);
        margin-bottom: 6px;
        display: block;
    }
    
    .sidebar-store-name {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--dark);
        line-height: 1.25;
        letter-spacing: -0.3px;
    }
    
    .sidebar-invoice-badge {
        font-size: 0.8rem;
        color: var(--gray);
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .sidebar-invoice-badge code {
        background: rgba(99, 102, 241, 0.08);
        padding: 3px 8px;
        border-radius: 6px;
        border: 1px solid rgba(99, 102, 241, 0.15);
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        color: var(--primary);
        font-size: 0.82rem;
        font-weight: 600;
    }
    
    .sidebar-amount-section {
        background: rgba(99, 102, 241, 0.03);
        border: 1px solid rgba(99, 102, 241, 0.08);
        padding: 20px;
        border-radius: 14px;
        margin-top: 5px;
    }
    
    .sidebar-amount-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--gray);
        font-weight: 600;
        margin-bottom: 6px;
        display: block;
    }
    
    .sidebar-amount-value {
        font-size: 2.1rem;
        font-weight: 800;
        color: var(--primary);
        letter-spacing: -0.8px;
    }
    
    .sidebar-customer-details {
        border-top: 1px dashed var(--glass-border);
        padding-top: 25px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .customer-detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .customer-detail-label {
        font-size: 0.72rem;
        color: var(--gray);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .customer-detail-label i {
        font-size: 0.75rem;
        color: var(--primary);
        opacity: 0.7;
    }
    
    .customer-detail-value {
        font-size: 0.9rem;
        color: var(--dark);
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .sidebar-timer {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.04) 100%);
        border: 1px solid rgba(239, 68, 68, 0.15);
        padding: 10px 18px;
        border-radius: 25px;
        color: var(--danger);
        font-weight: 700;
        font-size: 0.92rem;
        width: fit-content;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.03);
        animation: pulseTimer 2.5s infinite ease-in-out;
    }

    @keyframes pulseTimer {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.25); }
        70% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    
    /* Right Side Main Content */
    .checkout-main-content {
        padding: 40px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 500px;
    }

    .gateway-selection-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .gateway-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 16px;
        margin-bottom: 25px;
    }

    .gateway-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 14px;
        padding: 24px 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 14px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01);
    }

    .gateway-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.06) 0%, rgba(99, 102, 241, 0) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 0;
    }

    .gateway-card:hover::before {
        opacity: 1;
    }

    .gateway-card:hover {
        transform: translateY(-4px);
        border-color: var(--primary);
        box-shadow: 0 10px 20px rgba(99, 102, 241, 0.08);
    }

    .gateway-card.active {
        background: rgba(99, 102, 241, 0.06);
        border-color: var(--primary);
        border-width: 2px;
        padding: 23px 11px; /* visual alignment offset for 2px border */
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15);
    }

    .gateway-card.active::after {
        content: '\f058';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: 8px;
        right: 8px;
        color: var(--primary);
        font-size: 0.85rem;
    }

    [data-theme="dark"] .gateway-card.active {
        background: rgba(129, 140, 248, 0.1);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 8px 25px rgba(129, 140, 248, 0.2);
    }

    .gateway-icon {
        font-size: 2.1rem;
        color: var(--primary);
        position: relative;
        z-index: 1;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .gateway-card:hover .gateway-icon {
        transform: scale(1.1);
    }

    .gateway-name {
        font-size: 0.88rem;
        font-weight: 600;
        position: relative;
        z-index: 1;
        color: var(--dark);
    }

    /* Checkout Details Container */
    .checkout-details {
        display: none;
        animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .back-btn-container {
        margin-bottom: 25px;
        border-bottom: 1px dashed var(--glass-border);
        padding-bottom: 20px;
    }

    .back-btn {
        width: auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        font-size: 0.85rem;
        border-radius: 10px;
        border: 1px solid var(--glass-border);
        background: rgba(255, 255, 255, 0.4);
        font-weight: 700;
        color: var(--dark);
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    [data-theme="dark"] .back-btn {
        background: rgba(15, 23, 42, 0.3);
    }

    .back-btn:hover {
        transform: translateX(-4px);
        border-color: var(--primary);
        background: rgba(99, 102, 241, 0.05);
        color: var(--primary);
    }

    .details-title {
        font-size: 1.2rem;
        font-weight: 800;
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--dark);
    }

    .form-group {
        margin-bottom: 22px;
    }

    .form-group label {
        display: block;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--gray);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .form-control {
        width: 100%;
        padding: 15px 18px;
        padding-right: 52px;
        border-radius: 12px;
        border: 1px solid var(--glass-border);
        background: rgba(255, 255, 255, 0.5);
        color: var(--dark);
        font-family: inherit;
        font-size: 0.98rem;
        outline: none;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.01);
    }

    [data-theme="dark"] .form-control {
        background: rgba(15, 23, 42, 0.35);
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        background: rgba(255, 255, 255, 0.85);
    }

    [data-theme="dark"] .form-control:focus {
        background: rgba(15, 23, 42, 0.5);
    }

    .copy-btn {
        position: absolute;
        right: 8px;
        background: rgba(99, 102, 241, 0.06);
        border: 1px solid rgba(99, 102, 241, 0.1);
        border-radius: 8px;
        cursor: pointer;
        color: var(--primary);
        font-size: 1rem;
        padding: 8px 12px;
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .copy-btn:hover {
        background: var(--primary);
        color: white;
        transform: scale(1.05);
    }

    .instructions-box {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.04) 0%, rgba(99, 102, 241, 0.01) 100%);
        border: 1px solid rgba(99, 102, 241, 0.1);
        border-left: 4px solid var(--primary);
        padding: 18px 22px;
        border-radius: 12px;
        font-size: 0.92rem;
        line-height: 1.6;
        margin-bottom: 25px;
        color: var(--dark);
    }

    .network-select-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 10px;
        margin-bottom: 22px;
    }

    .network-btn {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 10px;
        padding: 11px 8px;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        text-align: center;
        color: var(--dark);
        box-shadow: 0 2px 4px rgba(0,0,0,0.01);
    }

    .network-btn:hover {
        transform: translateY(-2px);
        border-color: var(--primary);
        box-shadow: 0 4px 8px rgba(99, 102, 241, 0.06);
    }

    .network-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    }

    .status-alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-radius: 12px;
        font-size: 0.92rem;
        font-weight: 600;
        margin-top: 22px;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    /* QR Code Redesign */
    .qr-wrapper {
        text-align: center;
        margin-bottom: 25px;
    }

    .qr-frame {
        background: white;
        padding: 15px;
        border-radius: 16px;
        display: inline-block;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.05);
        position: relative;
    }

    .qr-frame img {
        max-width: 170px;
        height: auto;
        display: block;
        border-radius: 8px;
    }

    .qr-text {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--gray);
        margin-top: 10px;
        margin-bottom: 0;
    }

    /* Binance Note Container */
    .binance-note-container {
        background: rgba(240, 185, 11, 0.04);
        border: 1px solid rgba(240, 185, 11, 0.15);
        padding: 20px;
        border-radius: 14px;
        text-align: center;
    }

    .binance-note-text {
        font-size: 0.82rem;
        font-weight: 500;
        color: var(--gray);
        margin-bottom: 0;
        margin-top: 12px;
        line-height: 1.4;
    }

    .binance-note-text strong {
        color: #d97706;
    }

    /* Buttons inside Active Panel */
    #verifyPaymentBtn {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        border: none;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    #verifyPaymentBtn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
    }

    #sandboxPanelSimulateBtn {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border: none;
        font-weight: 700;
        white-space: nowrap;
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.25);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    #sandboxPanelSimulateBtn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(245, 158, 11, 0.35);
    }

    /* Sandbox Banner */
    .sandbox-banner {
        background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%);
        color: white;
        padding: 16px 24px;
        font-weight: 600;
        border-radius: 16px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 10px 25px -5px rgba(217, 119, 6, 0.2);
        flex-wrap: wrap;
        gap: 15px;
        border: 1px solid rgba(251, 191, 36, 0.2);
    }

    .sandbox-banner-btn {
        background: white;
        color: #d97706;
        padding: 10px 20px;
        font-size: 0.85rem;
        border-radius: 10px;
        border: none;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        width: auto;
    }

    .sandbox-banner-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.08);
        background: #fffbeb;
    }

    /* Modal Styling */
    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-box {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        border-radius: 24px;
        width: 90%;
        max-width: 480px;
        padding: 35px;
        animation: modalFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.96) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .modal-header {
        font-size: 1.25rem;
        font-weight: 800;
        margin-bottom: 18px;
        color: var(--dark);
        display: flex;
        align-items: center;
    }

    .modal-body {
        margin-bottom: 25px;
        font-size: 0.95rem;
        line-height: 1.65;
        color: var(--dark);
    }

    .trx-select-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 18px;
    }

    .trx-option-btn {
        background: rgba(255, 255, 255, 0.6);
        border: 1px solid var(--glass-border);
        padding: 15px;
        border-radius: 12px;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 1.05rem;
        cursor: pointer;
        text-align: center;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        font-weight: 700;
        color: var(--dark);
        box-shadow: 0 2px 4px rgba(0,0,0,0.01);
    }

    [data-theme="dark"] .trx-option-btn {
        background: rgba(15, 23, 42, 0.35);
    }

    .trx-option-btn:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(99, 102, 241, 0.2);
    }

    .footer {
        padding: 25px 0 0 0;
        border-top: 1px solid var(--glass-border);
        text-align: center;
        font-size: 0.78rem;
        color: var(--gray);
        margin-top: auto;
        font-weight: 500;
    }
</style>
@endsection

@section('content')
@if($invoice->is_sandbox)
    <div class="sandbox-banner">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.2rem;"></i>
            <span>SANDBOX/TEST INVOICE: No real funds will be charged. Use the simulation button to test the webhook and checkout success.</span>
        </div>
        <button type="button" id="sandboxSimulateBtn" class="sandbox-banner-btn">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Simulate Success
        </button>
    </div>
@endif

<div class="checkout-grid">
    <!-- Left Column: Summary -->
    <div class="checkout-summary-sidebar">
        <div class="sidebar-top">
            <div>
                <span class="sidebar-store-title">Payment Request</span>
                <h2 class="sidebar-store-name">{{ $invoice->store->name }}</h2>
                <div class="sidebar-invoice-badge">Invoice: <code>{{ $invoice->invoice_id }}</code></div>
            </div>
            
            <div class="sidebar-amount-section">
                <span class="sidebar-amount-label">Amount to Pay</span>
                <span class="sidebar-amount-value" id="amountDisplayVal">{{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}</span>
            </div>
            
            <div class="sidebar-customer-details">
                <div class="customer-detail-item">
                    <span class="customer-detail-label"><i class="fa-solid fa-user"></i> Customer</span>
                    <span class="customer-detail-value">{{ $invoice->customer_name }}</span>
                </div>
                <div class="customer-detail-item">
                    <span class="customer-detail-label"><i class="fa-solid fa-envelope"></i> Email</span>
                    <span class="customer-detail-value" title="{{ $invoice->customer_email }}">{{ $invoice->customer_email }}</span>
                </div>
            </div>
        </div>
        
        <div class="sidebar-bottom">
            <div class="sidebar-timer" id="countdownTimer">
                <i class="fa-solid fa-clock" style="animation: pulse 2s infinite;"></i>
                <span id="timeLeft">00:00</span>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Payment Form/Gateways -->
    <div class="checkout-main-content">
        <div class="gateway-selection">
            <h3 class="gateway-selection-title">
                <i class="fa-solid fa-credit-card" style="color: var(--primary);"></i> Select Payment Method
            </h3>
            <div class="gateway-grid">
                @foreach($configs as $cfg)
                    <div class="gateway-card" data-code="{{ $cfg->paymentMethod->code }}">
                        <div class="gateway-icon" style="display: flex; align-items: center; justify-content: center; height: 35px; width: 100%;">
                            @if(!empty($cfg->settings['logo']))
                                <img src="{{ asset($cfg->settings['logo']) }}" style="max-height: 35px; max-width: 100%; object-fit: contain; border-radius: 4px;" alt="{{ $cfg->paymentMethod->name }}">
                            @elseif($cfg->paymentMethod->logo)
                                <img src="{{ asset($cfg->paymentMethod->logo) }}" style="max-height: 35px; max-width: 100%; object-fit: contain; border-radius: 4px;" alt="{{ $cfg->paymentMethod->name }}">
                            @else
                                @if($cfg->paymentMethod->code === 'bkash')
                                    <i class="fa-solid fa-mobile-screen-button" style="color: #d12053;"></i>
                                @elseif($cfg->paymentMethod->code === 'nagad')
                                    <i class="fa-solid fa-mobile-screen-button" style="color: #f35922;"></i>
                                @elseif($cfg->paymentMethod->code === 'upay')
                                    <i class="fa-solid fa-mobile-screen-button" style="color: #e5a900;"></i>
                                @elseif($cfg->paymentMethod->code === 'rocket')
                                    <i class="fa-solid fa-mobile-screen-button" style="color: #8c2e8a;"></i>
                                @elseif($cfg->paymentMethod->code === 'cellfin')
                                    <i class="fa-solid fa-mobile-screen-button" style="color: #0c723a;"></i>
                                @elseif($cfg->paymentMethod->code === 'okwallet')
                                    <i class="fa-solid fa-mobile-screen-button" style="color: #0f75bc;"></i>
                                @elseif($cfg->paymentMethod->code === 'tap')
                                    <i class="fa-solid fa-mobile-screen-button" style="color: #e11d48;"></i>
                                @elseif($cfg->paymentMethod->code === 'binance')
                                    <i class="fa-solid fa-coins" style="color: #f0b90b;"></i>
                                @elseif($cfg->paymentMethod->code === 'bybit')
                                    <i class="fa-solid fa-coins" style="color: #16b97d;"></i>
                                @elseif($cfg->paymentMethod->code === 'web3')
                                    <i class="fa-solid fa-wallet" style="color: #6366f1;"></i>
                                @endif
                            @endif
                        </div>
                        <div class="gateway-name">{{ $cfg->paymentMethod->name }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Active Gateway Panel -->
        <div class="checkout-details" id="paymentPanel">
            <div class="back-btn-container">
                <button type="button" id="backToSelectionBtn" class="back-btn">
                    <i class="fa-solid fa-arrow-left"></i> Back to Payment Methods
                </button>
            </div>
            
            <div class="details-title" id="panelTitle">
                <!-- Dynamic Icon & Title -->
            </div>

            <div class="instructions-box" id="panelInstructions">
                <!-- Dynamic Instructions -->
            </div>

            <!-- Dynamic QR Code Display -->
            <div id="qrCodeWrapper" class="qr-wrapper" style="display: none;">
                <div class="qr-frame">
                    <img id="checkoutQrCode" src="" alt="Scan QR Code">
                </div>
                <p class="qr-text">Scan QR Code to Pay</p>
            </div>

            <!-- Binance Note Group -->
            <div class="form-group" id="binanceNoteGroup" style="display: none; margin-bottom: 20px;">
                <label>Required Payment Note</label>
                <div class="binance-note-container">
                    <div style="display: flex; gap: 8px; justify-content: center; margin-bottom: 10px;" id="binanceNoteDigits">
                        <!-- Note digits will be added here -->
                    </div>
                    <p class="binance-note-text">You <strong>must</strong> include this exact 4-digit note code in your Binance transfer note/ref field.</p>
                </div>
            </div>

            <!-- Dynamic Form Fields will be shown here based on method selection -->
            
            <!-- Web3 Specific Chain Selection -->
            <div id="web3ChainSelector" style="display: none; margin-bottom: 20px;">
                <label>Select Network</label>
                <div class="network-select-grid">
                    <button type="button" class="network-btn active" data-net="bsc">BSC (USDT)</button>
                    <button type="button" class="network-btn" data-net="eth">Ethereum</button>
                    <button type="button" class="network-btn" data-net="tron">Tron (TRC20)</button>
                    <button type="button" class="network-btn" data-net="arb">Arbitrum</button>
                    <button type="button" class="network-btn" data-net="opmain">Optimism</button>
                </div>
            </div>

            <!-- Configured Wallets / Accounts -->
            <div class="form-group" id="accountAddressGroup" style="display: none;">
                <label id="accountAddressLabel">Recipient Address</label>
                <div class="input-wrapper">
                    <input type="text" class="form-control" id="recipientAccount" readonly>
                    <button type="button" class="copy-btn" onclick="copyText('recipientAccount')">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>
            </div>

            <!-- MFS Transaction ID Input Form -->
            <div class="form-group" id="trxInputGroup" style="display: none;">
                <label for="trxIdInput" id="trxIdLabel">Enter Transaction ID</label>
                <input type="text" class="form-control" id="trxIdInput" placeholder="Enter Transaction ID (e.g. 9J87X65Y4)">
            </div>

            <div class="form-group" id="verifyBtnGroup" style="display: flex; gap: 10px;">
                <button class="btn btn-primary" id="verifyPaymentBtn" style="flex-grow: 1;">
                    <i class="fa-solid fa-shield-check" style="margin-right: 8px;"></i> Verify Payment
                </button>
                @if($invoice->is_sandbox)
                    <button type="button" class="btn" id="sandboxPanelSimulateBtn">
                        <i class="fa-solid fa-wand-magic-sparkles" style="margin-right: 8px;"></i> Simulate Payment
                    </button>
                @endif
            </div>

            <!-- Polling Loader Indicators -->
            <div class="status-alert badge-pending" id="pollingStatusBox" style="display: none; justify-content: center; width: 100%;">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
                <span id="pollingStatusText">Checking payment status...</span>
            </div>
        </div>
        
        @if(!isset($invoice->store) || !$invoice->store->hide_branding)
        <div class="footer">
            <i class="fa-solid fa-shield-lock" style="margin-right: 5px;"></i> Secured by OmniPay Gateway System
        </div>
        @endif
    </div>
</div>

<!-- MFS Auto Matching Modal -->
<div class="modal-backdrop" id="autoMatchModal">
    <div class="modal-box">
        <div class="modal-header">
            <i class="fa-solid fa-circle-check" style="color: var(--success); margin-right: 8px;"></i> Transaction Found!
        </div>
        <div class="modal-body">
            We detected a transaction matching your remaining BDT amount. Please select your exact Transaction ID to complete checkout:
            <div class="trx-select-list" id="trxOptionsContainer">
                <!-- Shuffled choices generated on verification -->
            </div>
        </div>
        <button class="btn btn-secondary" onclick="closeModal()">Close / Enter Manually</button>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let timeLeft = {{ $timeLeft }};
    let activeMethod = null;
    let activeNetwork = 'bsc';
    let pollInterval = null;
    let mfsAutoPollInterval = null;

    // 1. Countdown Timer loop
    const countdown = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(countdown);
            window.location.reload();
            return;
        }
        timeLeft--;
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        document.getElementById('timeLeft').textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);

    // 2. Clipboard copy Helper
    function copyText(id) {
        const input = document.getElementById(id);
        input.select();
        document.execCommand('copy');
        
        // Show temp success visual
        const btn = input.nextElementSibling;
        const icon = btn.querySelector('i');
        icon.className = 'fa-solid fa-check';
        icon.style.color = 'var(--success)';
        setTimeout(() => {
            icon.className = 'fa-solid fa-copy';
            icon.style.color = '';
        }, 1500);
    }

    // 3. Handle Method Card selection
    $('.gateway-card').on('click', function() {
        $('.gateway-card').removeClass('active');
        $(this).addClass('active');
        
        const code = $(this).data('code');
        activeMethod = code;
        
        // Clear old loops
        if (pollInterval) clearInterval(pollInterval);
        if (mfsAutoPollInterval) clearInterval(mfsAutoPollInterval);

        // Fetch method initiation details
        $.post('{{ route('checkout.select', ['invoice' => $invoice->invoice_id]) }}', {
            _token: '{{ csrf_token() }}',
            method_code: code
        }, function(res) {
            if (res.success) {
                renderGatewayForm(code, res.init_data);
            }
        });
    });

    // Handle Back to selections
    $('#backToSelectionBtn').on('click', function() {
        // Clear old loops
        if (pollInterval) clearInterval(pollInterval);
        if (mfsAutoPollInterval) clearInterval(mfsAutoPollInterval);
        
        $('#paymentPanel').hide();
        $('.gateway-selection').show();
        $('.gateway-card').removeClass('active');
        
        // Reset amount display back to original currency / amount
        $('#amountDisplayVal').text('{{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}');
    });

    // 4. Render panels dynamically
    function renderGatewayForm(code, data) {
        const panel = $('#paymentPanel');
        const title = $('#panelTitle');
        const instructions = $('#panelInstructions');
        
        // Reset panels
        $('#accountAddressGroup').hide();
        $('#trxInputGroup').hide();
        $('#web3ChainSelector').hide();
        $('#pollingStatusBox').hide();
        $('#qrCodeWrapper').hide();
        $('#binanceNoteGroup').hide();
        $('#verifyBtnGroup').show();
        
        panel.show();
        $('.gateway-selection').hide(); // Hide selection grid

        // Render QR Code if present
        if (data.qr_code) {
            $('#checkoutQrCode').attr('src', data.qr_code);
            $('#qrCodeWrapper').show();
        }

        // Render title with logo or fallback icon
        if (data.gateway_logo) {
            title.html(`<img src="${data.gateway_logo}" style="width: 28px; height: 28px; object-fit: contain; border-radius: 6px; margin-right: 8px; vertical-align: middle;" alt="${data.gateway_name}"> <span style="vertical-align: middle;">Pay with ${data.gateway_name}</span>`);
        } else {
            if (['bkash', 'nagad', 'upay', 'rocket', 'cellfin', 'okwallet', 'tap'].includes(code)) {
                let color = '#6366f1';
                if (code === 'bkash') color = '#d12053';
                else if (code === 'nagad') color = '#f35922';
                else if (code === 'upay') color = '#e5a900';
                else if (code === 'rocket') color = '#8c2e8a';
                else if (code === 'cellfin') color = '#0c723a';
                else if (code === 'okwallet') color = '#0f75bc';
                else if (code === 'tap') color = '#e11d48';
                title.html(`<i class="fa-solid fa-mobile-screen-button" style="color: ${color}"></i> <span>Pay with ${code.toUpperCase()}</span>`);
            } else if (['binance', 'bybit'].includes(code)) {
                let color = code === 'binance' ? '#f0b90b' : '#16b97d';
                title.html(`<i class="fa-solid fa-coins" style="color: ${color}"></i> <span>Pay with ${code.toUpperCase()}</span>`);
            } else if (code === 'web3') {
                title.html(`<i class="fa-solid fa-wallet" style="color: #6366f1"></i> <span>Web3 Crypto Checkout</span>`);
            }
        }

        if (['bkash', 'nagad', 'upay', 'rocket', 'cellfin', 'okwallet', 'tap'].includes(code)) {
            // MFS Flow
            instructions.html(`Please Send exactly <strong>${data.remaining_bdt} BDT</strong> to the personal number: <strong>${data.phone}</strong>.<br><br>${data.instructions}`);
            
            // Adjust invoice amount display value to BDT
            $('#amountDisplayVal').text(`${data.remaining_bdt} BDT`);

            // Dynamically set TrxID labels based on selected gateway
            let trxLabel = 'Enter Transaction ID';
            let trxPlaceholder = 'Enter Transaction ID (e.g. 9J87X65Y4)';
            
            if (code === 'bkash') {
                trxLabel = 'Enter bKash Transaction ID (TrxID)';
                trxPlaceholder = 'e.g. 9J87X65Y4';
            } else if (code === 'nagad') {
                trxLabel = 'Enter Nagad Transaction ID (TxnID)';
                trxPlaceholder = 'e.g. A1B2C3D4E5';
            } else if (code === 'rocket') {
                trxLabel = 'Enter Rocket Transaction ID (TxnId)';
                trxPlaceholder = 'e.g. 1234567890';
            } else if (code === 'upay') {
                trxLabel = 'Enter Upay Transaction ID (TrxID)';
                trxPlaceholder = 'e.g. 9J87X65Y4';
            } else if (code === 'tap') {
                trxLabel = 'Enter Tap Transaction ID (TxID)';
                trxPlaceholder = 'e.g. TAP12345';
            } else if (code === 'cellfin') {
                trxLabel = 'Enter Cellfin Transaction ID (TrxId)';
                trxPlaceholder = 'e.g. CF123456';
            } else if (code === 'okwallet') {
                trxLabel = 'Enter OK Wallet Transaction ID (TrxID)';
                trxPlaceholder = 'e.g. OK123456';
            }
            
            $('#trxIdLabel').text(trxLabel);
            $('#trxIdInput').attr('placeholder', trxPlaceholder);

            $('#trxInputGroup').show();
            
            // Start MFS automatic SMS transaction detection loop
            startMfsPolling();

        } else if (['binance', 'bybit'].includes(code)) {
            // CEX note system balance delta flow
            instructions.html(data.instructions);
            
            $('#amountDisplayVal').text(`${data.amount} USDT`);

            if (code === 'binance' && data.payment_note) {
                $('#binanceNoteGroup').show();
                const digitsContainer = $('#binanceNoteDigits');
                digitsContainer.empty();
                for (let i = 0; i < data.payment_note.length; i++) {
                    digitsContainer.append(`
                        <div style="width: 38px; height: 44px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f0b90b 0%, #fde047 100%); color: #0f172a; font-weight: 800; font-size: 1.25rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(240,185,11,0.15);">${data.payment_note[i]}</div>
                    `);
                }
            }
            
            // We just trigger automatic checking loop
            startActivePolling();

        } else if (code === 'web3') {
            // Blockchain transaction explorer scanning
            instructions.html(data.instructions);
            
            $('#amountDisplayVal').text(`${data.amount} USDT`);
            $('#web3ChainSelector').show();
            $('#accountAddressGroup').show();
            $('#accountAddressLabel').text('Merchant Recipient Address');
            
            // Set default wallet details
            setWeb3Wallet(data.wallets);
            
            // Hook network change buttons
            $('.network-btn').off('click').on('click', function() {
                $('.network-btn').removeClass('active');
                $(this).addClass('active');
                activeNetwork = $(this).data('net');
                setWeb3Wallet(data.wallets);
            });
        }
    }

    function setWeb3Wallet(wallets) {
        const addr = wallets[activeNetwork] || 'Not Configured';
        $('#recipientAccount').val(addr);
    }

    // 5. Polling checks for Binance/Bybit
    function startActivePolling() {
        $('#pollingStatusBox').show();
        $('#pollingStatusText').text('Awaiting payment detection...');
        $('#verifyBtnGroup').hide();

        pollInterval = setInterval(() => {
            $.post('{{ route('checkout.status', ['invoice' => $invoice->invoice_id]) }}', {
                _token: '{{ csrf_token() }}'
            }, function(res) {
                if (res.status === 'success') {
                    clearInterval(pollInterval);
                    window.location.href = res.redirect;
                } else if (res.status === 'expired') {
                    clearInterval(pollInterval);
                    window.location.reload();
                }
            });
        }, 5000);
    }

    // 6. Polling loop to find matching SMS
    function startMfsPolling() {
        mfsAutoPollInterval = setInterval(() => {
            $.post('{{ route('checkout.status', ['invoice' => $invoice->invoice_id]) }}', {
                _token: '{{ csrf_token() }}',
                poll: true
            }, function(res) {
                if (res.status === 'found') {
                    clearInterval(mfsAutoPollInterval);
                    showTrxChoices(res.transactions);
                }
            });
        }, 5000);
    }

    // Show Auto Match Choices
    function showTrxChoices(transactions) {
        const container = $('#trxOptionsContainer');
        container.empty();
        
        transactions.forEach(trx => {
            container.append(`<button type="button" class="trx-option-btn" onclick="submitTrxId('${trx}')">${trx}</button>`);
        });

        $('#autoMatchModal').css('display', 'flex');
    }

    function closeModal() {
        $('#autoMatchModal').hide();
        // restart polling in case they closed
        startMfsPolling();
    }

    // Manual Submit verification
    $('#verifyPaymentBtn').on('click', function() {
        const trxId = $('#trxIdInput').val();
        if (activeMethod === 'web3') {
            // Trigger EVM/TRON scan verification
            triggerManualVerification(null);
        } else if (['bkash', 'nagad', 'upay', 'rocket', 'cellfin', 'okwallet', 'tap'].includes(activeMethod)) {
            if (!trxId) {
                alert('Please input your Transaction ID first.');
                return;
            }
            submitTrxId(trxId);
        }
    });

    function submitTrxId(trxId) {
        $('#autoMatchModal').hide();
        triggerManualVerification(trxId);
    }

    function triggerManualVerification(trxId) {
        $('#pollingStatusBox').show();
        $('#pollingStatusText').text('Verifying transaction...');
        
        $.post('{{ route('checkout.status', ['invoice' => $invoice->invoice_id]) }}', {
            _token: '{{ csrf_token() }}',
            trx_id: trxId,
            network: activeNetwork
        }, function(res) {
            $('#pollingStatusBox').hide();
            
            if (res.status === 'success') {
                window.location.href = res.redirect;
            } else if (res.status === 'partial') {
                alert(res.message);
                window.location.reload();
            } else {
                alert(res.message || 'Verification failed. Please check parameters and try again.');
            }
        });
    }

    // Sandbox Simulation Event Handlers
    $('#sandboxSimulateBtn, #sandboxPanelSimulateBtn').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Simulating...');
        
        $.post('{{ route('checkout.simulate-sandbox', ['invoice' => $invoice->invoice_id]) }}', {
            _token: '{{ csrf_token() }}'
        }, function(res) {
            if (res.success) {
                window.location.href = res.redirect;
            } else {
                alert(res.error || 'Simulation failed.');
                btn.prop('disabled', false).html(originalHtml);
            }
        }).fail(function() {
            alert('An error occurred during simulation.');
            btn.prop('disabled', false).html(originalHtml);
        });
    });
</script>
@endsection
