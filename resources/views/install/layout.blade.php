<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'OmniPay Installation Wizard')</title>
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            --body-bg: linear-gradient(135deg, #f5f7ff 0%, #e8ecf8 100%);
            --card-bg: rgba(255, 255, 255, 0.75);
            --border: #e2e8f0;
            --gray: #64748b;
            --border-radius: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow: 0 10px 30px -5px rgba(99, 102, 241, 0.08);
            --font-family: 'Outfit', sans-serif;
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
            --body-bg: radial-gradient(circle at top left, #0e1726, #07090e);
            --card-bg: rgba(15, 23, 42, 0.55);
            --border: rgba(255, 255, 255, 0.08);
            --gray: #94a3b8;
            --shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.5);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-family);
            background: var(--body-bg);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            transition: var(--transition);
        }

        .install-container {
            width: 100%;
            max-width: 680px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 40px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* Topbar Controls */
        .controls-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 850;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .theme-toggle-btn {
            background: rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.15);
            color: var(--primary);
            width: 42px;
            height: 42px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .theme-toggle-btn:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.05);
        }

        /* Step Progress Header */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin: 10px 0;
            padding: 0 10px;
        }

        .progress-line {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--border);
            z-index: 1;
            transform: translateY(-50%);
        }

        .progress-line-fill {
            position: absolute;
            top: 50%;
            left: 0;
            height: 3px;
            background: var(--primary);
            z-index: 2;
            transform: translateY(-50%);
            transition: var(--transition);
        }

        .step-node {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--light);
            border: 3px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 800;
            z-index: 3;
            color: var(--gray);
            transition: var(--transition);
            position: relative;
        }

        .step-node.active {
            border-color: var(--primary);
            color: var(--primary);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.25);
            background: var(--light);
        }

        .step-node.completed {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .step-label {
            position: absolute;
            top: 45px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
            color: var(--gray);
            letter-spacing: 0.3px;
        }

        .step-node.active .step-label {
            color: var(--primary);
        }

        /* Core Card Content */
        .install-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .install-content h1 {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .install-content p.subtitle {
            color: var(--gray);
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Forms Elements */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        label {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            color: var(--gray);
            font-size: 1rem;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 44px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.05);
            color: var(--dark);
            font-family: var(--font-family);
            font-size: 0.95rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            background: rgba(255, 255, 255, 0.1);
        }

        /* Button controls */
        .btn-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            gap: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary {
            background: rgba(99, 102, 241, 0.06);
            border: 1px solid var(--border);
            color: var(--dark);
        }

        .btn-secondary:hover:not(:disabled) {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }

        .btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        /* Alert and indicators */
        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 0.92rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            line-height: 1.5;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.15);
            color: var(--success);
        }

        /* Footer */
        .footer-credits {
            text-align: center;
            font-size: 0.8rem;
            color: var(--gray);
            font-weight: 600;
            margin-top: 10px;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="install-container">
        <!-- Topbar Controls -->
        <div class="controls-top">
            <div class="logo-area">
                <i class="fa-solid fa-layer-group"></i>
                <span>OmniPay</span>
            </div>
            <button class="theme-toggle-btn" id="theme-toggle" aria-label="Toggle Dark Mode">
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>

        <!-- Step Indicator -->
        @yield('progress')

        <!-- Main Card Content -->
        <div class="install-content">
            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @yield('content')
        </div>

        <!-- Footer -->
        <div class="footer-credits">
            <span>Powered by OmniPay Engine &bull; Self-Host Installer</span>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Light/Dark Theme Switcher
        const themeBtn = document.getElementById('theme-toggle');
        const themeIcon = themeBtn.querySelector('i');
        
        // Check local storage or defaults
        const currentTheme = localStorage.getItem('install-theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);

        themeBtn.addEventListener('click', () => {
            let theme = document.documentElement.getAttribute('data-theme');
            let newTheme = (theme === 'dark') ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('install-theme', newTheme);
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
