<?php

namespace App\Http\Controllers;

use App\Models\PaymentLink;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\ActivityLog;

class PaymentLinkController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            $paymentLinks = PaymentLink::with('store')->latest()->paginate(50);
            $stores = Store::where('is_active', true)->get();
        } else {
            $ownedStoreIds = Store::where('user_id', $user->id)->pluck('id');
            $staffStoreIds = $user->staffStores()->pluck('stores.id');
            $storeIds = $ownedStoreIds->merge($staffStoreIds)->unique();
            
            $paymentLinks = PaymentLink::whereIn('store_id', $storeIds)->with('store')->latest()->paginate(50);
            $stores = Store::whereIn('id', $storeIds)->where('is_active', true)->get();
        }

        return view('dashboard.payment-links.index', compact('paymentLinks', 'stores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'amount' => 'nullable|numeric|gt:0',
            'currency' => 'required|string|max:10',
            'description' => 'nullable|string',
        ]);

        $store = Store::findOrFail($request->store_id);
        
        $user = Auth::user();
        if ($user->role !== 'admin' && $store->user_id !== $user->id) {
            $staffUser = $store->staff()->where('user_id', $user->id)->first();
            if (!$staffUser || $staffUser->pivot->role !== 'manager') {
                abort(403);
            }
        }

        $identifier = Str::slug($request->name) . '-' . Str::random(6);
        while (PaymentLink::where('identifier', $identifier)->exists()) {
            $identifier = Str::slug($request->name) . '-' . Str::random(6);
        }

        $link = PaymentLink::create([
            'store_id' => $store->id,
            'identifier' => $identifier,
            'name' => $request->name,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'description' => $request->description,
            'is_active' => true,
        ]);

        ActivityLog::log('payment_link_create', "Created payment link '{$link->name}'", $store->id);

        return redirect()->route('payment-links.index')->with('success', 'Payment Link created successfully.');
    }

    public function destroy(PaymentLink $paymentLink)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $paymentLink->store->user_id !== $user->id) {
            $staffUser = $paymentLink->store->staff()->where('user_id', $user->id)->first();
            if (!$staffUser || $staffUser->pivot->role !== 'manager') {
                abort(403);
            }
        }

        ActivityLog::log('payment_link_delete', "Deleted payment link '{$paymentLink->name}'", $paymentLink->store_id);
        $paymentLink->delete();

        return redirect()->route('payment-links.index')->with('success', 'Payment Link deleted successfully.');
    }
}
