@extends('dashboard.layout')

@section('page_title', 'Analytics & Reports')

@section('styles')
<style>
    .analytics-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .date-filter {
        display: flex;
        gap: 10px;
    }
    
    .date-filter a {
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        background: var(--card-bg);
        border: 1px solid var(--border);
        color: var(--gray);
        transition: var(--transition);
    }
    
    .date-filter a:hover {
        background: rgba(0,0,0,0.02);
    }
    
    .date-filter a.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }

    .metric-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--border-radius);
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .metric-title {
        font-size: 0.85rem;
        color: var(--gray);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .metric-value {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--dark);
    }

    .chart-container {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--border-radius);
        padding: 25px;
        margin-bottom: 25px;
    }

    .top-stores-container {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--border-radius);
        padding: 25px;
    }
    
    .table-frame {
        width: 100%;
        overflow-x: auto;
    }
    
    .styled-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .styled-table th, .styled-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--border);
        font-size: 0.9rem;
    }
    
    .styled-table th {
        color: var(--gray);
        font-weight: 600;
        background: rgba(0,0,0,0.02);
    }
</style>
@endsection

@section('content')
<div class="analytics-header">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 800;">Analytics Overview</h2>
        <p style="font-size: 0.85rem; color: var(--gray);">Analyze your transaction volume and store performance.</p>
    </div>
    
    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
        <div class="date-filter">
            <a href="{{ route('dashboard.analytics', ['days' => 7]) }}" class="{{ $days == 7 ? 'active' : '' }}">7 Days</a>
            <a href="{{ route('dashboard.analytics', ['days' => 30]) }}" class="{{ $days == 30 ? 'active' : '' }}">30 Days</a>
            <a href="{{ route('dashboard.analytics', ['days' => 90]) }}" class="{{ $days == 90 ? 'active' : '' }}">90 Days</a>
            <a href="{{ route('dashboard.analytics', ['days' => 365]) }}" class="{{ $days == 365 ? 'active' : '' }}">1 Year</a>
        </div>
        
        <a href="{{ route('dashboard.analytics.export') }}" class="btn btn-secondary">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </a>
    </div>
</div>

<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-title">Processed Volume</div>
        <div class="metric-value" style="color: var(--primary);">${{ number_format($volume, 2) }}</div>
    </div>
    
    <div class="metric-card">
        <div class="metric-title">Paid Invoices</div>
        <div class="metric-value">{{ number_format($paidCount) }}</div>
    </div>
    
    <div class="metric-card">
        <div class="metric-title">Success Rate</div>
        <div class="metric-value" style="color: var(--success);">{{ $successRate }}%</div>
    </div>
    
    <div class="metric-card">
        <div class="metric-title">Avg Ticket Size</div>
        <div class="metric-value">${{ number_format($avgTicket, 2) }}</div>
    </div>
</div>

<div class="chart-container">
    <h3 style="margin-bottom: 20px; font-size: 1.1rem;">Revenue & Transactions</h3>
    <div style="position: relative; height: 350px; width: 100%;">
        <canvas id="analyticsChart"></canvas>
    </div>
</div>

<div class="top-stores-container">
    <h3 style="margin-bottom: 20px; font-size: 1.1rem;">Top Performing Stores</h3>
    <div class="table-frame">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Store Name</th>
                    <th>Volume Processed</th>
                    <th>Transactions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topStoresQuery as $storeStat)
                <tr>
                    <td style="font-weight: 600;">{{ $storeStat->store->name ?? 'Deleted Store' }}</td>
                    <td style="color: var(--success); font-weight: 700;">${{ number_format($storeStat->total_volume, 2) }}</td>
                    <td>{{ number_format($storeStat->total_count) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center; color: var(--gray);">No data available for this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        
        const labels = {!! json_encode($chartLabels) !!};
        const revenueData = {!! json_encode($chartRevenue) !!};
        const countData = {!! json_encode($chartCount) !!};

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
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Transactions (Count)',
                        data: countData,
                        borderColor: '#10b981',
                        borderWidth: 2,
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 12, usePointStyle: true }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Revenue ($)' },
                        grid: { borderDash: [4, 4] }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Count' },
                        grid: { drawOnChartArea: false }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    });
</script>
@endsection
