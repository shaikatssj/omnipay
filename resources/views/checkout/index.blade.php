@extends('checkout.layout')

@section('title', 'Pay Invoice ' . $invoice->invoice_id . ' | OmniPay')

@section('styles')
@if(isset($invoice->store) && $invoice->store->checkout_layout === 'left')
<style>
    @media (min-width: 1024px) {
        .checkout-grid {
            grid-template-columns: 1fr 400px !important;
        }
        .checkout-summary-sidebar {
            order: 2;
            border-right: none !important;
            border-left: 1px solid var(--border-color);
        }
        .checkout-main-content {
            order: 1;
        }
    }
</style>
@endif
<style>
    .checkout-grid {
        display: grid;
        grid-template-columns: 1fr;
        min-height: 550px;
    }
    
    @media (min-width: 1024px) {
        .checkout-grid {
            grid-template-columns: 400px 1fr;
        }
    }
    
    /* Left Sidebar */
    .checkout-summary-sidebar {
        background-color: var(--light);
        border-bottom: 1px solid var(--border-color);
        padding: 50px 50px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 45px;
    }
    
    @media (min-width: 1024px) {
        .checkout-summary-sidebar {
            border-bottom: none;
            border-right: 1px solid var(--border-color);
        }
    }
    
    .sidebar-top {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .sidebar-store-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
        color: var(--gray);
        margin-bottom: 8px;
        display: block;
    }
    
    .sidebar-store-name {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark);
        line-height: 1.2;
        letter-spacing: -0.02em;
        margin-bottom: 5px;
    }
    
    .sidebar-invoice-badge {
        font-size: 0.85rem;
        color: var(--gray);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .sidebar-invoice-badge code {
        background: var(--bg-surface);
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        font-family: 'SFMono-Regular', Consolas, monospace;
        color: var(--gray);
        font-size: 0.85rem;
    }
    
    .sidebar-amount-section {
        background-color: var(--bg-surface);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        padding: 25px;
        border-radius: var(--border-radius);
    }
    
    .sidebar-amount-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--gray);
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }
    
    .sidebar-amount-value {
        font-size: 2.4rem;
        font-weight: 700;
        color: var(--dark);
        letter-spacing: -0.03em;
        display: block;
    }
    
    .sidebar-customer-details {
        border-top: 1px solid var(--border-color);
        padding-top: 30px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .customer-detail-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .customer-detail-label {
        font-size: 0.75rem;
        color: var(--gray);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .customer-detail-label i {
        font-size: 0.8rem;
        color: var(--gray);
    }
    
    .customer-detail-value {
        font-size: 0.95rem;
        color: var(--dark);
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .sidebar-timer {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        background-color: rgba(239, 68, 68, 0.05);
        border: 1px solid rgba(239, 68, 68, 0.2);
        padding: 12px 24px;
        border-radius: var(--border-radius);
        color: var(--danger);
        font-weight: 600;
        font-size: 0.95rem;
        width: 100%;
    }
    
    /* Right Side Main Content */
    .checkout-main-content {
        padding: 50px 60px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 500px;
        position: relative;
    }

    .gateway-selection-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 14px;
        letter-spacing: -0.02em;
    }

    .gateway-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }

    .gateway-card {
        background-color: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 25px 15px;
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        position: relative;
    }

    .gateway-card:hover {
        border-color: var(--gray);
        box-shadow: var(--shadow-sm);
    }

    .gateway-card.active {
        border-color: var(--primary);
        box-shadow: 0 0 0 1px var(--primary);
        background-color: var(--primary-light);
    }

    .gateway-card.active::after {
        content: '\f058';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: 10px;
        right: 10px;
        color: var(--primary);
        font-size: 1rem;
    }

    [data-theme="dark"] .gateway-card.active {
        background-color: rgba(99, 102, 241, 0.1);
    }

    .gateway-icon {
        font-size: 2rem;
        color: var(--dark);
        transition: var(--transition);
    }

    .gateway-card.active .gateway-icon {
        color: var(--primary);
    }

    .gateway-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--dark);
    }

    /* Checkout Details Container */
    .checkout-details {
        display: none;
        animation: fadeIn 0.3s ease forwards;
    }

    .back-btn-container {
        margin-bottom: 30px;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        font-size: 0.9rem;
        border-radius: var(--border-radius);
        background: transparent;
        border: 1px solid var(--border-color);
        font-weight: 500;
        color: var(--gray);
        cursor: pointer;
        transition: var(--transition);
    }

    .back-btn:hover {
        border-color: var(--gray);
        color: var(--dark);
    }

    .details-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 14px;
        color: var(--dark);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--gray);
        margin-bottom: 8px;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        padding-right: 50px;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        background-color: var(--bg-surface);
        color: var(--dark);
        font-family: 'SFMono-Regular', Consolas, monospace;
        font-size: 0.95rem;
        outline: none;
        transition: var(--transition);
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .copy-btn {
        position: absolute;
        right: 8px;
        background: transparent;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        color: var(--gray);
        font-size: 1rem;
        padding: 6px 10px;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .copy-btn:hover {
        background: var(--light);
        color: var(--dark);
    }

    .instructions-box {
        background-color: var(--light);
        border: 1px solid var(--border-color);
        padding: 20px;
        border-radius: var(--border-radius);
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 25px;
        color: var(--dark);
    }

    .network-select-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 10px;
        margin-bottom: 25px;
    }

    .network-btn {
        background-color: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 10px 8px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
        color: var(--dark);
    }

    .network-btn:hover {
        border-color: var(--gray);
    }

    .network-btn.active {
        background-color: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .status-alert {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 20px;
        border-radius: var(--border-radius);
        font-size: 0.95rem;
        font-weight: 500;
        margin-top: 25px;
        background-color: rgba(245, 158, 11, 0.05);
        border: 1px solid rgba(245, 158, 11, 0.2);
        color: var(--warning);
    }

    /* QR Code Redesign */
    .qr-wrapper {
        text-align: center;
        margin-bottom: 30px;
    }

    .qr-frame {
        background: white;
        padding: 15px;
        border-radius: var(--border-radius);
        display: inline-block;
        border: 1px solid var(--border-color);
    }

    .qr-frame img {
        max-width: 180px;
        height: auto;
        display: block;
        border-radius: 8px;
    }

    .qr-text {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--gray);
        margin-top: 15px;
        margin-bottom: 0;
    }

    /* Binance Note Container */
    .binance-note-container {
        background-color: var(--light);
        border: 1px solid var(--border-color);
        padding: 20px;
        border-radius: var(--border-radius);
        text-align: center;
    }

    .binance-note-text {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--gray);
        margin-bottom: 0;
        margin-top: 15px;
        line-height: 1.5;
    }

    /* Sandbox Banner */
    .sandbox-banner {
        background-color: var(--warning);
        color: white;
        padding: 16px 24px;
        font-weight: 600;
        border-radius: var(--border-radius);
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }

    .sandbox-banner-btn {
        background: white;
        color: var(--warning);
        padding: 8px 16px;
        font-size: 0.9rem;
        border-radius: 6px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .sandbox-banner-btn:hover {
        background: rgba(255, 255, 255, 0.9);
    }

    /* Modal Styling */
    .modal-backdrop {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-box {
        background-color: var(--bg-surface);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-lg);
        border-radius: var(--border-radius);
        width: 90%;
        max-width: 450px;
        padding: 35px;
        animation: fadeIn 0.3s ease;
    }

    .modal-header {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--dark);
    }

    .modal-body {
        margin-bottom: 30px;
        font-size: 1rem;
        line-height: 1.6;
        color: var(--gray);
    }

    .trx-select-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 20px;
    }

    .trx-option-btn {
        background-color: var(--bg-surface);
        border: 1px solid var(--border-color);
        padding: 14px;
        border-radius: var(--border-radius);
        font-family: 'SFMono-Regular', Consolas, monospace;
        font-size: 1rem;
        cursor: pointer;
        text-align: center;
        transition: var(--transition);
        font-weight: 600;
        color: var(--dark);
    }

    .trx-option-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    .footer {
        padding: 30px 0 0 0;
        border-top: 1px solid var(--border-color);
        text-align: center;
        font-size: 0.85rem;
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
