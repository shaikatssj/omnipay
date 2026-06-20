@extends('dashboard.layout')

@section('page_title', 'Register New Store')

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
    <form action="{{ route('stores.store') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label for="name">Store Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. My E-commerce Site" required>
            <div class="help-text">Choose a descriptive name for your store. Customers will see this name on the checkout screen.</div>
        </div>

        <div class="form-group">
            <label for="domain">Allowed Website Domain (Origin)</label>
            <input type="text" name="domain" id="domain" class="form-control" placeholder="e.g. mysite.com">
            <div class="help-text">Security check: requests to initialize payments will only be accepted from this origin/website.</div>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-cloud-arrow-up"></i> Register Store
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
