<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $link->name }} | {{ $link->store->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: {{ $link->store->theme_color ?? '#6366f1' }};
            --primary-dark: {{ $link->store->theme_color ?? '#4f46e5' }};
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .payment-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 450px;
            padding: 40px 30px;
            text-align: center;
        }
        .store-name {
            font-size: 0.9rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .link-name {
            font-size: 1.5rem;
            font-weight: 800;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .description {
            color: #475569;
            font-size: 0.95rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 8px;
            color: #334155;
        }
        .form-control {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        @if($link->store->custom_css)
            {!! $link->store->custom_css !!}
        @endif
    </style>
</head>
<body>

    <div class="payment-card">
        <div class="store-name">{{ $link->store->name }}</div>
        <h1 class="link-name">{{ $link->name }}</h1>
        
        @if($link->description)
            <div class="description">{{ $link->description }}</div>
        @endif

        <form action="{{ route('payment-links.public.process', $link->identifier) }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label>Your Name</label>
                <input type="text" name="customer_name" class="form-control" required placeholder="John Doe">
            </div>

            <div class="form-group">
                <label>Your Email</label>
                <input type="email" name="customer_email" class="form-control" required placeholder="john@example.com">
            </div>

            <div class="form-group">
                <label>Amount ({{ $link->currency }})</label>
                @if($link->amount)
                    <input type="text" class="form-control" value="{{ number_format($link->amount, 2) }}" disabled style="background: #f1f5f9; font-weight: bold;">
                @else
                    <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00" min="1">
                @endif
            </div>

            <button type="submit" class="btn-submit">Proceed to Payment</button>
        </form>

        @if(!$link->store->hide_branding)
        <div style="margin-top: 30px; font-size: 0.8rem; color: #94a3b8;">
            Secured by OmniPay
        </div>
        @endif
    </div>

</body>
</html>
