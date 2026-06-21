<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $link->name }} | {{ $link->store->name }}</title>
    <!-- Modern Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: {{ $link->store->theme_color ?? '#6366f1' }};
            /* Generate variations using color-mix for modern browsers */
            --primary-dark: color-mix(in srgb, var(--primary) 85%, black);
            --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
            --bg-color: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Abstract blobs */
        .blob-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, var(--primary) 0%, transparent 60%);
            opacity: 0.15;
            z-index: -1;
            filter: blur(60px);
            animation: float 10s ease-in-out infinite alternate;
        }

        .blob-2 {
            position: absolute;
            bottom: -15%;
            right: -10%;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, var(--primary-dark) 0%, transparent 60%);
            opacity: 0.1;
            z-index: -1;
            filter: blur(80px);
            animation: float 12s ease-in-out infinite alternate-reverse;
        }

        @keyframes float {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(30px) scale(1.05); }
        }

        .checkout-wrapper {
            width: 100%;
            max-width: 480px;
            perspective: 1000px;
            z-index: 10;
        }

        .payment-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.06), inset 0 0 0 1px rgba(255,255,255,0.8);
            padding: 45px 35px;
            text-align: center;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) rotateX(10deg); }
            to { opacity: 1; transform: translateY(0) rotateX(0); }
        }

        .store-avatar {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px var(--primary-light);
        }

        .store-name {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .link-name {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 15px;
            line-height: 1.2;
            color: var(--text-main);
        }

        .description {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 35px;
            line-height: 1.6;
        }

        .form-group {
            text-align: left;
            margin-bottom: 22px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 8px;
            color: var(--text-main);
            margin-left: 4px;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid transparent;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 14px;
            font-size: 1rem;
            color: var(--text-main);
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .form-control:focus {
            outline: none;
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .amount-display {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid var(--primary-light);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }

        .amount-display .currency {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 700;
        }

        .amount-display .value {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -1px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 18px;
            width: 100%;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 20px var(--primary-light);
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px var(--primary-light);
        }

        .branding-footer {
            margin-top: 35px;
            font-size: 0.85rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 500;
        }

        .branding-footer i {
            color: var(--primary);
        }

        @if($link->store->custom_css)
            {!! $link->store->custom_css !!}
        @endif
    </style>
</head>
<body>

    <!-- Abstract Backgrounds -->
    <div class="blob-1"></div>
    <div class="blob-2"></div>

    <div class="checkout-wrapper">
        <div class="payment-card">
            
            <div class="store-avatar">
                {{ strtoupper(substr($link->store->name, 0, 1)) }}
            </div>
            
            <div class="store-name">Payment Request from {{ $link->store->name }}</div>
            <h1 class="link-name">{{ $link->name }}</h1>
            
            @if($link->description)
                <div class="description">{{ $link->description }}</div>
            @endif

            <form action="{{ route('payment-links.public.process', $link->identifier) }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label>Your Name</label>
                    <input type="text" name="customer_name" class="form-control" required placeholder="E.g. John Doe">
                </div>

                <div class="form-group">
                    <label>Your Email</label>
                    <input type="email" name="customer_email" class="form-control" required placeholder="john@example.com">
                </div>

                @if($link->amount)
                    <div class="amount-display">
                        <span class="currency">{{ $link->currency }}</span>
                        <span class="value">{{ number_format($link->amount, 2) }}</span>
                    </div>
                @else
                    <div class="form-group">
                        <label>Amount to Pay ({{ $link->currency }})</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00" min="1" style="font-size: 1.5rem; font-weight: 700; padding: 20px;">
                    </div>
                @endif

                <button type="submit" class="btn-submit">
                    Continue to Payment <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            @if(!$link->store->hide_branding)
            <div class="branding-footer">
                <i class="fa-solid fa-shield-halved"></i> Secured by OmniPay
            </div>
            @endif
        </div>
    </div>

</body>
</html>
