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
            --light: #fcfcfd;
            --gray: #64748b;
            --border-radius: 12px;
            --transition: all 0.2s ease-in-out;
            --bg-surface: #ffffff;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-float: 0 50px 100px -20px rgba(50,50,93,0.1), 0 30px 60px -30px rgba(0,0,0,0.1);
        }

        [data-theme="dark"] {
            --primary: #818cf8;
            --primary-dark: #6366f1;
            --primary-light: #312e81;
            --success: #34d399;
            --warning: #fbbf24;
            --danger: #f87171;
            --dark: #f8fafc;
            --light: #0b0f19;
            --gray: #94a3b8;
            --bg-surface: #111827;
            --border-color: #1f2937;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.5);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.4), 0 2px 4px -1px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.4);
            --shadow-float: 0 50px 100px -20px rgba(0,0,0,0.6), 0 30px 60px -30px rgba(0,0,0,0.5);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
            transition: background-color 0.3s ease;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .checkout-container {
            width: 100%;
            max-width: 1050px;
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-float);
            overflow: hidden;
            transition: var(--transition);
            position: relative;
        }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            top: 25px;
            right: 25px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 1rem;
            transition: var(--transition);
        }

        .theme-toggle:hover {
            color: var(--dark);
            box-shadow: var(--shadow-lg);
            transform: translateY(-1px);
        }

        /* Common Elements */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .badge-pending { background-color: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .badge-success { background-color: rgba(16, 185, 129, 0.1); color: var(--success); }
        .badge-danger { background-color: rgba(239, 68, 68, 0.1); color: var(--danger); }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 8px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background-color: transparent;
            border-color: var(--border-color);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background-color: var(--light);
            border-color: var(--gray);
        }

        /* Minimal Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
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
