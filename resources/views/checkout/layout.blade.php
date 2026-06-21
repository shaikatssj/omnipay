<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'OmniPay Secure Checkout')</title>
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #e0e7ff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.08);
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-dark: #6366f1;
            --primary-light: #312e81;
            --success: #34d399;
            --warning: #fbbf24;
            --danger: #f87171;
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
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
            transition: background-color 0.5s ease;
            position: relative;
        }

        /* Abstract Background Gradients */
        body::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -10%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, transparent 70%);
            z-index: -1;
            filter: blur(50px);
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, transparent 70%);
            z-index: -1;
            filter: blur(50px);
        }

        .checkout-container {
            width: 100%;
            max-width: 780px;
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-top: 4px solid var(--primary);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
            z-index: 1;
        }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: var(--shadow);
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        /* Common Elements */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-pending {
            background-color: rgba(245, 158, 11, 0.15);
            color: var(--warning);
        }

        .badge-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }

        .badge-danger {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: transparent;
            border: 1px solid var(--gray);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* Custom Animations */
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animated-fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
    </style>
    @yield('styles')
    @if(isset($invoice) && isset($invoice->store))
        @if($invoice->store->theme_color)
            <style>
                :root {
                    --primary: {{ $invoice->store->theme_color }};
                    --primary-dark: {{ $invoice->store->theme_color }}; /* simplified for theme injection */
                }
                [data-theme="dark"] {
                    --primary: {{ $invoice->store->theme_color }};
                    --primary-dark: {{ $invoice->store->theme_color }};
                }
            </style>
        @endif
        @if($invoice->store->custom_css)
            <style>
                {!! $invoice->store->custom_css !!}
            </style>
        @endif
    @endif
</head>
<body>
    <button class="theme-toggle" id="themeToggleBtn" aria-label="Toggle Theme">
        <i class="fa-solid fa-moon" id="themeIcon"></i>
    </button>

    <div class="checkout-container animated-fade-in">
        @yield('content')
    </div>

    <script>
        // Light/Dark Theme Switching Logic
        const themeBtn = document.getElementById('themeToggleBtn');
        const themeIcon = document.getElementById('themeIcon');
        
        // Load saved theme
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);

        themeBtn.addEventListener('click', () => {
            const activeTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = activeTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'fa-solid fa-sun';
            } else {
                themeIcon.className = 'fa-solid fa-moon';
            }
        }
    </script>
    @yield('scripts')
</body>
</html>
