@extends('dashboard.layout')

@section('page_title')
Configure Gateways: {{ $store->name }}
@endsection

@section('styles')
<style>
    .configs-wrapper {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    /* Page Navigation Back Bar */
    .back-nav-bar {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }

    .back-nav-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--gray);
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        transition: var(--transition);
    }

    .back-nav-link:hover {
        color: var(--primary);
    }

    /* Gateway Panel styling */
    .gateway-panel {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--border-radius);
        padding: 30px;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        gap: 25px;
        opacity: 0.65;
        position: relative;
    }

    .gateway-panel.method-active {
        opacity: 1;
        border-color: rgba(99, 102, 241, 0.25);
        box-shadow: 0 10px 30px rgba(99, 102, 241, 0.05);
    }

    .gateway-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 20px;
        border-bottom: 1px dashed var(--border);
    }

    .gateway-title {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .gateway-title img {
        width: 32px;
        height: 32px;
        object-fit: contain;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: white;
        padding: 2px;
    }

    .gateway-title .icon-placeholder {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.03);
    }

    [data-theme="dark"] .gateway-title .icon-placeholder {
        background: rgba(255,255,255,0.03);
    }

    .gateway-title span {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--dark);
    }

    .gateway-title code {
        font-size: 0.72rem;
        color: var(--gray);
        background: rgba(0,0,0,0.04);
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-left: 5px;
    }

    [data-theme="dark"] .gateway-title code {
        background: rgba(255,255,255,0.04);
    }

    /* Switch toggle */
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 26px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--gray);
        transition: .4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: var(--success);
    }

    input:checked + .slider:before {
        transform: translateX(24px);
    }

    /* Form Design */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group label {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--gray);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: rgba(255, 255, 255, 0.4);
        color: var(--dark);
        font-family: inherit;
        font-size: 0.88rem;
        outline: none;
        transition: var(--transition);
    }

    [data-theme="dark"] .form-control {
        background: rgba(0, 0, 0, 0.15);
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    /* Previews & Assets File Controls */
    .asset-preview-container {
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
        background: rgba(0,0,0,0.01);
        padding: 12px 16px;
        border-radius: 10px;
        border: 1px solid var(--border);
    }

    [data-theme="dark"] .asset-preview-container {
        background: rgba(255,255,255,0.01);
    }

    .asset-preview-img {
        width: 44px;
        height: 44px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: white;
    }

    .asset-preview-text {
        font-size: 0.8rem;
        color: var(--gray);
        font-weight: 600;
        flex: 1;
    }

    .remove-checkbox-wrapper {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        color: var(--danger);
        cursor: pointer;
        font-weight: 700;
        user-select: none;
    }

    .remove-checkbox-wrapper input {
        accent-color: var(--danger);
        cursor: pointer;
        width: 14px;
        height: 14px;
    }

    /* Bottom Button Actions Group */
    .bottom-actions-panel {
        display: flex;
        gap: 15px;
        align-items: center;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--border-radius);
        padding: 20px 25px;
        box-shadow: var(--shadow);
    }
</style>
@endsection

@section('content')
<div class="back-nav-bar">
    <a href="{{ route('stores.index') }}" class="back-nav-link">
        <i class="fa-solid fa-arrow-left"></i> Back to Storefronts
    </a>
</div>

<form action="{{ route('stores.configs.update', ['store' => $store->id]) }}" method="POST" class="configs-wrapper" enctype="multipart/form-data">
    @csrf

    @foreach($paymentMethods as $method)
        @php
            $cfg = $configs->get($method->id);
            $isActive = $cfg ? $cfg->is_active : false;
            $settings = $cfg ? $cfg->settings : [];
        @endphp

        <div class="gateway-panel @if($isActive) method-active @endif" id="gateway_panel_{{ $method->id }}">
            <div class="gateway-panel-header">
                <div class="gateway-title">
                    @if(!empty($settings['logo']))
                        <img src="{{ asset($settings['logo']) }}" alt="{{ $method->name }}">
                    @elseif($method->logo)
                        <img src="{{ asset($method->logo) }}" alt="{{ $method->name }}">
                    @else
                        <div class="icon-placeholder">
                            @if($method->code === 'bkash')
                                <i class="fa-solid fa-mobile-screen-button" style="color: #d12053; font-size: 1.2rem;"></i>
                            @elseif($method->code === 'nagad')
                                <i class="fa-solid fa-mobile-screen-button" style="color: #f35922; font-size: 1.2rem;"></i>
                            @elseif($method->code === 'upay')
                                <i class="fa-solid fa-mobile-screen-button" style="color: #e5a900; font-size: 1.2rem;"></i>
                            @elseif($method->code === 'rocket')
                                <i class="fa-solid fa-mobile-screen-button" style="color: #8c3b93; font-size: 1.2rem;"></i>
                            @elseif($method->code === 'cellfin')
                                <i class="fa-solid fa-mobile-screen-button" style="color: #0b713b; font-size: 1.2rem;"></i>
                            @elseif($method->code === 'okwallet')
                                <i class="fa-solid fa-mobile-screen-button" style="color: #0c5ca8; font-size: 1.2rem;"></i>
                            @elseif($method->code === 'tap')
                                <i class="fa-solid fa-mobile-screen-button" style="color: #ff007f; font-size: 1.2rem;"></i>
                            @elseif($method->code === 'binance')
                                <i class="fa-solid fa-coins" style="color: #f0b90b; font-size: 1.2rem;"></i>
                            @elseif($method->code === 'bybit')
                                <i class="fa-solid fa-coins" style="color: #16b97d; font-size: 1.2rem;"></i>
                            @elseif($method->code === 'web3')
                                <i class="fa-solid fa-wallet" style="color: #6366f1; font-size: 1.2rem;"></i>
                            @endif
                        </div>
                    @endif
                    <span>{{ $method->name }}</span>
                    <code>{{ $method->code }}</code>
                </div>
                
                <label class="switch">
                    <input type="checkbox" class="gateway-toggle-input" name="active_methods[]" value="{{ $method->id }}" @if($isActive) checked @endif>
                    <span class="slider"></span>
                </label>
            </div>
 
            <div class="form-grid">
                @if(in_array($method->code, ['bkash', 'nagad', 'upay', 'rocket', 'cellfin', 'okwallet', 'tap']))
                    <!-- Mobile Financial Services Settings -->
                    <div class="form-group">
                        <label><i class="fa-solid fa-phone" style="font-size: 0.75rem;"></i> Merchant Personal Number</label>
                        <input type="text" name="settings[{{ $method->id }}][phone]" class="form-control" placeholder="e.g. 01700000000" value="{{ $settings['phone'] ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-money-bill-transfer" style="font-size: 0.75rem;"></i> USD to BDT Conversion Rate</label>
                        <input type="number" step="0.01" name="settings[{{ $method->id }}][conversion_rate]" class="form-control" value="{{ $settings['conversion_rate'] ?? '130.00' }}">
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2;">
                        <label><i class="fa-solid fa-qrcode" style="font-size: 0.75rem;"></i> Pay QR Code Image (Optional)</label>
                        <input type="file" name="settings[{{ $method->id }}][qr_code]" id="qr_code_input_{{ $method->id }}" class="form-control qr-code-file-input" data-method-id="{{ $method->id }}" accept="image/*">
                        <input type="hidden" name="settings[{{ $method->id }}][qr_code_data]" id="qr_code_data_{{ $method->id }}" value="">
                        
                        @if(!empty($settings['qr_code']))
                            <div class="asset-preview-container">
                                <img src="{{ asset($settings['qr_code']) }}" class="asset-preview-img">
                                <span class="asset-preview-text">Current Merchant QR Code Active</span>
                                <label class="remove-checkbox-wrapper">
                                    <input type="checkbox" name="settings[{{ $method->id }}][remove_qr_code]" value="1"> Remove QR Code
                                </label>
                            </div>
                        @endif
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2;">
                        <label><i class="fa-solid fa-image" style="font-size: 0.75rem;"></i> Custom Gateway Logo (Optional - Overrides Default)</label>
                        <input type="file" name="settings[{{ $method->id }}][logo]" class="form-control" accept="image/*">
                        
                        @if(!empty($settings['logo']))
                            <div class="asset-preview-container">
                                <img src="{{ asset($settings['logo']) }}" class="asset-preview-img">
                                <span class="asset-preview-text">Current Custom Gateway Logo Active</span>
                                <label class="remove-checkbox-wrapper">
                                    <input type="checkbox" name="settings[{{ $method->id }}][remove_logo]" value="1"> Remove Logo
                                </label>
                            </div>
                        @endif
                    </div>

                @elseif(in_array($method->code, ['binance', 'bybit']))
                    <!-- Exchange Note System Settings -->
                    <div class="form-group">
                        <label><i class="fa-solid fa-key" style="font-size: 0.75rem;"></i> API Key</label>
                        <input type="text" name="settings[{{ $method->id }}][api_key]" class="form-control" placeholder="Enter API Key" value="{{ $settings['api_key'] ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-lock" style="font-size: 0.75rem;"></i> API Secret Key</label>
                        <input type="password" name="settings[{{ $method->id }}][api_secret]" class="form-control" placeholder="Enter Secret Key" value="{{ $settings['api_secret'] ?? '' }}">
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2;">
                        <label><i class="fa-solid fa-qrcode" style="font-size: 0.75rem;"></i> Pay QR Code Image (Optional)</label>
                        <input type="file" name="settings[{{ $method->id }}][qr_code]" id="qr_code_input_exch_{{ $method->id }}" class="form-control qr-code-file-input" data-method-id="{{ $method->id }}" accept="image/*">
                        <input type="hidden" name="settings[{{ $method->id }}][qr_code_data]" id="qr_code_data_{{ $method->id }}" value="">
                        
                        @if(!empty($settings['qr_code']))
                            <div class="asset-preview-container">
                                <img src="{{ asset($settings['qr_code']) }}" class="asset-preview-img">
                                <span class="asset-preview-text">Current Pay QR Code Active</span>
                                <label class="remove-checkbox-wrapper">
                                    <input type="checkbox" name="settings[{{ $method->id }}][remove_qr_code]" value="1"> Remove QR Code
                                </label>
                            </div>
                        @endif
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2;">
                        <label><i class="fa-solid fa-image" style="font-size: 0.75rem;"></i> Custom Gateway Logo (Optional - Overrides Default)</label>
                        <input type="file" name="settings[{{ $method->id }}][logo]" class="form-control" accept="image/*">
                        
                        @if(!empty($settings['logo']))
                            <div class="asset-preview-container">
                                <img src="{{ asset($settings['logo']) }}" class="asset-preview-img">
                                <span class="asset-preview-text">Current Custom Gateway Logo Active</span>
                                <label class="remove-checkbox-wrapper">
                                    <input type="checkbox" name="settings[{{ $method->id }}][remove_logo]" value="1"> Remove Logo
                                </label>
                            </div>
                        @endif
                    </div>

                @elseif($method->code === 'web3')
                    <!-- Web3 Crypto Wallet Settings -->
                    <div class="form-group">
                        <label><i class="fa-solid fa-wallet" style="font-size: 0.75rem;"></i> BSC Wallet Address</label>
                        <input type="text" name="settings[{{ $method->id }}][bsc_wallet]" class="form-control" placeholder="0x..." value="{{ $settings['bsc_wallet'] ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-wallet" style="font-size: 0.75rem;"></i> Ethereum Wallet Address</label>
                        <input type="text" name="settings[{{ $method->id }}][eth_wallet]" class="form-control" placeholder="0x..." value="{{ $settings['eth_wallet'] ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-wallet" style="font-size: 0.75rem;"></i> Arbitrum Wallet Address</label>
                        <input type="text" name="settings[{{ $method->id }}][arb_wallet]" class="form-control" placeholder="0x..." value="{{ $settings['arb_wallet'] ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-wallet" style="font-size: 0.75rem;"></i> Optimism Wallet Address</label>
                        <input type="text" name="settings[{{ $method->id }}][opmain_wallet]" class="form-control" placeholder="0x..." value="{{ $settings['opmain_wallet'] ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-wallet" style="font-size: 0.75rem;"></i> TRON Wallet Address</label>
                        <input type="text" name="settings[{{ $method->id }}][tron_wallet]" class="form-control" placeholder="T..." value="{{ $settings['tron_wallet'] ?? '' }}">
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2;">
                        <label><i class="fa-solid fa-image" style="font-size: 0.75rem;"></i> Custom Gateway Logo (Optional - Overrides Default)</label>
                        <input type="file" name="settings[{{ $method->id }}][logo]" class="form-control" accept="image/*">
                        
                        @if(!empty($settings['logo']))
                            <div class="asset-preview-container">
                                <img src="{{ asset($settings['logo']) }}" class="asset-preview-img">
                                <span class="asset-preview-text">Current Custom Gateway Logo Active</span>
                                <label class="remove-checkbox-wrapper">
                                    <input type="checkbox" name="settings[{{ $method->id }}][remove_logo]" value="1"> Remove Logo
                                </label>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    <div class="bottom-actions-panel">
        <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-weight: 700; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-floppy-disk"></i> Save Configurations
        </button>
        <a href="{{ route('stores.index') }}" class="btn btn-secondary" style="padding: 12px 28px; font-weight: 700; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center;">
            Cancel
        </a>
    </div>
</form>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<div id="qr-reader" style="display: none;"></div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle panel opacity class based on checkbox changes dynamically
        document.querySelectorAll('.gateway-toggle-input').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const panel = document.getElementById('gateway_panel_' + this.value);
                if (panel) {
                    if (this.checked) {
                        panel.classList.add('method-active');
                    } else {
                        panel.classList.remove('method-active');
                    }
                }
            });
        });

        // QR Code uploading and decoding checks
        const qrInputs = document.querySelectorAll('.qr-code-file-input');
        let html5QrCode = null;
        try {
            html5QrCode = new Html5Qrcode("qr-reader");
        } catch (e) {
            console.error("Failed to initialize Html5Qrcode", e);
        }

        qrInputs.forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const methodId = input.getAttribute('data-method-id');
                const dataHiddenInput = document.getElementById('qr_code_data_' + methodId);
                
                // Clear old values
                if (dataHiddenInput) {
                    dataHiddenInput.value = '';
                }

                // Stage 1: Try html5-qrcode
                if (html5QrCode) {
                    html5QrCode.scanFile(file, true)
                        .then(decodedText => {
                            checkQrPayload(decodedText, dataHiddenInput, input);
                        })
                        .catch(err => {
                            console.warn("Primary Html5Qrcode failed, trying fallback jsQR...", err);
                            runJsQrFallback(file, dataHiddenInput, input);
                        });
                } else {
                    runJsQrFallback(file, dataHiddenInput, input);
                }
            });
        });

        function checkQrPayload(decodedText, hiddenInput, fileInput) {
            fetch("{{ route('dashboard.qr.check') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ qr_data: decodedText })
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error("HTTP error " + res.status);
                }
                return res.json();
            })
            .then(data => {
                if (data.allowed) {
                    if (hiddenInput) {
                        hiddenInput.value = decodedText;
                    }
                } else {
                    alert(data.message || 'This QR Code payload is blocked and cannot be used.');
                    fileInput.value = '';
                    if (hiddenInput) {
                        hiddenInput.value = '';
                    }
                }
            })
            .catch(err => {
                console.error("Failed to verify QR payload on server", err);
                if (hiddenInput) {
                    hiddenInput.value = decodedText;
                }
            });
        }

        function runJsQrFallback(file, hiddenInput, fileInput) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    const scales = [1.0, 0.75, 0.5];
                    let decoded = false;
                    
                    for (let scale of scales) {
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        const width = Math.round(img.width * scale);
                        const height = Math.round(img.height * scale);
                        canvas.width = width;
                        canvas.height = height;
                        context.drawImage(img, 0, 0, width, height);
                        
                        try {
                            const imageData = context.getImageData(0, 0, width, height);
                            const code = jsQR(imageData.data, imageData.width, imageData.height);
                            
                            if (code && code.data) {
                                checkQrPayload(code.data, hiddenInput, fileInput);
                                decoded = true;
                                break;
                            }
                        } catch (e) {
                            console.error(e);
                        }
                    }

                    if (!decoded) {
                        alert('Could not decode QR code automatically. Please ensure the image is a clear QR code, otherwise upload might be rejected on the server.');
                        fileInput.value = '';
                    }
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endsection
