<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotInstalled
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Bypass installation check during unit/feature testing unless testing the installer
        if (app()->runningUnitTests() && !$request->hasHeader('X-Test-Installer-Middleware')) {
            return $next($request);
        }

        $installed = file_exists(storage_path('installed'));

        if (!$installed) {
            // Redirect to installer if not visiting install routes
            if (!$request->is('install*')) {
                return redirect()->route('install.welcome');
            }
        } else {
            // If already installed, block accessing the installer
            if ($request->is('install*')) {
                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
