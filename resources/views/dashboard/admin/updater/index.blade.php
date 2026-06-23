@extends('dashboard.layout')

@section('title', 'System Updater')
@section('page_title', 'System Updater')

@section('styles')
<style>
    .version-card {
        padding: 30px;
        text-align: center;
        border-radius: var(--border-radius);
        border: 1px solid var(--border);
        background-color: var(--card-bg);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    
    .version-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        font-weight: 600;
        color: var(--gray);
        letter-spacing: 1px;
        margin-bottom: 10px;
    }

    .version-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--dark);
    }
    
    .version-number.highlight {
        color: var(--primary);
    }

    .changelog-box {
        background-color: rgba(0,0,0,0.02);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        margin-top: 15px;
        margin-bottom: 30px;
        color: var(--dark);
        line-height: 1.6;
    }

    [data-theme="dark"] .changelog-box {
        background-color: rgba(255,255,255,0.02);
    }

    .updater-actions {
        border-top: 1px solid var(--border);
        padding-top: 25px;
        margin-top: 20px;
    }

    .status-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: rgba(16, 185, 129, 0.15);
        color: var(--success);
        font-size: 1.8rem;
        margin-bottom: 15px;
    }

    .up-to-date-message {
        text-align: center;
        padding: 20px 0;
    }
    
    .up-to-date-message h3 {
        font-size: 1.3rem;
        margin-bottom: 5px;
        font-weight: 700;
    }
    
    .up-to-date-message p {
        color: var(--gray);
        margin-bottom: 20px;
    }
    
    .warning-text {
        color: var(--warning);
        font-size: 0.85rem;
        margin-top: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>
@endsection

@section('content')
<div class="card" style="max-width: 900px; margin: 0 auto;">
    <p style="color: var(--gray); margin-bottom: 30px;">Manage and install updates for your Omnipay installation.</p>

    <div class="grid-3" style="grid-template-columns: 1fr 1fr; margin-bottom: 30px;">
        <!-- Current Version -->
        <div class="version-card">
            <span class="version-label">Current Version</span>
            <span class="version-number">v{{ $currentVersion }}</span>
        </div>

        <!-- Latest Version -->
        <div class="version-card" style="{{ $updateAvailable ? 'border-color: var(--primary); background-color: var(--primary-light);' : '' }}">
            <span class="version-label" style="{{ $updateAvailable ? 'color: var(--primary-dark);' : '' }}">Latest Available</span>
            <span class="version-number {{ $updateAvailable ? 'highlight' : '' }}">v{{ $latestVersion }}</span>
        </div>
    </div>

    @if($updateAvailable)
        <div>
            <h3 style="font-size: 1.1rem; font-weight: 700;">What's New in v{{ $latestVersion }}</h3>
            <div class="changelog-box">
                {{ $changelog ?? 'No changelog provided.' }}
            </div>
        </div>

        <div class="updater-actions">
            <form action="{{ route('admin.updater.run') }}" method="POST" id="update-form">
                @csrf
                <button type="submit" onclick="return confirmUpdate()" class="btn btn-primary" style="padding: 12px 25px; font-size: 1rem;">
                    <i class="fa-solid fa-cloud-arrow-down"></i> Install Update Now
                </button>
            </form>
            <div class="warning-text">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Please backup your database before updating. The update process will automatically apply database migrations.
            </div>
        </div>
    @else
        <div class="updater-actions up-to-date-message">
            <div class="status-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <h3>You're up to date!</h3>
            <p>Omnipay is running the latest available version.</p>
            <a href="{{ route('admin.updater') }}" class="btn btn-secondary">
                <i class="fa-solid fa-rotate-right"></i> Check Again
            </a>
        </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
    function confirmUpdate() {
        if(confirm('Are you sure you want to install this update? Ensure you have backed up your database.')) {
            const btn = document.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Installing... Please do not close this window.';
            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
            document.getElementById('update-form').submit();
            return true;
        }
        return false;
    }
</script>
@endsection
