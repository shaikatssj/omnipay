@extends('dashboard.layout')

@section('page_title', 'Manage Stores')

@section('styles')
<style>
    .stores-container {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    /* Stats Overview Banner */
    .stores-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }

    .stat-card-premium {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--border-radius);
        padding: 22px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: var(--shadow);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .stat-card-premium::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -30%;
        width: 130px;
        height: 130px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.08) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .stat-card-premium:hover {
        transform: translateY(-2px);
        border-color: var(--primary);
    }

    .stat-icon-wrapper {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }

    .stat-icon-primary {
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary);
    }

    .stat-icon-success {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    .stat-icon-info {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }

    .stat-details h4 {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--gray);
        margin-bottom: 4px;
    }

    .stat-details .value {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--dark);
    }

    /* Page Header Section */
    .stores-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        padding-bottom: 5px;
    }

    .stores-header-left h2 {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--dark);
    }

    .stores-header-left p {
        font-size: 0.85rem;
        color: var(--gray);
        margin-top: 2px;
    }

    /* Cards Grid */
    .stores-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
    }

    .store-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--border-radius);
        padding: 25px;
        box-shadow: var(--shadow);
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        gap: 20px;
        position: relative;
    }

    .store-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(99, 102, 241, 0.08);
        border-color: rgba(99, 102, 241, 0.3);
    }

    .store-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
    }

    .store-avatar {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        font-weight: 800;
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2);
    }

    .store-title-area {
        flex: 1;
    }

    .store-title-area h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--dark);
        line-height: 1.3;
    }

    .store-owner-text {
        font-size: 0.75rem;
        color: var(--gray);
        margin-top: 3px;
    }

    .badge-status {
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .badge-active {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.15);
    }

    .badge-inactive {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.15);
    }

    /* Meta Fields */
    .store-meta-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        border-top: 1px dashed var(--border);
        border-bottom: 1px dashed var(--border);
        padding: 18px 0;
    }

    .store-meta-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.82rem;
        color: var(--dark);
    }

    .store-meta-item i {
        color: var(--gray);
        font-size: 0.9rem;
        width: 16px;
        text-align: center;
    }

    .store-meta-label {
        color: var(--gray);
        font-weight: 600;
        margin-right: 4px;
    }

    /* API Key Box */
    .api-key-wrapper {
        background: rgba(0, 0, 0, 0.02);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-top: 2px;
    }

    [data-theme="dark"] .api-key-wrapper {
        background: rgba(255, 255, 255, 0.02);
    }

    .api-key-value {
        font-family: monospace;
        font-size: 0.8rem;
        color: var(--dark);
        letter-spacing: 0.5px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }

    .api-key-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .api-key-btn {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--gray);
        font-size: 0.82rem;
        padding: 4px;
        border-radius: 6px;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .api-key-btn:hover {
        color: var(--primary);
        background: rgba(99, 102, 241, 0.08);
    }

    /* Card Footer Actions */
    .store-card-footer {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 5px;
    }

    .action-btn-pill {
        flex: 1;
        padding: 8px 12px;
        font-size: 0.78rem;
        font-weight: 700;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        cursor: pointer;
        transition: var(--transition);
        border: 1px solid transparent;
        background: none;
        color: var(--dark);
    }

    .action-btn-primary-pill {
        background-color: var(--primary);
        color: white;
    }

    .action-btn-primary-pill:hover {
        background-color: var(--primary-dark);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    }

    .action-btn-secondary-pill {
        border: 1px solid var(--border);
        background: transparent;
    }

    .action-btn-secondary-pill:hover {
        background: rgba(0, 0, 0, 0.03);
    }

    [data-theme="dark"] .action-btn-secondary-pill:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .action-btn-icon-only {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: var(--transition);
        border: 1px solid var(--border);
        background: transparent;
        color: var(--gray);
    }

    .action-btn-icon-only:hover {
        background: rgba(0, 0, 0, 0.03);
        color: var(--dark);
    }

    [data-theme="dark"] .action-btn-icon-only:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .action-btn-danger-only:hover {
        background: rgba(239, 68, 68, 0.1);
        border-color: rgba(239, 68, 68, 0.25);
        color: var(--danger);
    }

    .action-btn-warning-only:hover {
        background: rgba(245, 158, 11, 0.1);
        border-color: rgba(245, 158, 11, 0.25);
        color: var(--warning);
    }

    /* Toast Notification */
    .toast-copy {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: var(--dark);
        color: var(--light);
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 8px;
        z-index: 1000;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
    }

    .toast-copy.show {
        opacity: 1;
        transform: translateY(0);
    }
</style>
@endsection

@section('content')
<div class="stores-container">
    @php
        $totalStores = count($stores);
        $activeStores = $stores->where('is_active', true)->count();
        $inactiveStores = $stores->where('is_active', false)->count();
    @endphp

    <!-- Stats Section -->
    <div class="stores-stats-grid">
        <div class="stat-card-premium">
            <div class="stat-icon-wrapper stat-icon-primary">
                <i class="fa-solid fa-shop"></i>
            </div>
            <div class="stat-details">
                <h4>Total Stores</h4>
                <div class="value">{{ $totalStores }}</div>
            </div>
        </div>

        <div class="stat-card-premium">
            <div class="stat-icon-wrapper stat-icon-success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-details">
                <h4>Active Profiles</h4>
                <div class="value">{{ $activeStores }}</div>
            </div>
        </div>

        <div class="stat-card-premium">
            <div class="stat-icon-wrapper stat-icon-info">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <div class="stat-details">
                <h4>Access Mode</h4>
                <div class="value" style="font-size: 0.95rem; font-weight: 700; margin-top: 5px;">
                    {{ \App\Models\Setting::get('merchant_system_enabled', '1') === '1' ? 'Multi-Merchant' : 'Admin-Only' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Header Panel -->
    <div class="stores-header">
        <div class="stores-header-left">
            <h2>Registered Storefronts</h2>
            <p>Deploy secure API isolated processing layers for WHMCS or custom checkouts.</p>
        </div>
        <a href="{{ route('stores.create') }}" class="btn btn-primary" style="padding: 10px 20px; font-weight: 700; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-plus"></i> Register New Store
        </a>
    </div>

    <!-- Cards Grid -->
    <div class="stores-cards-grid">
        @forelse($stores as $st)
            <div class="store-card">
                <!-- Header -->
                <div class="store-card-header">
                    <div class="store-avatar">
                        {{ strtoupper(substr($st->name, 0, 2)) }}
                    </div>
                    <div class="store-title-area">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                            <h3>{{ $st->name }}</h3>
                            <span class="badge-status {{ $st->is_active ? 'badge-active' : 'badge-inactive' }}">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; display: inline-block;"></span>
                                {{ $st->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @if(Auth::user()->role === 'admin')
                            <div class="store-owner-text">
                                <i class="fa-solid fa-user-tie" style="margin-right: 3px; font-size: 0.7rem;"></i> 
                                Owner: <strong>{{ $st->user->name ?? 'Deleted User' }}</strong> ({{ $st->user->email ?? '' }})
                            </div>
                        @else
                            <div class="store-owner-text">
                                <i class="fa-solid fa-calendar-days" style="margin-right: 3px; font-size: 0.7rem;"></i> 
                                Registered: {{ $st->created_at ? $st->created_at->format('M d, Y') : 'N/A' }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Meta Details -->
                <div class="store-meta-list">
                    <div class="store-meta-item">
                        <i class="fa-solid fa-globe"></i>
                        <span class="store-meta-label">Domain:</span>
                        <code style="background: rgba(0,0,0,0.03); padding: 2px 6px; border-radius: 4px; font-size: 0.78rem; font-weight: 700;">
                            {{ $st->domain ?? 'Wildcard (*)' }}
                        </code>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 4px;">
                        <span class="store-meta-label" style="font-size: 0.8rem; display: flex; align-items: center; gap: 5px;">
                            <i class="fa-solid fa-key" style="font-size: 0.8rem; color: var(--gray);"></i> Token Credentials:
                        </span>
                        <div class="api-key-wrapper">
                            <span class="api-key-value" data-raw="{{ $st->api_key }}">••••••••••••••••••••••••••••••••</span>
                            <div class="api-key-actions">
                                <button type="button" class="api-key-btn" onclick="copyApiKey('{{ $st->api_key }}')" title="Copy to Clipboard">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                                <button type="button" class="api-key-btn" onclick="toggleKeyVisibility(this)" title="Toggle Visibility">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="store-card-footer">
                    <a href="{{ route('stores.configs.edit', ['store' => $st->id]) }}" class="action-btn-pill action-btn-primary-pill" title="Configure Gateways">
                        <i class="fa-solid fa-gears"></i> Gateways
                    </a>
                    
                    <a href="{{ route('stores.edit', ['store' => $st->id]) }}" class="action-btn-pill action-btn-secondary-pill" title="Edit Store Details">
                        <i class="fa-solid fa-pencil"></i> Edit
                    </a>

                    <!-- Status Toggle Form -->
                    <form action="{{ route('stores.toggle-status', ['store' => $st->id]) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="action-btn-icon-only action-btn-warning-only" title="Toggle Active/Inactive">
                            <i class="fa-solid fa-power-off"></i>
                        </button>
                    </form>

                    <!-- Regenerate Key Form -->
                    <form action="{{ route('stores.regenerate-key', ['store' => $st->id]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to regenerate the API key? Your existing API integrations for this store will break immediately.');">
                        @csrf
                        <button type="submit" class="action-btn-icon-only" title="Regenerate API Key">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </form>

                    <!-- Delete Form -->
                    <form action="{{ route('stores.delete', ['store' => $st->id]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this store? This will permanently delete all configured gateways and invoices associated with this store. This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-btn-icon-only action-btn-danger-only" title="Delete Store">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; background: var(--card-bg); border: 1px dashed var(--border); padding: 60px 40px; border-radius: var(--border-radius); text-align: center; color: var(--gray); box-shadow: var(--shadow);">
                <i class="fa-solid fa-folder-open" style="font-size: 3rem; color: var(--primary); opacity: 0.35; margin-bottom: 20px;"></i>
                <h3 style="font-weight: 700; color: var(--dark); font-size: 1.1rem; margin-bottom: 5px;">No storefronts found</h3>
                <p style="font-size: 0.85rem; margin-bottom: 25px;">Ready to start accepting payments? Launch your first isolated payment store.</p>
                <a href="{{ route('stores.create') }}" class="btn btn-primary" style="padding: 10px 24px; border-radius: 8px; font-weight: 700;">
                    <i class="fa-solid fa-plus"></i> Create Store
                </a>
            </div>
        @endforelse
    </div>
</div>

<!-- Toast element -->
<div class="toast-copy" id="copyToast">
    <i class="fa-solid fa-circle-check" style="color: var(--success);"></i>
    <span>API key copied to clipboard!</span>
</div>
@endsection

@section('scripts')
<script>
    function toggleKeyVisibility(btn) {
        const span = btn.closest('.api-key-wrapper').querySelector('.api-key-value');
        const icon = btn.querySelector('i');
        const raw = span.getAttribute('data-raw');
        
        if (span.textContent === '••••••••••••••••••••••••••••••••') {
            span.textContent = raw;
            icon.className = 'fa-solid fa-eye-slash';
        } else {
            span.textContent = '••••••••••••••••••••••••••••••••';
            icon.className = 'fa-solid fa-eye';
        }
    }

    function copyApiKey(key) {
        navigator.clipboard.writeText(key).then(() => {
            const toast = document.getElementById('copyToast');
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 2500);
        }).catch(err => {
            console.error('Could not copy API Key: ', err);
        });
    }
</script>
@endsection
