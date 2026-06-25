<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    /**
     * Show analytics page.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Date range
        $days = (int) $request->get('days', 30);
        if (!in_array($days, [7, 30, 90, 365])) {
            $days = 30;
        }
        $startDate = now()->subDays($days)->startOfDay();

        $query = Invoice::where('paid_at', '>=', $startDate)->where('status', 'paid');
        $allInvoicesQuery = Invoice::where('created_at', '>=', $startDate);

        if ($user->role !== 'admin') {
            $ownedStoreIds = Store::where('user_id', $user->id)->pluck('id');
            $staffStoreIds = $user->staffStores()->pluck('stores.id');
            $storeIds = $ownedStoreIds->merge($staffStoreIds)->unique();
            
            $query->whereIn('store_id', $storeIds);
            $allInvoicesQuery->whereIn('store_id', $storeIds);
        }

        // Stats
        $volume = $query->sum('amount');
        $paidCount = $query->count();
        $totalCount = $allInvoicesQuery->count();
        $successRate = $totalCount > 0 ? round(($paidCount / $totalCount) * 100, 1) : 0;
        $avgTicket = $paidCount > 0 ? round($volume / $paidCount, 2) : 0;

        // Group by Date for Chart
        $revenueQuery = (clone $query)
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $transactionsQuery = (clone $query)
            ->selectRaw('DATE(paid_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Fill chart timeline
        $chartLabels = [];
        $chartRevenue = [];
        $chartCount = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateKeyYmd = $date->format('Y-m-d');
            $chartLabels[] = $date->format('M d');

            $rev = 0;
            if ($revenueQuery->has($dateKeyYmd)) {
                $rev = (float) $revenueQuery->get($dateKeyYmd)->total;
            }
            $chartRevenue[] = round($rev, 2);

            $count = 0;
            if ($transactionsQuery->has($dateKeyYmd)) {
                $count = (int) $transactionsQuery->get($dateKeyYmd)->count;
            }
            $chartCount[] = $count;
        }

        // Top Stores
        $topStoresQuery = (clone $query)
            ->selectRaw('store_id, SUM(amount) as total_volume, COUNT(*) as total_count')
            ->groupBy('store_id')
            ->orderByDesc('total_volume')
            ->with('store')
            ->take(5)
            ->get();

        return view('dashboard.analytics.index', compact(
            'days', 'volume', 'paidCount', 'successRate', 'avgTicket', 
            'chartLabels', 'chartRevenue', 'chartCount', 'topStoresQuery'
        ));
    }

    /**
     * Export invoices to CSV
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        
        $query = Invoice::with(['store', 'paymentMethod'])->orderByDesc('created_at');

        if ($user->role !== 'admin') {
            $ownedStoreIds = Store::where('user_id', $user->id)->pluck('id');
            $staffStoreIds = $user->staffStores()->pluck('stores.id');
            $storeIds = $ownedStoreIds->merge($staffStoreIds)->unique();
            
            $query->whereIn('store_id', $storeIds);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('store_id') && $request->store_id !== '') {
            $query->where('store_id', $request->store_id);
        }

        $invoices = $query->get();

        $filename = "export_invoices_" . date('Y-m-d_H-i-s') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, ['Invoice ID', 'Store', 'Customer Name', 'Customer Email', 'Amount', 'Currency', 'Status', 'Payment Method', 'Created At', 'Paid At']);

        foreach ($invoices as $invoice) {
            fputcsv($handle, [
                $invoice->invoice_id,
                $invoice->store->name ?? 'N/A',
                $invoice->customer_name,
                $invoice->customer_email,
                $invoice->amount,
                $invoice->currency,
                $invoice->status,
                $invoice->paymentMethod->name ?? 'N/A',
                $invoice->created_at,
                $invoice->paid_at
            ]);
        }

        fclose($handle);
        exit;
    }
}
