@extends('dashboard.layout')

@section('page_title', 'API Request Logs')

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

    /* Badges */
    .badge-method {
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 800;
        color: white;
    }

    .badge-get { background-color: #3b82f6; }
    .badge-post { background-color: #10b981; }
    .badge-put { background-color: #f59e0b; }
    .badge-delete { background-color: #ef4444; }

    .badge-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 700;
        display: inline-block;
    }

    .status-success {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .status-warning {
        background-color: rgba(245, 158, 11, 0.1);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .status-danger {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
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
        max-width: 800px;
        max-height: 85vh;
        overflow-y: auto;
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

    .json-block {
        background: #0f172a;
        color: #e2e8f0;
        padding: 15px;
        border-radius: 8px;
        font-family: monospace;
        font-size: 0.82rem;
        overflow-x: auto;
        max-height: 250px;
        margin-top: 8px;
        border: 1px solid rgba(255, 255, 255, 0.05);
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
    <form method="GET" action="{{ route('dashboard.api-logs') }}" id="filterForm">
        <div class="filter-bar">
            <div class="filter-options">
                <select name="method" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Methods</option>
                    <option value="GET" @if(request('method') === 'GET') selected @endif>GET</option>
                    <option value="POST" @if(request('method') === 'POST') selected @endif>POST</option>
                    <option value="PUT" @if(request('method') === 'PUT') selected @endif>PUT</option>
                    <option value="DELETE" @if(request('method') === 'DELETE') selected @endif>DELETE</option>
                </select>

                <select name="status" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Statuses</option>
                    <option value="200" @if(request('status') === '200') selected @endif>200 OK</option>
                    <option value="201" @if(request('status') === '201') selected @endif>201 Created</option>
                    <option value="400" @if(request('status') === '400') selected @endif>400 Bad Request</option>
                    <option value="401" @if(request('status') === '401') selected @endif>401 Unauthorized</option>
                    <option value="403" @if(request('status') === '403') selected @endif>403 Forbidden</option>
                    <option value="409" @if(request('status') === '409') selected @endif>409 Conflict</option>
                    <option value="422" @if(request('status') === '422') selected @endif>422 Unprocessable</option>
                    <option value="500" @if(request('status') === '500') selected @endif>500 Server Error</option>
                </select>

                <input type="text" name="url" placeholder="Filter by endpoint (e.g. /payment)" value="{{ request('url') }}" class="form-input">
                <input type="text" name="search" placeholder="Search body/IP..." value="{{ request('search') }}" class="form-input">
            </div>
            
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 18px;">
                    <i class="fa-solid fa-magnifying-glass"></i> Filter
                </button>
                <a href="{{ route('dashboard.api-logs') }}" class="btn btn-secondary" style="padding: 10px 18px;">
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
                    <th>Store / Client</th>
                    <th>Method</th>
                    <th>URL</th>
                    <th>Status</th>
                    <th>Duration</th>
                    <th>IP Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="color: var(--gray);">{{ $log->created_at->toDateTimeString() }}</td>
                        <td>
                            @if($log->store)
                                <span style="font-weight: 600;">{{ $log->store->name }}</span>
                            @else
                                <span style="color: var(--gray); font-style: italic;">User/System API</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge-method badge-{{ strtolower($log->method) }}">
                                {{ $log->method }}
                            </span>
                        </td>
                        <td style="font-family: monospace; font-weight: 500;" title="{{ $log->url }}">
                            {{ Str::limit($log->url, 45) }}
                        </td>
                        <td>
                            <span class="badge-status @if($log->response_status >= 200 && $log->response_status < 300) status-success @elseif($log->response_status >= 400 && $log->response_status < 500) status-warning @else status-danger @endif">
                                {{ $log->response_status }}
                            </span>
                        </td>
                        <td>
                            <strong style="color: @if($log->duration > 800) var(--danger) @elseif($log->duration > 300) var(--warning) @else inherit @endif;">
                                {{ number_format($log->duration) }} ms
                            </strong>
                        </td>
                        <td style="font-family: monospace;">{{ $log->ip_address }}</td>
                        <td>
                            <button type="button" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;" onclick="inspectLog({{ json_encode($log) }})">
                                <i class="fa-solid fa-eye"></i> Inspect
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--gray); padding: 30px;">No API logs found matching current filter values.</td>
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
            <h3>API Log Entry Inspector</h3>
            <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        
        <div class="meta-grid">
            <div class="meta-item">
                <label>Method & Endpoint</label>
                <span id="modalMethodUrl">-</span>
            </div>
            <div class="meta-item">
                <label>Status Code</label>
                <span id="modalStatus">-</span>
            </div>
            <div class="meta-item">
                <label>Response Time</label>
                <span id="modalDuration">-</span>
            </div>
            <div class="meta-item">
                <label>IP Address</label>
                <span id="modalIp">-</span>
            </div>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-size: 0.8rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Request Headers</label>
            <pre class="json-block" id="modalRequestHeaders"></pre>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-size: 0.8rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Request Payload / Body</label>
            <pre class="json-block" id="modalRequestBody"></pre>
        </div>

        <div style="margin-bottom: 10px;">
            <label style="font-size: 0.8rem; font-weight: 700; color: var(--gray); text-transform: uppercase;">Response Payload / Body</label>
            <pre class="json-block" id="modalResponseBody"></pre>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const modal = document.getElementById('inspectModal');
    
    function inspectLog(log) {
        document.getElementById('modalMethodUrl').innerHTML = `<span class="badge-method badge-${log.method.toLowerCase()}">${log.method}</span> <span style="font-family: monospace; font-size: 0.88rem; font-weight: 700; margin-left: 8px;">${log.url}</span>`;
        
        const statusBadgeClass = (log.response_status >= 200 && log.response_status < 300) ? 'status-success' : (log.response_status >= 400 && log.response_status < 500 ? 'status-warning' : 'status-danger');
        document.getElementById('modalStatus').innerHTML = `<span class="badge-status ${statusBadgeClass}">${log.response_status}</span>`;
        document.getElementById('modalDuration').innerText = `${log.duration} ms`;
        document.getElementById('modalIp').innerText = log.ip_address;

        // Print request headers
        try {
            document.getElementById('modalRequestHeaders').innerText = JSON.stringify(log.request_headers || {}, null, 2);
        } catch (e) {
            document.getElementById('modalRequestHeaders').innerText = String(log.request_headers);
        }

        // Print request body
        try {
            document.getElementById('modalRequestBody').innerText = JSON.stringify(log.request_body || {}, null, 2);
        } catch (e) {
            document.getElementById('modalRequestBody').innerText = String(log.request_body);
        }

        // Print response body
        try {
            document.getElementById('modalResponseBody').innerText = JSON.stringify(log.response_body || {}, null, 2);
        } catch (e) {
            document.getElementById('modalResponseBody').innerText = String(log.response_body);
        }

        modal.classList.add('show');
    }

    function closeModal() {
        modal.classList.remove('show');
    }

    // Close modal when clicking outside content
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
@endsection
