@extends('dashboard.layout')

@section('page_title', 'Create Manual Payment Invoice')

@section('styles')
<style>
    .form-container {
        max-width: 600px;
        margin: 0 auto;
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

    .form-control, .form-select {
        width: 100%;
        padding: 12px 16px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: rgba(255, 255, 255, 0.5);
        color: var(--dark);
        font-family: inherit;
        font-size: 0.95rem;
        outline: none;
        transition: var(--transition);
    }

    [data-theme="dark"] .form-control, [data-theme="dark"] .form-select {
        background: rgba(0, 0, 0, 0.2);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    @media(max-width: 600px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="card form-container">
    @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <ul style="list-style: none; padding: 0;">
                @foreach ($errors->all() as $error)
                    <li><i class="fa-solid fa-circle-exclamation" style="margin-right: 5px;"></i> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('dashboard.invoices.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="store_id">Select Store</label>
            <select name="store_id" id="store_id" class="form-select" required>
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ old('store_id') == $st->id ? 'selected' : '' }}>{{ $st->name }} ({{ $st->domain ?? 'localhost' }})</option>
                @endforeach
            </select>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="amount">Payment Amount</label>
                <input type="number" step="0.000001" name="amount" id="amount" class="form-control" placeholder="e.g. 10.00" value="{{ old('amount') }}" required>
            </div>
            <div class="form-group">
                <label for="currency">Currency</label>
                <select name="currency" id="currency" class="form-select" required>
                    <option value="USDT" {{ old('currency') == 'USDT' ? 'selected' : '' }}>USDT (Crypto)</option>
                    <option value="BDT" {{ old('currency') == 'BDT' ? 'selected' : '' }}>BDT (MFS/Tk)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="customer_name">Customer Full Name</label>
            <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="e.g. John Doe" value="{{ old('customer_name') }}" required>
        </div>

        <div class="form-group">
            <label for="customer_email">Customer Email Address</label>
            <input type="email" name="customer_email" id="customer_email" class="form-control" placeholder="e.g. john@example.com" value="{{ old('customer_email') }}" required>
        </div>

        <div class="form-group">
            <label for="callback_url">Webhook Callback URL (Optional)</label>
            <input type="url" name="callback_url" id="callback_url" class="form-control" placeholder="https://my-store.com/webhooks/omnipay" value="{{ old('callback_url') }}">
        </div>

        <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 10px; margin-bottom: 25px;">
            <input type="checkbox" name="is_sandbox" id="is_sandbox" value="1" {{ old('is_sandbox') ? 'checked' : '' }} style="width: 18px; height: 18px; cursor: pointer;">
            <label for="is_sandbox" style="margin-bottom: 0; cursor: pointer; font-weight: 600; color: var(--warning);">Enable Sandbox / Test Mode (Risk-Free Checkout)</label>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 25px;">
            <button type="submit" class="btn btn-primary" style="flex-grow: 1; padding: 12px;">
                <i class="fa-solid fa-file-invoice-dollar" style="margin-right: 8px;"></i> Generate Invoice & Payment Link
            </button>
            <a href="{{ route('dashboard.invoices') }}" class="btn btn-secondary" style="padding: 12px 20px;">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
