@extends('dashboard.layout')

@section('page_title', 'Invoice Transaction Logs')

@section('styles')
<style>
    /* Status Widgets Row */
    .grid-4 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
        flex-shrink: 0;
    }

    .status-widget {
        display: flex;
        align-items: center;
        gap: 15px;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: var(--shadow);
    }

    .widget-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .widget-paid .widget-icon {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }
    .widget-pending .widget-icon {
        background-color: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }
    .widget-refunded .widget-icon {
        background-color: rgba(99, 102, 241, 0.1);
        color: var(--primary);
    }
    .widget-expired .widget-icon {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }

    .widget-info label {
        font-size: 0.75rem;
        color: var(--gray);
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .widget-info .val {
        font-size: 1.25rem;
        font-weight: 800;
    }

    /* Filters and Layout */
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
        gap: 10px;
    }

    .form-select {
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
    
    .form-select:focus {
        border-color: var(--primary);
    }

    /* Scrollable table frame */
    .table-frame {
        overflow-x: auto;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--card-bg);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .data-table th, .data-table td {
        padding: 16px 20px;
        font-size: 0.88rem;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }

    .data-table tr:last-child td {
        border-bottom: none;
    }

    .data-table tr:hover {
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

    .badge-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 700;
        display: inline-block;
    }

    .badge-paid {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .badge-pending {
        background-color: rgba(245, 158, 11, 0.1);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .badge-expired {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .badge-refunded {
        background-color: rgba(99, 102, 241, 0.1);
        color: var(--primary);
        border: 1px solid rgba(99, 102, 241, 0.2);
    }

    .badge-sandbox {
        background-color: rgba(245, 158, 11, 0.12);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 6px;
        text-transform: uppercase;
        vertical-align: middle;
    }

    .pagination-wrapper {
        margin-top: 25px;
        display: flex;
        justify-content: center;
        flex-shrink: 0;
    }
</style>
@endsection

@section('content')
<!-- Invoice Status Summary Row -->
<div class="grid-4">
    <div class="status-widget widget-paid">
        <div class="widget-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="widget-info">
            <label>Paid Invoices</label>
            <div class="val">{{ $paidCount }}</div>
        </div>
    </div>

    <div class="status-widget widget-pending">
        <div class="widget-icon">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div class="widget-info">
            <label>Pending Payments</label>
            <div class="val">{{ $pendingCount }}</div>
        </div>
    </div>

    <div class="status-widget widget-refunded">
        <div class="widget-icon">
            <i class="fa-solid fa-rotate-left"></i>
        </div>
        <div class="widget-info">
            <label>Refunded Invoices</label>
            <div class="val">{{ $refundedCount }}</div>
        </div>
    </div>

    <div class="status-widget widget-expired">
        <div class="widget-icon">
            <i class="fa-solid fa-circle-xmark"></i>
        </div>
        <div class="widget-info">
            <label>Expired / Cancelled</label>
            <div class="val">{{ $expiredCount }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="filter-bar">
        <div class="filter-options">
            <label style="font-size: 0.88rem; font-weight: 600; color: var(--gray); margin-right: 5px;">Filter by status:</label>
            <select class="form-select" onchange="filterStatus(this.value)">
                <option value="" @if(request('status') === '') selected @endif>All Statuses</option>
                <option value="pending" @if(request('status') === 'pending') selected @endif>Pending</option>
                <option value="paid" @if(request('status') === 'paid') selected @endif>Paid</option>
                <option value="refunded" @if(request('status') === 'refunded') selected @endif>Refunded</option>
                <option value="expired" @if(request('status') === 'expired') selected @endif>Expired</option>
            </select>

            <label style="font-size: 0.88rem; font-weight: 600; color: var(--gray); margin-left: 15px; margin-right: 5px;">Show per page:</label>
            <select class="form-select" onchange="changePerPage(this.value)">
                <option value="50" @if($perPage === 50) selected @endif>50</option>
                <option value="100" @if($perPage === 100) selected @endif>100</option>
                <option value="200" @if($perPage === 200) selected @endif>200</option>
                <option value="500" @if($perPage === 500) selected @endif>500</option>
            </select>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <button type="button" id="btnBulkDelete" class="btn btn-danger" style="padding: 10px 20px; font-size: 0.88rem; display: none; align-items: center; gap: 8px; border: none; cursor: pointer; background-color: var(--danger); color: white; border-radius: 8px;">
                <i class="fa-solid fa-trash-can"></i> Delete Selected
            </button>
            <a href="{{ route('dashboard.invoices.create') }}" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-plus"></i> Create Invoice
            </a>
        </div>
    </div>

    <!-- Scrollable Table Frame -->
    <div class="table-frame">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAll" style="cursor: pointer; transform: scale(1.1);"></th>
                    <th>Invoice ID</th>
                    <th>Store</th>
                    <th>Customer Name</th>
                    <th>Customer Email</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Gateway</th>
                    <th>Created At</th>
                    <th>Paid At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" name="ids[]" value="{{ $inv->id }}" class="invoice-checkbox" style="cursor: pointer; transform: scale(1.1);"></td>
                        <td style="font-family: monospace; font-weight: 700; color: var(--primary);">
                            {{ $inv->invoice_id }}
                            @if($inv->is_sandbox)
                                <span class="badge-sandbox">Sandbox</span>
                            @endif
                        </td>
                        <td><span style="font-weight: 600;">{{ $inv->store->name ?? 'N/A' }}</span></td>
                        <td>{{ $inv->customer_name }}</td>
                        <td>{{ $inv->customer_email }}</td>
                        <td><strong style="font-size: 0.95rem;">{{ number_format($inv->amount, 2) }} {{ $inv->currency }}</strong></td>
                        <td>
                            <span class="badge-status @if($inv->status==='paid') badge-paid @elseif($inv->status==='pending') badge-pending @elseif($inv->status==='refunded') badge-refunded @else badge-expired @endif">
                                {{ strtoupper($inv->status) }}
                            </span>
                        </td>
                        <td>
                            @if($inv->paymentMethod)
                                <div><strong>{{ $inv->paymentMethod->name }}</strong></div>
                                @if(!empty($inv->meta_data))
                                    @php $meta = is_array($inv->meta_data) ? $inv->meta_data : json_decode($inv->meta_data, true); @endphp
                                    @if(isset($meta['txHash']))
                                        <div style="font-size: 0.75rem; color: var(--gray); font-family: monospace; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $meta['txHash'] }}">
                                            Hash: {{ substr($meta['txHash'], 0, 12) }}...
                                        </div>
                                    @endif
                                    @foreach(['bkash', 'nagad', 'upay', 'rocket', 'cellfin', 'okwallet', 'tap'] as $mfsCode)
                                        @if(isset($meta[$mfsCode . '_payments']))
                                            @foreach($meta[$mfsCode . '_payments'] as $p)
                                                <div style="font-size: 0.72rem; color: var(--gray); font-family: monospace;">TrxID: {{ $p['trxid'] ?? 'N/A' }}</div>
                                            @endforeach
                                        @endif
                                    @endforeach
                                    @if(isset($meta['refund_logs']))
                                        @foreach($meta['refund_logs'] as $r)
                                            <div style="font-size: 0.72rem; color: var(--danger); font-family: monospace;">Ref: {{ $r['refund_trxid'] ?? 'N/A' }}</div>
                                        @endforeach
                                    @endif
                                @endif
                            @else
                                <span style="color: var(--gray); font-style: italic;">Not Selected</span>
                            @endif
                        </td>
                        <td>{{ $inv->created_at->toDateTimeString() }}</td>
                        <td>{{ $inv->paid_at ? $inv->paid_at->toDateTimeString() : 'N/A' }}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: nowrap;">
                                <a href="{{ $inv->payment_link }}" target="_blank" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Pay
                                </a>
                                
                                <form action="{{ route('dashboard.invoices.delete', ['invoice' => $inv->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this invoice? This action cannot be undone.');" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" style="padding: 6px 12px; font-size: 0.8rem; background-color: var(--danger); color: white; display: inline-flex; align-items: center; gap: 4px; border: none; border-radius: 8px; cursor: pointer;">
                                        <i class="fa-solid fa-trash-can"></i> Delete
                                    </button>
                                </form>

                                @if($inv->status === 'paid')
                                    <form action="{{ route('dashboard.invoices.refund', ['invoice' => $inv->id]) }}" method="POST" style="display: inline-flex; align-items: center; gap: 4px;" onsubmit="return confirm('Are you sure you want to refund this invoice?');">
                                        @csrf
                                        <input type="text" name="reason" placeholder="Reason" style="padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border); font-size: 0.78rem; width: 110px; outline: none; background: var(--card-bg); color: var(--dark);">
                                        <button type="submit" class="btn btn-warning btn-sm" style="padding: 6px 12px; font-size: 0.8rem; background-color: var(--warning); color: white; border: none; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-rotate-left"></i> Refund
                                         </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align: center; color: var(--gray); padding: 30px;">No invoices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Hidden Bulk Delete Form -->
    <form id="realBulkDeleteForm" action="{{ route('dashboard.invoices.bulk-delete') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <div class="pagination-wrapper">
        {{ $invoices->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script>
    function filterStatus(status) {
        const url = new URL(window.location.href);
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        window.location.href = url.toString();
    }

    function changePerPage(perPage) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage);
        window.location.href = url.toString();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.invoice-checkbox');
        const btnBulkDelete = document.getElementById('btnBulkDelete');
        const realForm = document.getElementById('realBulkDeleteForm');

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                toggleBulkDeleteButton();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!this.checked && selectAll) {
                    selectAll.checked = false;
                } else if (selectAll && document.querySelectorAll('.invoice-checkbox:checked').length === checkboxes.length) {
                    selectAll.checked = true;
                }
                toggleBulkDeleteButton();
            });
        });

        function toggleBulkDeleteButton() {
            const checkedCount = document.querySelectorAll('.invoice-checkbox:checked').length;
            if (btnBulkDelete) {
                if (checkedCount > 0) {
                    btnBulkDelete.style.display = 'inline-flex';
                } else {
                    btnBulkDelete.style.display = 'none';
                }
            }
        }

        if (btnBulkDelete && realForm) {
            btnBulkDelete.addEventListener('click', function(e) {
                e.preventDefault();
                
                const checkedBoxes = document.querySelectorAll('.invoice-checkbox:checked');
                if (checkedBoxes.length === 0) {
                    alert('No invoices selected.');
                    return;
                }

                if (!confirm('Are you sure you want to delete all selected invoices? This action cannot be undone.')) {
                    return;
                }

                // Clear any previous dynamic inputs
                const previousInputs = realForm.querySelectorAll('.dynamic-id-input');
                previousInputs.forEach(el => el.remove());

                // Append selected IDs
                checkedBoxes.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    input.className = 'dynamic-id-input';
                    realForm.appendChild(input);
                });

                realForm.submit();
            });
        }
    });
</script>
@endsection
