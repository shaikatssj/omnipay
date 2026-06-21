@extends('dashboard.layout')

@section('page_title', 'Payment Links')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Payment Links & Buttons</h2>
    <button class="btn btn-primary" onclick="document.getElementById('createModal').style.display='flex'">
        <i class="fa-solid fa-plus"></i> Create Link
    </button>
</div>

<div class="card">
    @if($paymentLinks->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-link fa-3x mb-3"></i>
            <h5>No Payment Links Yet</h5>
            <p>Create a static link to share with customers or embed a buy button on your website.</p>
        </div>
    @else
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                    <th style="padding: 12px;">Name</th>
                    <th style="padding: 12px;">Store</th>
                    <th style="padding: 12px;">Amount</th>
                    <th style="padding: 12px;">Public URL</th>
                    <th style="padding: 12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paymentLinks as $link)
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 12px;"><strong>{{ $link->name }}</strong></td>
                    <td style="padding: 12px;">{{ $link->store->name }}</td>
                    <td style="padding: 12px;">
                        @if($link->amount)
                            {{ number_format($link->amount, 2) }} {{ $link->currency }}
                        @else
                            <em>Customer Decides</em>
                        @endif
                    </td>
                    <td style="padding: 12px;">
                        <a href="{{ route('payment-links.public.show', $link->identifier) }}" target="_blank" style="color: var(--primary); text-decoration: none;">
                            /pay/{{ Str::limit($link->identifier, 10) }}...
                        </a>
                    </td>
                    <td style="padding: 12px; display: flex; gap: 8px;">
                        <button class="btn btn-secondary btn-sm" onclick="showEmbedModal('{{ route('payment-links.public.show', $link->identifier) }}')" title="Get Embed Code">
                            <i class="fa-solid fa-code"></i> Embed
                        </button>
                        <form action="{{ route('payment-links.destroy', $link->id) }}" method="POST" onsubmit="return confirm('Delete this link?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top: 20px;">
            {{ $paymentLinks->links() }}
        </div>
    @endif
</div>

<!-- Create Modal -->
<div id="createModal" class="modal-backdrop" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="card" style="width: 90%; max-width: 500px; padding: 20px;">
        <h3 class="mb-4">Create Payment Link</h3>
        <form action="{{ route('payment-links.store') }}" method="POST">
            @csrf
            <div class="form-group mb-3">
                <label>Store</label>
                <select name="store_id" class="form-control" required>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-3">
                <label>Link Name (e.g. Monthly Donation)</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group mb-3">
                <label>Amount (Leave blank for user input)</label>
                <input type="number" step="0.01" name="amount" class="form-control">
            </div>
            <div class="form-group mb-3">
                <label>Currency</label>
                <input type="text" name="currency" class="form-control" value="BDT" required>
            </div>
            <div class="form-group mb-4">
                <label>Description (Optional)</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="d-flex justify-content-end" style="gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('createModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Link</button>
            </div>
        </form>
    </div>
</div>

<!-- Embed Modal -->
<div id="embedModal" class="modal-backdrop" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="card" style="width: 90%; max-width: 600px; padding: 20px;">
        <h3 class="mb-4">Embed "Buy Button"</h3>
        <p class="text-muted">Copy and paste this HTML snippet into your website (WordPress, Webflow, etc.) to show a payment button.</p>
        
        <textarea id="embedCode" class="form-control" rows="5" readonly style="font-family: monospace; font-size: 0.9rem; background: #f8fafc; cursor: pointer;"></textarea>
        
        <div class="d-flex justify-content-end mt-4">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('embedModal').style.display='none'">Close</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function showEmbedModal(url) {
        const snippet = `<a href="${url}" target="_blank" style="display: inline-block; background-color: #6366f1; color: #ffffff; padding: 12px 24px; font-family: sans-serif; font-weight: bold; text-decoration: none; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">Pay Now</a>`;
        document.getElementById('embedCode').value = snippet;
        document.getElementById('embedModal').style.display = 'flex';
    }

    // Auto-copy on click
    document.getElementById('embedCode').addEventListener('click', function() {
        this.select();
        document.execCommand('copy');
        alert('Copied to clipboard!');
    });
</script>
@endsection
