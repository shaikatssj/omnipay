@extends('dashboard.layout')

@section('page_title', 'System Gateways & Plugins')

@section('styles')
<style>
    .gateway-list-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
    }

    .gateway-admin-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--border-radius);
        padding: 25px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 20px;
    }

    .card-title-section {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .gateway-admin-card .icon {
        font-size: 2.2rem;
        color: var(--primary);
    }

    .gateway-admin-card h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .gateway-admin-card span {
        font-size: 0.8rem;
        color: var(--gray);
    }
    .admin-settings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }

    @media(max-width: 900px) {
        .admin-settings-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="admin-settings-grid">
    <!-- Left: General System & Captcha Configurations -->
    <div class="card" style="border-radius: var(--border-radius); background: var(--card-bg); border: 1px solid var(--border); box-shadow: var(--shadow); padding: 25px; display: flex; flex-direction: column; justify-content: flex-start; gap: 20px;">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 5px; color: var(--primary);">
            <i class="fa-solid fa-gears" style="margin-right: 8px;"></i> General System Controls
        </h3>
        <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 10px; line-height: 1.5;">
            Manage global permissions and automated anti-bot gateway triggers.
        </p>

        <!-- Multi-Merchant Toggle -->
        <div style="padding-top: 15px; border-top: 1px dashed var(--border); display: flex; justify-content: space-between; align-items: center; gap: 15px;">
            <div style="flex: 1;">
                <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 5px;">Multi-Merchant Status</h4>
                <p style="font-size: 0.8rem; color: var(--gray); line-height: 1.4; margin-bottom: 0;">
                    Toggle standard registration and login permissions for merchants.
                </p>
            </div>
            @php
                $merchantEnabled = \App\Models\Setting::get('merchant_system_enabled', '1') === '1';
            @endphp
            <form action="{{ route('admin.settings.toggle-merchant') }}" method="POST">
                @csrf
                <button type="submit" class="btn @if($merchantEnabled) btn-primary @else btn-secondary @endif" style="padding: 8px 16px; font-weight: 700; border-radius: 8px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; width: auto; cursor: pointer;">
                    <i class="fa-solid fa-users-gear"></i>
                    {{ $merchantEnabled ? 'Enabled' : 'Disabled' }}
                </button>
            </form>
        </div>

        <!-- Captcha System Toggle -->
        <div style="padding-top: 15px; border-top: 1px dashed var(--border); display: flex; justify-content: space-between; align-items: center; gap: 15px;">
            <div style="flex: 1;">
                <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 5px;">Dynamic Math Captcha</h4>
                <p style="font-size: 0.8rem; color: var(--gray); line-height: 1.4; margin-bottom: 0;">
                    Enforce distorted mathematical OCR-resistant captchas on registration/login forms.
                </p>
            </div>
            @php
                $captchaEnabled = \App\Models\Setting::get('captcha_enabled', '0') === '1';
            @endphp
            <form action="{{ route('admin.settings.toggle-captcha') }}" method="POST">
                @csrf
                <button type="submit" class="btn @if($captchaEnabled) btn-primary @else btn-secondary @endif" style="padding: 8px 16px; font-weight: 700; border-radius: 8px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; width: auto; cursor: pointer;">
                    <i class="fa-solid fa-shield"></i>
                    {{ $captchaEnabled ? 'Enabled' : 'Disabled' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Right: Global SMTP Configurations -->
    <div class="card" style="border-radius: var(--border-radius); background: var(--card-bg); border: 1px solid var(--border); box-shadow: var(--shadow); padding: 25px;">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 5px; color: var(--primary);">
            <i class="fa-solid fa-envelope" style="margin-right: 8px;"></i> Global SMTP Settings
        </h3>
        <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 20px; line-height: 1.5;">
            Configure parameters below. All merchant security alerts and notifications route through this mail relay.
        </p>

        <form action="{{ route('admin.settings.smtp') }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--gray); margin-bottom: 6px;">SMTP Host</label>
                    <input type="text" name="mail_host" class="form-control" placeholder="smtp.mailgun.org" required value="{{ \App\Models\Setting::get('mail_host') }}" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg); color: var(--dark); font-size: 0.85rem; outline: none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--gray); margin-bottom: 6px;">SMTP Port</label>
                    <input type="number" name="mail_port" class="form-control" placeholder="587" required value="{{ \App\Models\Setting::get('mail_port') }}" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg); color: var(--dark); font-size: 0.85rem; outline: none;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--gray); margin-bottom: 6px;">Username</label>
                    <input type="text" name="mail_username" class="form-control" placeholder="postmaster@yourdomain.com" value="{{ \App\Models\Setting::get('mail_username') }}" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg); color: var(--dark); font-size: 0.85rem; outline: none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--gray); margin-bottom: 6px;">Password</label>
                    <input type="password" name="mail_password" class="form-control" placeholder="••••••••••••" value="{{ \App\Models\Setting::get('mail_password') }}" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg); color: var(--dark); font-size: 0.85rem; outline: none;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--gray); margin-bottom: 6px;">Mail Encryption</label>
                    <select name="mail_encryption" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg); color: var(--dark); font-size: 0.85rem; outline: none;">
                        @php
                            $enc = \App\Models\Setting::get('mail_encryption', 'tls');
                        @endphp
                        <option value="tls" @if($enc === 'tls') selected @endif>TLS (Recommended)</option>
                        <option value="ssl" @if($enc === 'ssl') selected @endif>SSL</option>
                        <option value="none" @if($enc === 'none') selected @endif>None</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--gray); margin-bottom: 6px;">From Name</label>
                    <input type="text" name="mail_from_name" class="form-control" placeholder="OmniPay System" required value="{{ \App\Models\Setting::get('mail_from_name', 'OmniPay') }}" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg); color: var(--dark); font-size: 0.85rem; outline: none;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--gray); margin-bottom: 6px;">From Email Address</label>
                <input type="email" name="mail_from_address" class="form-control" placeholder="noreply@yourdomain.com" required value="{{ \App\Models\Setting::get('mail_from_address') }}" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg); color: var(--dark); font-size: 0.85rem; outline: none;">
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-weight: 700; border-radius: 8px; font-size: 0.85rem; width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer;">
                <i class="fa-solid fa-floppy-disk"></i> Save SMTP Settings
            </button>
        </form>
    </div>
</div>

<div class="gateway-list-container">
    @foreach($gateways as $gw)
        <div class="gateway-admin-card">
            <div class="card-title-section">
                <div class="icon" style="display: flex; align-items: center; justify-content: center; width: 50px; height: 50px;">
                    @if($gw->logo)
                        <img src="{{ asset($gw->logo) }}" style="max-width: 50px; max-height: 50px; object-fit: contain; border-radius: 8px; border: 1px solid var(--border);" alt="{{ $gw->name }}">
                    @else
                        @if($gw->code === 'bkash')
                            <i class="fa-solid fa-mobile-screen-button" style="color: #d12053;"></i>
                        @elseif($gw->code === 'nagad')
                            <i class="fa-solid fa-mobile-screen-button" style="color: #f35922;"></i>
                        @elseif($gw->code === 'upay')
                            <i class="fa-solid fa-mobile-screen-button" style="color: #e5a900;"></i>
                        @elseif($gw->code === 'rocket')
                            <i class="fa-solid fa-mobile-screen-button" style="color: #8c2e8a;"></i>
                        @elseif($gw->code === 'cellfin')
                            <i class="fa-solid fa-mobile-screen-button" style="color: #0c723a;"></i>
                        @elseif($gw->code === 'okwallet')
                            <i class="fa-solid fa-mobile-screen-button" style="color: #0f75bc;"></i>
                        @elseif($gw->code === 'tap')
                            <i class="fa-solid fa-mobile-screen-button" style="color: #e11d48;"></i>
                        @elseif($gw->code === 'binance')
                            <i class="fa-solid fa-coins" style="color: #f0b90b;"></i>
                        @elseif($gw->code === 'bybit')
                            <i class="fa-solid fa-coins" style="color: #16b97d;"></i>
                        @elseif($gw->code === 'web3')
                            <i class="fa-solid fa-wallet" style="color: #6366f1;"></i>
                        @endif
                    @endif
                </div>
                <div>
                    <h3>{{ $gw->name }}</h3>
                    <span>Driver Code: <code>{{ $gw->code }}</code></span>
                </div>
            </div>

            <!-- Upload Logo form -->
            <div style="border-top: 1px dashed var(--border); padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.8rem; color: var(--gray); font-weight: 600;">Gateway Logo</span>
                <form action="{{ route('admin.gateways.logo', ['id' => $gw->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; width: auto;">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload
                        <input type="file" name="logo" style="display: none;" onchange="this.form.submit()" accept="image/*">
                    </label>
                </form>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border); padding-top: 15px; margin-top: auto;">
                <span style="font-weight: 600; font-size: 0.85rem; color: {{ $gw->is_active ? 'var(--success)' : 'var(--danger)' }}">
                    {{ $gw->is_active ? 'ENABLED GLOBALLY' : 'DISABLED GLOBALLY' }}
                </span>
                
                <form action="{{ route('admin.gateways.toggle', ['id' => $gw->id]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem;">
                        <i class="fa-solid fa-power-off"></i> Toggle Global Active
                    </button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
