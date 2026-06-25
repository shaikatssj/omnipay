<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;

class CheckStoreRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role = null): Response
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        $store = $request->route('store');
        
        if (!$store) {
            // For routes that don't have store parameter, we can't check store role directly here
            return $next($request);
        }

        if (is_string($store)) {
            $store = Store::findOrFail($store);
        }

        // Owner has full access
        if ($store->user_id === $user->id) {
            return $next($request);
        }

        // Check pivot role
        $staffUser = $store->staff()->where('user_id', $user->id)->first();
        
        if (!$staffUser) {
            abort(403, 'Unauthorized access to this store.');
        }

        if ($role) {
            $userRole = $staffUser->pivot->role;
            if ($role === 'manager' && $userRole !== 'manager') {
                abort(403, 'Requires manager role.');
            }
            if ($role === 'cashier' && !in_array($userRole, ['manager', 'cashier'])) {
                abort(403, 'Requires cashier role.');
            }
        }

        return $next($request);
    }
}
