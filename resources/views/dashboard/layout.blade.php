<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'OmniPay Dashboard')</title>
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
            --sidebar-bg: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.7);
            --border: #e2e8f0;
            --gray: #64748b;
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.05);
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
            --sidebar-bg: #0f172a;
            --card-bg: rgba(15, 23, 42, 0.6);
            --border: rgba(255, 255, 255, 0.08);
            --gray: #94a3b8;
            --shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.3);
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
            transition: var(--transition);
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 25px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            transition: var(--transition);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 40px;
        }

        .menu-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-grow: 1;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            color: var(--gray);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.92rem;
            border-radius: 12px;
            transition: var(--transition);
        }

        .menu-item.active a, .menu-item a:hover {
            background-color: var(--primary-light);
            color: var(--primary);
        }

        [data-theme="dark"] .menu-item.active a {
            background-color: var(--primary-light);
            color: #ffffff;
        }

        .sidebar-footer {
            border-top: 1px solid var(--border);
            padding-top: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .user-details h4 {
            font-size: 0.88rem;
            font-weight: 600;
        }

        .user-details span {
            font-size: 0.75rem;
            color: var(--gray);
            text-transform: capitalize;
        }

        .logout-btn {
            background: none;
            border: none;
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 10px;
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
            border-radius: 8px;
        }

        .logout-btn:hover {
            background-color: rgba(239, 68, 68, 0.1);
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            flex-grow: 1;
            padding: 40px;
            transition: var(--transition);
            min-width: 0;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }

        .page-title h1 {
            font-size: 1.6rem;
            font-weight: 800;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Utilities */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 0.88rem;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: transparent;
            border: 1px solid var(--border);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background-color: rgba(0,0,0,0.05);
        }

        [data-theme="dark"] .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* Theme Toggle */
        .theme-toggle-btn {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            color: var(--dark);
            font-size: 1rem;
            transition: var(--transition);
        }

        .theme-toggle-btn:hover {
            transform: scale(1.05);
        }

        /* Grid */
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.12);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.12);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-warning {
            background-color: rgba(245, 158, 11, 0.12);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .sidebar-toggle-btn {
            display: none;
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            color: var(--dark);
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .sidebar-toggle-btn:hover {
            transform: scale(1.05);
        }

        /* Responsive */
        @media(max-width: 900px) {
            .sidebar-toggle-btn {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
                box-shadow: 5px 0 25px rgba(0,0,0,0.15);
            }
            [data-theme="dark"] .sidebar.active {
                box-shadow: 5px 0 25px rgba(0,0,0,0.4);
            }
            .main-content {
                margin-left: 0;
                padding: 25px;
            }
            .top-nav {
                margin-top: 40px;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="dashboardSidebar">
        <div class="logo-section">
            <i class="fa-solid fa-cloud-bolt"></i>
            <span>OmniPay</span>
        </div>
        
        <ul class="menu-list">
            <li class="menu-item @if(Route::is('dashboard')) active @endif">
                <a href="{{ route('dashboard') }}">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="menu-item @if(Route::is('stores.index') || Route::is('stores.create') || Route::is('stores.edit') || Route::is('stores.configs.edit')) active @endif">
                <a href="{{ route('stores.index') }}">
                    <i class="fa-solid fa-shop"></i> My Stores
                </a>
            </li>
            <li class="menu-item @if(Route::is('dashboard.invoices')) active @endif">
                <a href="{{ route('dashboard.invoices') }}">
                    <i class="fa-solid fa-receipt"></i> Invoices
                </a>
            </li>
            <li class="menu-item @if(Route::is('dashboard.qr')) active @endif">
                <a href="{{ route('dashboard.qr') }}">
                    <i class="fa-solid fa-qrcode"></i> QR Manager
                </a>
            </li>
            <li class="menu-item @if(Route::is('dashboard.docs')) active @endif">
                <a href="{{ route('dashboard.docs') }}">
                    <i class="fa-solid fa-book"></i> API Documentation
                </a>
            </li>
            <li class="menu-item @if(Route::is('dashboard.api-logs')) active @endif">
                <a href="{{ route('dashboard.api-logs') }}">
                    <i class="fa-solid fa-list-check"></i> API Logs
                </a>
            </li>
            <li class="menu-item @if(Route::is('dashboard.activity-logs')) active @endif">
                <a href="{{ route('dashboard.activity-logs') }}">
                    <i class="fa-solid fa-clock-rotate-left"></i> Activity Logs
                </a>
            </li>
            <li class="menu-item @if(Route::is('settings.security')) active @endif">
                <a href="{{ route('settings.security') }}">
                    <i class="fa-solid fa-shield-halved"></i> Security & Email
                </a>
            </li>
            
            @if(Auth::user()->role === 'admin')
                <li class="menu-item @if(Route::is('admin.gateways')) active @endif">
                    <a href="{{ route('admin.gateways') }}">
                        <i class="fa-solid fa-gears"></i> Core Gateways
                    </a>
                </li>
            @endif
        </ul>

        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="avatar">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <div class="user-details">
                    <h4>{{ Auth::user()->name }}</h4>
                    <span>{{ Auth::user()->role }}</span>
                </div>
            </div>
            
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Section -->
    <div class="main-content">
        <div class="top-nav">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="sidebar-toggle-btn" id="sidebarToggler" aria-label="Toggle Sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="page-title">
                    <h1>@yield('page_title', 'Dashboard')</h1>
                </div>
            </div>
            <div class="top-actions">
                <button class="theme-toggle-btn" id="themeToggler" aria-label="Toggle Theme">
                    <i class="fa-solid fa-moon" id="themeTogglerIcon"></i>
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning">
                {{ session('warning') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </div>

    <script>
        const btnToggle = document.getElementById('themeToggler');
        const iconToggle = document.getElementById('themeTogglerIcon');
        
        // Match base layout theme
        const activeTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', activeTheme);
        updateIcon(activeTheme);

        btnToggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const target = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', target);
            localStorage.setItem('theme', target);
            updateIcon(target);
        });

        function updateIcon(theme) {
            if (theme === 'dark') {
                iconToggle.className = 'fa-solid fa-sun';
            } else {
                iconToggle.className = 'fa-solid fa-moon';
            }
        }

        // Mobile Sidebar toggler
        const sidebarToggler = document.getElementById('sidebarToggler');
        const dashboardSidebar = document.getElementById('dashboardSidebar');
        
        if (sidebarToggler && dashboardSidebar) {
            sidebarToggler.addEventListener('click', (e) => {
                e.stopPropagation();
                dashboardSidebar.classList.toggle('active');
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 900) {
                    if (dashboardSidebar.classList.contains('active') && !dashboardSidebar.contains(e.target) && e.target !== sidebarToggler) {
                        dashboardSidebar.classList.remove('active');
                    }
                }
            });
        }
    </script>
    @yield('scripts')
</body>
</html>
