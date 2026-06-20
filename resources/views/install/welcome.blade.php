@extends('install.layout')

@section('title', 'OmniPay Installer - Welcome')

@section('styles')
<style>
    .requirement-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin: 15px 0;
    }

    .requirement-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(99, 102, 241, 0.04);
        border: 1px solid var(--border);
        padding: 14px 20px;
        border-radius: 12px;
        font-size: 0.95rem;
        font-weight: 600;
        transition: var(--transition);
    }

    .requirement-item:hover {
        background: rgba(99, 102, 241, 0.08);
        border-color: rgba(99, 102, 241, 0.2);
    }

    .req-name-desc {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .req-desc {
        font-size: 0.78rem;
        color: var(--gray);
        font-weight: 500;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .status-pass {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    .status-fail {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }

    .section-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--dark);
        margin: 25px 0 10px 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>
@endsection

@section('progress')
<div class="progress-steps">
    <div class="progress-line"></div>
    <div class="progress-line-fill" style="width: 0%;"></div>
    
    <div class="step-node active">
        1
        <span class="step-label">Prerequisites</span>
    </div>
    <div class="step-node">
        2
        <span class="step-label">Database</span>
    </div>
    <div class="step-node">
        3
        <span class="step-label">Admin Setup</span>
    </div>
    <div class="step-node">
        4
        <span class="step-label">Installing</span>
    </div>
    <div class="step-node">
        5
        <span class="step-label">Finish</span>
    </div>
</div>
@endsection

@section('content')
<h1>Initialize Installation</h1>
<p class="subtitle">Welcome to the OmniPay setup wizard. We need to check your system requirements and file permissions to begin configuration.</p>

<!-- 1. PHP Version -->
<div class="section-title">
    <i class="fa-solid fa-microchip"></i>
    <span>Environment Check</span>
</div>
<div class="requirement-list">
    <div class="requirement-item">
        <div class="req-name-desc">
            <span>{{ $requirements['php']['name'] }}</span>
            <span class="req-desc">System PHP: {{ $requirements['php']['current'] }}</span>
        </div>
        @if($requirements['php']['supported'])
            <span class="status-badge status-pass"><i class="fa-solid fa-circle-check"></i> Passed</span>
        @else
            <span class="status-badge status-fail"><i class="fa-solid fa-circle-xmark"></i> Failed</span>
        @endif
    </div>
</div>

<!-- 2. PHP Extensions -->
<div class="section-title">
    <i class="fa-solid fa-puzzle-piece"></i>
    <span>PHP Extensions</span>
</div>
<div class="requirement-list">
    @foreach($requirements['extensions'] as $ext)
        <div class="requirement-item">
            <span>Extension: <strong>{{ $ext['name'] }}</strong></span>
            @if($ext['supported'])
                <span class="status-badge status-pass"><i class="fa-solid fa-circle-check"></i> Enabled</span>
            @else
                <span class="status-badge status-fail"><i class="fa-solid fa-circle-xmark"></i> Missing</span>
            @endif
        </div>
    @endforeach
</div>

<!-- 3. Directory Permissions -->
<div class="section-title">
    <i class="fa-solid fa-folder-open"></i>
    <span>File & Directory Permissions</span>
</div>
<div class="requirement-list">
    @foreach($requirements['directories'] as $name => $dir)
        <div class="requirement-item">
            <div class="req-name-desc">
                <span>{{ $dir['name'] }}</span>
                <span class="req-desc" style="max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Path: {{ $dir['path'] }}</span>
            </div>
            @if($dir['supported'])
                <span class="status-badge status-pass"><i class="fa-solid fa-circle-check"></i> Writable</span>
            @else
                <span class="status-badge status-fail"><i class="fa-solid fa-circle-xmark"></i> Not Writable</span>
            @endif
        </div>
    @endforeach
</div>

@if(!$requirements['all_passed'])
    <div class="alert alert-danger" style="margin-top: 25px;">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span>Some requirements are missing. Please resolve them and refresh the page to continue.</span>
    </div>
@endif

<div class="btn-wrapper">
    <div></div> <!-- Spacing spacer -->
    <a href="{{ route('install.database') }}" class="btn btn-primary" @if(!$requirements['all_passed']) disabled style="pointer-events: none; opacity: 0.55;" @endif>
        <span>Continue Setup</span>
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
@endsection
