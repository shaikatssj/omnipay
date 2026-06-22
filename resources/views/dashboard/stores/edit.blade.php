@extends('dashboard.layout')

@section('page_title', 'Edit Store: ' . $store->name)

@section('styles')
<style>
    .form-wrapper {
        max-width: 540px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--gray);
        margin-bottom: 8px;
    }

    .form-control {
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

    [data-theme="dark"] .form-control {
        background: rgba(0, 0, 0, 0.2);
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .help-text {
        font-size: 0.78rem;
        color: var(--gray);
        margin-top: 5px;
    }
</style>
@endsection

@section('content')
<div class="card form-wrapper">
    <form action="{{ route('stores.update', ['store' => $store->id]) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="name">Store Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. My E-commerce Site" value="{{ old('name', $store->name) }}" required>
            <div class="help-text">Choose a descriptive name for your store. Customers will see this name on the checkout screen.</div>
        </div>

        <div class="form-group">
            <label for="domain">Allowed Website Domain (Origin)</label>
            <input type="text" name="domain" id="domain" class="form-control" placeholder="e.g. mysite.com" value="{{ old('domain', $store->domain) }}">
            <div class="help-text">Security check: requests to initialize payments will only be accepted from this origin/website.</div>
        </div>

        <h3 style="margin-top: 30px; margin-bottom: 15px; font-size: 1.1rem;">White-Label Customization</h3>
        <div class="help-text" style="margin-bottom: 15px;">Customize how the checkout page looks for this specific store.</div>

        <div class="form-group">
            <label for="theme_color">Theme Primary Color</label>
            <input type="color" name="theme_color" id="theme_color" class="form-control" style="height: 50px; padding: 5px;" value="{{ old('theme_color', $store->theme_color ?? '#6366f1') }}">
            <div class="help-text">Select the primary color for buttons and accents on the checkout page.</div>
        </div>

        <div class="form-group">
            <label for="custom_css">Custom CSS</label>
            <textarea name="custom_css" id="custom_css" class="form-control" rows="4" placeholder="body { background: #000; }">{{ old('custom_css', $store->custom_css) }}</textarea>
            <div class="help-text">Inject raw CSS into the checkout page header to heavily modify the appearance.</div>
        </div>

        <div class="form-group">
            <label for="checkout_layout">Checkout Layout Configuration</label>
            <select name="checkout_layout" id="checkout_layout" class="form-control">
                <option value="right" {{ old('checkout_layout', $store->checkout_layout) == 'right' ? 'selected' : '' }}>Payment Request on Left, Select Method on Right (Default)</option>
                <option value="left" {{ old('checkout_layout', $store->checkout_layout) == 'left' ? 'selected' : '' }}>Select Method on Left, Payment Request on Right</option>
            </select>
            <div class="help-text">Choose the alignment for the checkout grid layout.</div>
        </div>

        <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
            <input type="hidden" name="hide_branding" value="0">
            <input type="checkbox" name="hide_branding" id="hide_branding" value="1" {{ old('hide_branding', $store->hide_branding) ? 'checked' : '' }} style="width: 18px; height: 18px;">
            <label for="hide_branding" style="margin-bottom: 0;">Hide "Powered by OmniPay" Footer</label>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Update Store Details
            </button>
            <a href="{{ route('stores.index') }}" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
