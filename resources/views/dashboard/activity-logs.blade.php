@extends('dashboard.layout')

@section('page_title', 'Dashboard Activity Logs')

@section('styles')
<style>
    html, body {
        height: 100vh;
        overflow: hidden;
    }

    .main-content {
        height: 100vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
        flex-shrink: 0;
    }

    .filter-options {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .form-select, .form-input {
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--card-bg);
        color: var(--dark);
        outline: none;
        font-family: inherit;
        font-size: 0.88rem;
        cursor: pointer;
        transition: var(--transition);
    }
    
    .form-select:focus, .form-input:focus {
        border-color: var(--primary);
    }

    .form-input {
        width: 220px;
        cursor: text;
    }

    /* Scrollable Table Frame */
    .table-frame {
        flex-grow: 1;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--card-bg);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        position: relative;
        min-height: 0;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .data-table th, .data-table td {
        padding: 14px 18px;
        font-size: 0.88rem;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }

    .data-table tr:last-child td {
        border-bottom: none;
    }

    .data-table tbody tr:hover {
        background-color: rgba(99, 102, 241, 0.015);
    }

    /* Sticky Headers */
    .data-table th {
        font-weight: 700;
        color: var(--gray);
        background: var(--light);
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 2px solid var(--border);
    }

    [data-theme="dark"] .data-table th {
        background: #0f172a;
    }

    /* Action Badges */
    .badge-action {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-block;
    }

    .action-create, .action-login, .action-register {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .action-update, .action-config, .action-key {
        background-color: rgba(245, 158, 11, 0.1);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .action-delete, .action-logout {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .action-refund {
        background-color: rgba(99, 102, 241, 0.1);
        color: var(--primary);
        border: 1px solid rgba(99, 102, 241, 0.2);
    }

    .pagination-wrapper {
        margin-top: 25px;
        display: flex;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Modal Styling */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(4px);
    }

    .modal.show {
        display: flex;
    }

    .modal-content {
        background: var(--sidebar-bg);
        border: 1px solid var(--border);
        border-radius: 16px;
        width: 90%;
        max-width: 600px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        position: relative;
        animation: modalFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes modalFadeIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border);
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    .modal-header h3 {
        font-size: 1.2rem;
        font-weight: 700;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--gray);
        transition: var(--transition);
    }

    .close-btn:hover {
        color: var(--danger);
    }

    .meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .meta-item {
        background: rgba(99, 102, 241, 0.03);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 10px 15px;
    }

    .meta-item label {
        font-size: 0.72rem;
        text-transform: uppercase;
        color: var(--gray);
        font-weight: 700;
        display: block;
        margin-bottom: 3px;
    }

    .meta-item span {
        font-size: 0.9rem;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="card" style="flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; margin-bottom: 0; min-height: 0;">
    <form method="GET" action="{{ route('dashboard.activity-logs') }}" id="filterForm">
        <div class="filter-bar">
            <div class="filter-options">
                <select name="action" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Action Types</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" @if(request('action') === $act) selected @endif>{{ strtoupper(str_replace('_', ' ', $act)) }}</option>
                    @endforeach
                </select>

                <input type="text" name="search" placeholder="Search description/IP..." value="{{ request('search') }}" class="form-input">
            </div>
            
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 18px;">
                    <i class="fa-solid fa-magnifying-glass"></i> Filter
                </button>
                <a href="{{ route('dashboard.activity-logs') }}" class="btn btn-secondary" style="padding: 10px 18px;">
                    Clear
                </a>
            </div>
        </div>
    </form>

    <!-- Scrollable table frame -->
    <div class="table-frame">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User / Actor</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Store ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="color: var(--gray);">{{ $log->created_at->toDateTimeString() }}</td>
                        <td>
                            <span style="font-weight: 600;">{{ $log->user->name }}</span>
                            <span style="font-size: 0.75rem; color: var(--gray);">({{ $log->user->role }})</span>
                        </td>
                        <td>
                            @php
                                $badgeClass = 'action-update';
                                if (Str::contains($log->action, 'create') || Str::contains($log->action, 'login') || Str::contains($log->action, 'register')) {
                                    $badgeClass = 'action-create';
                                } elseif (Str::contains($log->action, 'delete') || Str::contains($log->action, 'logout')) {
                                    $badgeClass = 'action-delete';
                                } elseif (Str::contains($log->action, 'refund')) {
                                    $badgeClass = 'action-refund';
                                }
                            @endphp
                            <span class="badge-action {{ $badgeClass }}">
                                {{ str_replace('_', ' ', $log->action) }}
                            </span>
                        </td>
                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->description }}">
                            {{ $log->description }}
                        </td>
                        <td style="font-family: monospace;">{{ $log->ip_address }}</td>
                        <td>
                            @if($log->store)
                                <span style="font-weight: 500;">#{{ $log->store_id }} ({{ $log->store->name }})</span>
                            @else
                                <span style="color: var(--gray); font-style: italic;">N/A</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;" onclick="inspectLog({{ json_encode($log->load('user')) }})">
                                <i class="fa-solid fa-eye"></i> Details
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--gray); padding: 30px;">No activity logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $logs->appends(request()->query())->links() }}
    </div>
</div>

<!-- Inspect Log Modal -->
<div class="modal" id="inspectModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Activity Log Inspector</h3>
            <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        
        <div class="meta-grid">
            <div class="meta-item">
                <label>Actor</label>
                <span id="modalUser">-</span>
            </div>
            <div class="meta-item">
                <label>Action Type</label>
                <span id="modalAction">-</span>
            </div>
            <div class="meta-item">
                <label>IP Address</label>
                <span id="modalIp">-</span>
            </div>
            <div class="meta-item">
                <label>Timestamp</label>
                <span id="modalTime">-</span>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="font-size: 0.8rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Activity Description</label>
            <div style="background: rgba(99, 102, 241, 0.04); border: 1px solid var(--border); padding: 15px; border-radius: 8px; font-size: 0.95rem; font-weight: 600; line-height: 1.5; margin-top: 5px;" id="modalDescription">
                -
            </div>
        </div>

        <div style="margin-bottom: 10px;">
            <label style="font-size: 0.8rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Client User Agent</label>
            <div style="font-family: monospace; font-size: 0.82rem; color: var(--gray); background: var(--light); padding: 12px; border-radius: 8px; border: 1px solid var(--border); overflow-x: auto; margin-top: 5px;" id="modalUserAgent">
                -
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const modal = document.getElementById('inspectModal');
    
    function inspectLog(log) {
        document.getElementById('modalUser').innerText = `${log.user.name} (${log.user.role})`;
        
        let badgeClass = 'action-update';
        if (log.action.includes('create') || log.action.includes('login') || log.action.includes('register')) {
            badgeClass = 'action-create';
        } else if (log.action.includes('delete') || log.action.includes('logout')) {
            badgeClass = 'action-delete';
        } else if (log.action.includes('refund')) {
            badgeClass = 'action-refund';
        }
        
        document.getElementById('modalAction').innerHTML = `<span class="badge-action ${badgeClass}">${log.action.replace('_', ' ')}</span>`;
        document.getElementById('modalIp').innerText = log.ip_address || 'N/A';
        document.getElementById('modalTime').innerText = new Date(log.created_at).toLocaleString();
        document.getElementById('modalDescription').innerText = log.description;
        document.getElementById('modalUserAgent').innerText = log.user_agent || 'N/A';

        modal.classList.add('show');
    }

    function closeModal() {
        modal.classList.remove('show');
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
@endsection
