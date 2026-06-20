@extends('install.layout')

@section('title', 'OmniPay Installer - Installation Complete')

@section('styles')
<style>
    .success-badge-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        margin: 20px 0;
        text-align: center;
    }

    .success-large-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: rgba(16, 185, 129, 0.1);
        border: 4px solid var(--success);
        color: var(--success);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
    }

    @keyframes scaleIn {
        from { transform: scale(0.6); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .credentials-card {
        background: rgba(99, 102, 241, 0.04);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 20px;
        margin: 15px 0;
    }

    .credential-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
        font-size: 0.95rem;
    }

    .credential-row:last-child {
        border-bottom: none;
    }

    .credential-label {
        font-weight: 700;
        color: var(--dark);
    }

    .credential-value {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        color: var(--primary);
        font-weight: 600;
    }
</style>
@endsection

@section('progress')
<div class="progress-steps">
    <div class="progress-line"></div>
    <div class="progress-line-fill" style="width: 100%;"></div>
    
    <div class="step-node completed">
        <i class="fa-solid fa-check"></i>
        <span class="step-label">Prerequisites</span>
    </div>
    <div class="step-node completed">
        <i class="fa-solid fa-check"></i>
        <span class="step-label">Database</span>
    </div>
    <div class="step-node completed">
        <i class="fa-solid fa-check"></i>
        <span class="step-label">Admin Setup</span>
    </div>
    <div class="step-node completed">
        <i class="fa-solid fa-check"></i>
        <span class="step-label">Installing</span>
    </div>
    <div class="step-node active">
        <i class="fa-solid fa-circle-check"></i>
        <span class="step-label">Finish</span>
    </div>
</div>
@endsection

@section('content')
<div class="success-badge-container">
    <div class="success-large-icon">
        <i class="fa-solid fa-circle-check"></i>
    </div>
    <h1>Installation Complete!</h1>
    <p class="subtitle">OmniPay has been successfully configured and installed. You can now log in using the credentials configured during setup.</p>
</div>

<h3 style="font-size: 1rem; font-weight: 800; text-transform: uppercase; color: var(--dark); margin: 25px 0 5px 0; letter-spacing: 0.5px;">Account Credentials</h3>

<div class="credentials-card">
    <div class="credential-row">
        <span class="credential-label">Admin Panel URL</span>
        <span class="credential-value">{{ url('/login') }}</span>
    </div>
    <div class="credential-row">
        <span class="credential-label">Administrator Account</span>
        <span class="credential-value">Your Configured Email</span>
    </div>
    <div class="credential-row">
        <span class="credential-label">Admin Password</span>
        <span class="credential-value">Your Configured Password</span>
    </div>
</div>

<div class="alert alert-success" style="margin-top: 20px;">
    <i class="fa-solid fa-lightbulb"></i>
    <span><strong>Security Notice:</strong> The installation wizard has been locked automatically. To re-run the installation, you must delete the lock file at <code>storage/installed</code>.</span>
</div>

<div class="btn-wrapper" style="margin-top: 30px;">
    <div></div>
    <a href="{{ route('login') }}" class="btn btn-primary" style="width: 100%; text-align: center;">
        <span>Launch Application</span>
        <i class="fa-solid fa-rocket"></i>
    </a>
</div>
@endsection
