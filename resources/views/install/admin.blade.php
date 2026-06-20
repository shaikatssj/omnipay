@extends('install.layout')

@section('title', 'OmniPay Installer - Admin Setup')

@section('progress')
<div class="progress-steps">
    <div class="progress-line"></div>
    <div class="progress-line-fill" style="width: 50%;"></div>
    
    <div class="step-node completed">
        <i class="fa-solid fa-check"></i>
        <span class="step-label">Prerequisites</span>
    </div>
    <div class="step-node completed">
        <i class="fa-solid fa-check"></i>
        <span class="step-label">Database</span>
    </div>
    <div class="step-node active">
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
<h1>Administrator Account</h1>
<p class="subtitle">Set up the primary administrator account for this installation. These credentials will be used to manage system gateways and stores.</p>

<form action="{{ route('install.admin.save') }}" method="POST">
    @csrf

    <!-- Admin Name -->
    <div class="form-group">
        <label for="admin_name">Full Name</label>
        <div class="input-wrapper">
            <i class="fa-solid fa-user-tie"></i>
            <input type="text" id="admin_name" name="admin_name" class="form-control" value="{{ old('admin_name', 'Admin User') }}" required placeholder="e.g. John Doe">
        </div>
        @error('admin_name')
            <span style="color: var(--danger); font-size: 0.8rem; font-weight: 600; margin-top: 5px;">{{ $message }}</span>
        @enderror
    </div>

    <!-- Admin Email -->
    <div class="form-group">
        <label for="admin_email">Email Address</label>
        <div class="input-wrapper">
            <i class="fa-solid fa-envelope"></i>
            <input type="email" id="admin_email" name="admin_email" class="form-control" value="{{ old('admin_email', 'admin@omnipay.com') }}" required placeholder="admin@domain.com">
        </div>
        @error('admin_email')
            <span style="color: var(--danger); font-size: 0.8rem; font-weight: 600; margin-top: 5px;">{{ $message }}</span>
        @enderror
    </div>

    <!-- Admin Password -->
    <div class="form-group">
        <label for="admin_password">Password</label>
        <div class="input-wrapper">
            <i class="fa-solid fa-lock"></i>
            <input type="password" id="admin_password" name="admin_password" class="form-control" required placeholder="At least 6 characters" minlength="6">
        </div>
        @error('admin_password')
            <span style="color: var(--danger); font-size: 0.8rem; font-weight: 600; margin-top: 5px;">{{ $message }}</span>
        @enderror
    </div>

    <div class="btn-wrapper">
        <a href="{{ route('install.database') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back</span>
        </a>
        
        <button type="submit" class="btn btn-primary">
            <span>Next Step</span>
            <i class="fa-solid fa-arrow-right"></i>
        </button>
    </div>
</form>
@endsection
