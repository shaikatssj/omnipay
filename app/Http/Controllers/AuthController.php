<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // 1. Smart Captcha Validation
        if (\App\Models\Setting::get('captcha_enabled', '0') === '1') {
            $request->validate([
                'captcha_answer' => 'required',
            ]);

            if (session('captcha_result') === null || intval($request->input('captcha_answer')) !== session('captcha_result')) {
                return back()->withErrors([
                    'email' => 'Security Verification failed. Incorrect captcha answer.',
                ])->onlyInput('email');
            }
        }

        // 2. Brute Force Protection (Login Throttling)
        $throttleKey = \Illuminate\Support\Str::lower($request->input('email')) . '|' . $request->ip();

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
            ])->onlyInput('email');
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::validate($credentials)) {
            $user = User::where('email', $credentials['email'])->first();
            
            if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1' && $user->role !== 'admin') {
                \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);
                return back()->withErrors([
                    'email' => 'Merchant system is disabled. Only Administrator login is allowed.',
                ])->onlyInput('email');
            }

            // Clear Rate Limiter on successful credentials
            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

            // 3. Two Factor Authentication Interception
            if ($user->two_factor_method !== 'none') {
                session([
                    '2fa:user_id' => $user->id,
                    '2fa:remember' => $request->has('remember')
                ]);

                if ($user->two_factor_method === 'email') {
                    $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                    $user->update([
                        'two_factor_code' => $code,
                        'two_factor_expires_at' => now()->addMinutes(10),
                    ]);

                    \App\Services\MailNotificationService::send2faCode($user, $code);
                }

                return redirect()->route('auth.2fa');
            }

            // Normal login
            Auth::login($user, $request->has('remember'));
            $request->session()->regenerate();

            // Send login notification
            \App\Services\MailNotificationService::sendLoginNotification($user, $request->ip());

            ActivityLog::log('login', 'User logged in successfully');
            return redirect()->intended(route('dashboard'));
        }

        // Increment Rate Limiter on failed attempt
        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function show2fa()
    {
        if (!session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        $user = User::find(session('2fa:user_id'));
        if (!$user) {
            return redirect()->route('login');
        }

        return view('auth.2fa', [
            'method' => $user->two_factor_method,
            'email' => $user->email
        ]);
    }

    public function verify2fa(Request $request)
    {
        if (!session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        $user = User::find(session('2fa:user_id'));
        if (!$user) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $code = trim($request->code);

        if ($user->two_factor_method === 'email') {
            if ($user->two_factor_code === $code && $user->two_factor_expires_at && $user->two_factor_expires_at->isFuture()) {
                $user->update([
                    'two_factor_code' => null,
                    'two_factor_expires_at' => null,
                ]);

                Auth::login($user, session('2fa:remember', false));
                session()->forget(['2fa:user_id', '2fa:remember']);
                $request->session()->regenerate();

                \App\Services\MailNotificationService::sendLoginNotification($user, $request->ip());
                ActivityLog::log('login_2fa', 'User logged in successfully via Email 2FA');
                return redirect()->intended(route('dashboard'));
            }
        } elseif ($user->two_factor_method === 'totp') {
            if (\App\Services\TotpService::verifyCode($user->two_factor_secret, $code)) {
                Auth::login($user, session('2fa:remember', false));
                session()->forget(['2fa:user_id', '2fa:remember']);
                $request->session()->regenerate();

                \App\Services\MailNotificationService::sendLoginNotification($user, $request->ip());
                ActivityLog::log('login_2fa', 'User logged in successfully via Authenticator 2FA');
                return redirect()->intended(route('dashboard'));
            }
        }

        return back()->withErrors([
            'code' => 'The provided verification code is invalid or has expired.',
        ]);
    }

    public function showRegister()
    {
        if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1') {
            return redirect()->route('login')->withErrors(['email' => 'Registration is currently disabled.']);
        }
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        if (\App\Models\Setting::get('merchant_system_enabled', '1') !== '1') {
            return redirect()->route('login')->withErrors(['email' => 'Registration is currently disabled.']);
        }

        // 1. Smart Captcha Validation
        if (\App\Models\Setting::get('captcha_enabled', '0') === '1') {
            $request->validate([
                'captcha_answer' => 'required',
            ]);

            if (session('captcha_result') === null || intval($request->input('captcha_answer')) !== session('captcha_result')) {
                return back()->withErrors([
                    'name' => 'Security Verification failed. Incorrect captcha answer.',
                ])->withInput();
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'merchant',
            'api_key' => 'usr_' . Str::random(30), // auto generate user level API key
        ]);

        Auth::login($user);

        // Send login notification
        \App\Services\MailNotificationService::sendLoginNotification($user, $request->ip());

        ActivityLog::log('register', 'User registered a new merchant account');

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        ActivityLog::log('logout', 'User logged out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
