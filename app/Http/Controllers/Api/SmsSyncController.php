<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Store;
use App\Models\SyncedTransaction;
use App\Services\MfsParser;
use Illuminate\Http\Request;

class SmsSyncController extends Controller
{
    /**
     * Receive SMS transaction logs from the mobile reader app.
     */
    public function sync(Request $request)
    {
        $apiKey = $request->header('X-API-KEY') ?? $request->input('api_key');
        if (empty($apiKey)) {
            return response()->json(['status' => 'error', 'message' => 'API key is required'], 401);
        }

        $user = User::where('sms_sync_key', $apiKey)->whereIn('role', ['merchant', 'admin'])->first();
        if (!$user) {
            $user = User::where('api_key', $apiKey)->whereIn('role', ['merchant', 'admin'])->first();
        }
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Invalid API key'], 401);
        }

        if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1' && $user->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Merchant system is disabled. Only Admin is authorized.'], 403);
        }

        $request->validate([
            'msg_data' => 'required|string',
            'sender' => 'required|string',
        ]);

        $msgdata = preg_replace('/\s+/', ' ', trim($request->msg_data));
        $sender = trim($request->sender);

        $mfsName = MfsParser::normalizeMfsName($sender);
        if (!$mfsName) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fraudulent or unauthorized sender format',
                'sender' => $sender
            ], 403);
        }

        $parsed = MfsParser::parse($mfsName, $msgdata);

        // Fallback to request parameters if parsing failed to extract trxid or amount
        $reqTrxid = $request->input('trxid') ?? $request->input('trnxid') ?? $request->input('trx_id') ?? $request->input('trnx_id') ?? $request->input('txn_id') ?? $request->input('transaction_id');
        if ($reqTrxid && (empty($parsed) || empty($parsed['trxid']))) {
            if (!$parsed) {
                $parsed = [
                    'mfs' => $mfsName,
                    'type' => 'Generic',
                    'raw' => $msgdata,
                ];
            }
            $parsed['trxid'] = strtoupper(trim($reqTrxid));
        }

        $reqAmount = $request->input('amount');
        if ($reqAmount && (empty($parsed) || empty($parsed['amount']))) {
            if (!$parsed) {
                $parsed = [
                    'mfs' => $mfsName,
                    'type' => 'Generic',
                    'raw' => $msgdata,
                ];
            }
            $parsed['amount'] = floatval(str_replace(',', '', $reqAmount));
        }

        if (!$parsed || empty($parsed['amount']) || empty($parsed['trxid'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to parse amount or transaction ID from message payload',
                'parsed_data' => $parsed
            ], 422);
        }

        $amount = $parsed['amount'];
        $trxid = $parsed['trxid'];

        // Check if TrxID already exists to prevent double spend
        if (SyncedTransaction::where('trxid', $trxid)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Duplicate TrxID. Transaction already synced.',
                'trxid' => $trxid
            ], 409);
        }

        $synced = SyncedTransaction::create([
            'user_id' => $user->id,
            'sender' => $mfsName,
            'amount' => $amount,
            'trxid' => $trxid,
            'raw_message' => $msgdata,
            'meta_data' => $parsed,
            'is_used' => false,
            'timestamp' => time()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'SMS data synced successfully',
            'amount' => $synced->amount,
            'trxid' => $synced->trxid,
            'sender' => $synced->sender,
            'meta_data' => $synced->meta_data
        ], 201);
    }

    /**
     * Verify an API key (can be merchant user key or store key).
     */
    public function verifyKey(Request $request)
    {
        $apiKey = $request->header('X-API-KEY') ?? $request->input('api_key');
        if (empty($apiKey)) {
            return response()->json(['valid' => false, 'message' => 'API key is required'], 401);
        }

        // 1. Check if it's a Merchant/Admin User Key
        $user = User::where('api_key', $apiKey)->whereIn('role', ['merchant', 'admin'])->first();
        if (!$user) {
            $user = User::where('sms_sync_key', $apiKey)->whereIn('role', ['merchant', 'admin'])->first();
        }
        if ($user) {
            if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1' && $user->role !== 'admin') {
                return response()->json(['valid' => false, 'message' => 'Merchant system is disabled.'], 403);
            }
            return response()->json([
                'valid' => true,
                'type' => 'merchant',
                'name' => $user->name,
                'email' => $user->email,
                'stores_count' => $user->stores()->count()
            ], 200);
        }

        // 2. Check if it's a Store Key
        $store = Store::where('api_key', $apiKey)->where('is_active', true)->first();
        if ($store) {
            if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1' && $store->user->role !== 'admin') {
                return response()->json(['valid' => false, 'message' => 'Merchant system is disabled.'], 403);
            }
            return response()->json([
                'valid' => true,
                'type' => 'store',
                'name' => $store->name,
                'domain' => $store->domain,
                'merchant_name' => $store->user ? $store->user->name : null
            ], 200);
        }

        return response()->json([
            'valid' => false,
            'message' => 'Invalid or inactive API key'
        ], 401);
    }

    /**
     * Mobile login API to fetch the user API key.
     */
    public function apiLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (\Illuminate\Support\Facades\Auth::once($request->only('email', 'password'))) {
            $user = \Illuminate\Support\Facades\Auth::user();
            
            if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1' && $user->role !== 'admin') {
                return response()->json(['status' => 'error', 'message' => 'Merchant system is disabled. Only Admin is authorized.'], 403);
            }

            if (empty($user->sms_sync_key)) {
                $user->sms_sync_key = 'sync_' . \Illuminate\Support\Str::random(30);
                $user->save();
            }

            return response()->json([
                'success' => true,
                'status' => 'success',
                'api_key' => $user->sms_sync_key, // Send sms_sync_key as api_key for client compatibility
                'sms_sync_key' => $user->sms_sync_key,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'Invalid email or password credentials',
        ], 401);
    }
}
