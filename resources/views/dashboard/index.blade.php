@extends('dashboard.layout')

@section('page_title', 'Dashboard Overview')

@section('styles')
<style>
    /* Premium Grid & Metric Widgets */
    .grid-4 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 25px;
        margin-bottom: 25px;
    }

    .metric-card {
        display: flex;
        align-items: center;
        gap: 20px;
        position: relative;
        overflow: hidden;
        transition: var(--transition);
        border: 1px solid var(--border);
    }

    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 20px rgba(0, 0, 0, 0.08);
    }

    .metric-icon {
        width: 54px;
        height: 54px;
        border-radius: 14px;
        background-color: var(--primary-light);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .metric-card.success-card .metric-icon {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    .metric-card.warning-card .metric-icon {
        background-color: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }

    .metric-card.danger-card .metric-icon {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }

    .metric-info h3 {
        font-size: 0.8rem;
        color: var(--gray);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 4px;
        font-weight: 700;
    }

    .metric-info .value {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .metric-subtext {
        font-size: 0.72rem;
        color: var(--gray);
        margin-top: 4px;
    }

    /* Chart Layout */
    .chart-card {
        padding: 25px;
        border: 1px solid var(--border);
        margin-bottom: 25px;
    }

    .analytics-grid {
        display: grid;
        grid-template-columns: 2fr 1.1fr;
        gap: 25px;
        margin-bottom: 25px;
        align-items: start;
    }

    @media (max-width: 1100px) {
        .analytics-grid {
            grid-template-columns: 1fr;
        }
    }

    .chart-legend {
        font-size: 0.85rem;
        color: var(--gray);
    }

    .legend-item i {
        margin-right: 4px;
    }

    /* Scrollable Table Frames */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .section-header h2 {
        font-size: 1.15rem;
        font-weight: 700;
    }

    .table-frame {
        max-height: 380px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--card-bg);
        margin-bottom: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
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
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 2px solid var(--border);
    }

    [data-theme="dark"] .data-table th {
        background: #0f172a;
    }

    .apikey-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: monospace;
    }

    .toggle-key-btn {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--gray);
        font-size: 0.9rem;
    }

    .toggle-key-btn:hover {
        color: var(--primary);
    }

    .badge-status {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .badge-paid {
        background-color: rgba(16, 185, 129, 0.12);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .badge-pending {
        background-color: rgba(245, 158, 11, 0.12);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .badge-expired {
        background-color: rgba(239, 68, 68, 0.12);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .badge-sandbox {
        background-color: rgba(99, 102, 241, 0.12);
        color: var(--primary);
        font-size: 0.65rem;
        font-weight: 700;
        border: 1px solid rgba(99, 102, 241, 0.2);
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 6px;
        text-transform: uppercase;
        vertical-align: middle;
    }
</style>
@endsection

@section('content')
<!-- Metrics Widgets Row -->
<div class="grid-4">
    <div class="card metric-card success-card">
        <div class="metric-icon">
            <i class="fa-solid fa-chart-bar"></i>
        </div>
        <div class="metric-info">
            <h3>Processed Volume</h3>
            <div class="value">${{ number_format($volume, 2) }}</div>
            <div class="metric-subtext">Total successful revenue</div>
        </div>
    </div>
    
    <div class="card metric-card">
        <div class="metric-icon">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div class="metric-info">
            <h3>Invoices / Success Rate</h3>
            <div class="value">{{ $invoicesCount }} <span style="font-size: 1rem; font-weight: 600; color: var(--success);">({{ $successRate }}%)</span></div>
            <div class="metric-subtext">Average payment success rate</div>
        </div>
    </div>

    <div class="card metric-card warning-card">
        <div class="metric-icon">
            <i class="fa-solid fa-sack-dollar"></i>
        </div>
        <div class="metric-info">
            <h3>Average Ticket</h3>
            <div class="value">${{ number_format($avgTicketSize, 2) }}</div>
            <div class="metric-subtext">Avg volume per paid invoice</div>
        </div>
    </div>

    <div class="card metric-card danger-card">
        <div class="metric-icon">
            @if(Auth::user()->role === 'admin')
                <i class="fa-solid fa-cubes"></i>
            @else
                <i class="fa-solid fa-shop"></i>
            @endif
        </div>
        <div class="metric-info">
            @if(Auth::user()->role === 'admin')
                <h3>Total Platforms</h3>
                <div class="value">{{ $storesCount }} <span style="font-size: 0.85rem; color: var(--gray); font-weight: 600;">Stores</span></div>
                <div class="metric-subtext">Aggregate merchant stores</div>
            @else
                <h3>My Active Stores</h3>
                <div class="value">{{ $storesCount }}</div>
                <div class="metric-subtext">Isolated environments</div>
            @endif
        </div>
    </div>
</div>

<div class="analytics-grid">
    <!-- Chart.js Revenue Trend -->
    <div class="card chart-card" style="margin-bottom: 0;">
        <div class="section-header">
            <div>
                <h2 style="margin-bottom: 4px;">Revenue & Transactions Overview</h2>
                <div style="font-size: 0.85rem; color: var(--gray);">Curve stats representing successful checkouts over the last 30 days</div>
            </div>
            <div class="chart-legend">
                <span class="legend-item"><i class="fa-solid fa-circle" style="color: rgba(99, 102, 241, 0.85);"></i> Revenue (Volume)</span>
                <span class="legend-item" style="margin-left: 15px;"><i class="fa-solid fa-circle" style="color: rgba(16, 185, 129, 0.85);"></i> Trx Count</span>
            </div>
        </div>
        <div style="position: relative; height: 320px; width: 100%;">
            <canvas id="analyticsChart"></canvas>
        </div>
    </div>

    <!-- Android SMS Sync Connection -->
    <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 15px; height: 100%;">
        <h2 style="font-size: 1.15rem; font-weight: 700; align-self: flex-start; margin-bottom: 5px;">SMS Reader Sync App</h2>
        <div style="font-size: 0.85rem; color: var(--gray); align-self: flex-start; text-align: left; margin-bottom: 10px; line-height: 1.4;">
            Scan this QR Code with your Android SMS Reader App to automatically connect and sync manual MFS payments.
        </div>
        
        <div style="padding: 15px; background: white; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: inline-block;">
            <canvas id="connection-qr" style="width: 150px; height: 150px; display: block;"></canvas>
        </div>

        <div style="width: 100%; border-top: 1px dashed var(--border); padding-top: 15px; margin-top: 5px; text-align: left;">
            <div style="font-size: 0.8rem; font-weight: 600; color: var(--gray); margin-bottom: 5px;">Manual Connection Details:</div>
            <div style="font-size: 0.78rem; font-family: monospace; background: rgba(0,0,0,0.03); padding: 8px; border-radius: 6px; word-break: break-all; color: var(--dark);">
                <strong>Server:</strong> {{ url('/') }}<br>
                <strong>App Connection Key:</strong> {{ Auth::user()->sms_sync_key }}
            </div>
        </div>
    </div>
</div>

<!-- Stores Section -->
@if(Auth::user()->role === 'merchant')
<div class="card">
    <div class="section-header">
        <h2>My Active Stores</h2>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('stores.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-list-check"></i> Manage Stores
            </a>
            <a href="{{ route('stores.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Create Store
            </a>
        </div>
    </div>

    <!-- Scrollable Table Frame -->
    <div class="table-frame">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Store Name</th>
                    <th>Domain</th>
                    <th>X-API-KEY Token</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stores as $st)
                    <tr>
                        <td style="font-weight: 600;">{{ $st->name }}</td>
                        <td>{{ $st->domain ?? 'N/A' }}</td>
                        <td>
                            <div class="apikey-cell">
                                <span class="api-key-text" data-raw="{{ $st->api_key }}">••••••••••••••••••••••••••••••••</span>
                                <button type="button" class="toggle-key-btn" onclick="toggleKeyVisibility(this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <span class="badge-status {{ $st->is_active ? 'badge-paid' : 'badge-expired' }}">
                                {{ $st->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('stores.configs.edit', ['store' => $st->id]) }}" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 0.8rem;">
                                <i class="fa-solid fa-gear"></i> Configure Gateways
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--gray); padding: 25px;">No stores registered yet. Click Create Store to get started.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Recent Transactions Log -->
<div class="card">
    <div class="section-header">
        <h2>Recent Checkout Invoices</h2>
        <a href="{{ route('dashboard.invoices') }}" class="btn btn-secondary">
            View All Logs
        </a>
    </div>

    <!-- Scrollable Table Frame -->
    <div class="table-frame">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    @if(Auth::user()->role === 'admin')
                        <th>Merchant Store</th>
                    @endif
                    <th>Customer Name</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Paid At</th>
                    <th>Checkout Link</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentInvoices as $inv)
                    <tr>
                        <td style="font-family: monospace; font-weight: 600;">
                            {{ $inv->invoice_id }}
                            @if($inv->is_sandbox)
                                <span class="badge-sandbox">Sandbox</span>
                            @endif
                        </td>
                        @if(Auth::user()->role === 'admin')
                            <td>{{ $inv->store->name ?? 'Deleted Store' }}</td>
                        @endif
                        <td>{{ $inv->customer_name }}</td>
                        <td><strong>{{ number_format($inv->amount, 2) }} {{ $inv->currency }}</strong></td>
                        <td>
                            <span class="badge-status @if($inv->status==='paid') badge-paid @elseif($inv->status==='pending') badge-pending @else badge-expired @endif">
                                {{ strtoupper($inv->status) }}
                            </span>
                        </td>
                        <td>{{ $inv->paid_at ? $inv->paid_at->toDateTimeString() : 'N/A' }}</td>
                        <td>
                            <a href="{{ $inv->payment_link }}" target="_blank" style="color: var(--primary); text-decoration: none; font-weight: 600;">
                                Checkout <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.8rem;"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--gray); padding: 25px;">No invoices generated yet. Use the API to create payments.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/qrious.min.js') }}"></script>
<script>
    // API Key toggling helper
    function toggleKeyVisibility(btn) {
        const span = btn.previousElementSibling;
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

    // Chart.js initialization for Revenue Curve
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize local QR code generation for SMS Sync App
        const connectionQrElement = document.getElementById('connection-qr');
        if (connectionQrElement) {
            new QRious({
                element: connectionQrElement,
                value: {!! json_encode(json_encode([
                    'server_url' => url('/'),
                    'api_key' => Auth::user()->sms_sync_key
                ])) !!},
                size: 200
            });
        }

        const ctx = document.getElementById('analyticsChart').getContext('2d');
        
        const labels = {!! json_encode($chartLabels) !!};
        const revenueData = {!! json_encode($chartRevenue) !!};
        const countData = {!! json_encode($chartCount) !!};

        // Create gradients
        const revGradient = ctx.createLinearGradient(0, 0, 0, 300);
        revGradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
        revGradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

        const countGradient = ctx.createLinearGradient(0, 0, 0, 300);
        countGradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
        countGradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Processed Revenue ($)',
                        data: revenueData,
                        borderColor: '#6366f1',
                        borderWidth: 3,
                        backgroundColor: revGradient,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Transactions (Count)',
                        data: countData,
                        borderColor: '#10b981',
                        borderWidth: 2,
                        backgroundColor: countGradient,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        yAxisID: 'y1',
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 12,
                        backgroundColor: '#0f172a',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        bodyFont: {
                            family: 'Inter',
                            size: 13
                        },
                        titleFont: {
                            family: 'Inter',
                            weight: 'bold',
                            size: 13
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 11
                            },
                            color: '#94a3b8'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: {
                            color: 'rgba(148, 163, 184, 0.08)'
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 11
                            },
                            color: '#94a3b8',
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false // only want grid lines for main y-axis
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 11
                            },
                            color: '#94a3b8',
                            stepSize: 1,
                            precision: 0
                        }
                    }
                }
            }
        });

        // Sync with Theme Changes
        const observer = new MutationObserver(function() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            chart.options.scales.y.grid.color = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(148, 163, 184, 0.08)';
            chart.update();
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    });
</script>
@endsection
