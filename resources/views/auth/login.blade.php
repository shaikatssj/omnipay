<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | OmniPay</title>
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.08);
            --border-radius: 16px;
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-dark: #6366f1;
            --dark: #f8fafc;
            --light: #090d16;
            --gray: #94a3b8;
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-border: rgba(255, 255, 255, 0.08);
            --shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -10%;
            left: -10%;
            width: 50%;
            height: 60%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
            z-index: -1;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 40px;
            z-index: 1;
        }

        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray);
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.5);
            color: var(--dark);
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s;
        }

        [data-theme="dark"] .form-control {
            background: rgba(0, 0, 0, 0.2);
        }

        .form-control:focus {
            border-color: var(--primary);
        }

        .btn {
            width: 100%;
            padding: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            background-color: var(--primary);
            color: white;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .auth-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 0.85rem;
            color: var(--gray);
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .error-box {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            list-style: none;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo-section">
            <i class="fa-solid fa-cloud-bolt"></i>
            <span>OmniPay</span>
        </div>

        @if($errors->any())
            <ul class="error-box">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required autofocus value="{{ old('email') }}">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            @if(\App\Models\Setting::get('captcha_enabled', '0') === '1')
                <div class="form-group">
                    <label for="captcha">Security Verification</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        {!! \App\Services\CaptchaService::generateCaptcha() !!}
                        <input type="text" name="captcha_answer" id="captcha" class="form-control" placeholder="Answer" required style="flex: 1; text-align: center;">
                    </div>
                </div>
            @endif

            <button type="submit" class="btn">Sign In</button>
        </form>

        @if(\App\Models\Setting::get('merchant_system_enabled', '1') === '1')
            <div class="auth-footer">
                Don't have an account? <a href="{{ route('register') }}">Sign Up</a>
            </div>
        @else
            <div class="auth-footer" style="opacity: 0.65;">
                Registration is disabled. Only Admin accounts can log in.
            </div>
        @endif
    </div>
    
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html>
