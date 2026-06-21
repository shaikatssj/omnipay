@extends('dashboard.layout')

@section('page_title', 'Payment Links')

@section('styles')
<style>
    /* Header layout */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    /* Table styling */
    .table-container {
        overflow-x: auto;
    }
    
    .styled-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    
    .styled-table th, .styled-table td {
        padding: 15px 20px;
        border-bottom: 1px solid var(--border);
    }
    
    .styled-table th {
        color: var(--gray);
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: rgba(0,0,0,0.02);
    }
    
    [data-theme="dark"] .styled-table th {
        background-color: rgba(255,255,255,0.02);
    }
    
    .styled-table tr:hover {
        background-color: rgba(0,0,0,0.01);
    }
    
    [data-theme="dark"] .styled-table tr:hover {
        background-color: rgba(255,255,255,0.01);
    }

    /* Action buttons */
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        border: 1px solid transparent;
        text-decoration: none;
    }
    
    .action-btn-embed {
        background-color: rgba(99, 102, 241, 0.1);
        color: var(--primary);
        border-color: rgba(99, 102, 241, 0.2);
    }
    
    .action-btn-embed:hover {
        background-color: var(--primary);
        color: white;
    }
    
    .action-btn-delete {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border-color: rgba(239, 68, 68, 0.2);
    }
    
    .action-btn-delete:hover {
        background-color: var(--danger);
        color: white;
    }

    /* Forms inside Modals */
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
        border: 1px solid var(--border);
        background: rgba(255, 255, 255, 0.5);
        color: var(--dark);
        font-family: inherit;
        font-size: 0.95rem;
        outline: none;
        transition: var(--transition);
    }

    [data-theme="dark"] .form-control {
        background: rgba(0, 0, 0, 0.2);
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }
    
    .modal-backdrop {
        display: none; 
        position: fixed; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(15, 23, 42, 0.6); 
        backdrop-filter: blur(4px);
        z-index: 1000; 
        align-items: center; 
        justify-content: center;
    }
    
    .modal-content-box {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--border-radius);
        width: 90%; 
        max-width: 500px; 
        padding: 30px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    
    .modal-title {
        font-size: 1.3rem;
        font-weight: 800;
        margin-bottom: 20px;
        color: var(--dark);
    }

    .flex-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 10px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--gray);
    }
    
    .empty-state i {
        font-size: 3.5rem;
        color: var(--primary);
        opacity: 0.4;
        margin-bottom: 15px;
    }
    
    .empty-state h5 {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 8px;
    }
    
    .text-muted-small {
        font-size: 0.85rem;
        color: var(--gray);
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--dark);">Payment Links & Buttons</h2>
        <p style="font-size: 0.85rem; color: var(--gray); margin-top: 2px;">Create static URLs or embeddable buy buttons.</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('createModal').style.display='flex'">
        <i class="fa-solid fa-plus"></i> Create Link
    </button>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    @if($paymentLinks->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-link"></i>
            <h5>No Payment Links Yet</h5>
            <p>Create a static link to share with customers or embed a buy button on your website.</p>
        </div>
    @else
        <div class="table-container">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Store</th>
                        <th>Amount</th>
                        <th>Public URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentLinks as $link)
                    <tr>
                        <td><strong>{{ $link->name }}</strong></td>
                        <td>{{ $link->store->name }}</td>
                        <td>
                            @if($link->amount)
                                <span style="font-weight: 700; color: var(--primary);">{{ number_format($link->amount, 2) }} {{ $link->currency }}</span>
                            @else
                                <span style="color: var(--gray); font-style: italic; font-size: 0.85rem;">Customer Decides</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('payment-links.public.show', $link->identifier) }}" target="_blank" style="color: var(--primary); text-decoration: none; font-family: monospace; font-size: 0.9rem; background: rgba(99,102,241,0.08); padding: 4px 8px; border-radius: 6px;">
                                /pay/{{ Str::limit($link->identifier, 10) }}...
                            </a>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="action-btn action-btn-embed" onclick="showEmbedModal('{{ route('payment-links.public.show', $link->identifier) }}')" title="Get Embed Code">
                                    <i class="fa-solid fa-code"></i> Embed
                                </button>
                                <form action="{{ route('payment-links.destroy', $link->id) }}" method="POST" onsubmit="return confirm('Delete this link?');" style="margin: 0;">
                                    @csrf @method('DELETE')
                                    <button class="action-btn action-btn-delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($paymentLinks->hasPages())
        <div style="padding: 20px; border-top: 1px solid var(--border);">
            {{ $paymentLinks->links() }}
        </div>
        @endif
    @endif
</div>

<!-- Create Modal -->
<div id="createModal" class="modal-backdrop">
    <div class="modal-content-box">
        <h3 class="modal-title">Create Payment Link</h3>
        <form action="{{ route('payment-links.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Store</label>
                <select name="store_id" class="form-control" required>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Link Name (e.g. Monthly Donation)</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Amount (Leave blank for user input)</label>
                <input type="number" step="0.01" name="amount" class="form-control">
            </div>
            <div class="form-group">
                <label>Currency</label>
                <input type="text" name="currency" class="form-control" value="BDT" required>
            </div>
            <div class="form-group">
                <label>Description (Optional)</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="flex-actions">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('createModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Link</button>
            </div>
        </form>
    </div>
</div>

<!-- Embed Modal -->
<div id="embedModal" class="modal-backdrop">
    <div class="modal-content-box" style="max-width: 600px;">
        <h3 class="modal-title">Embed "Buy Button"</h3>
        <p class="text-muted-small" style="margin-bottom: 15px;">Copy and paste this HTML snippet into your website (WordPress, Webflow, etc.) to show a payment button.</p>
        
        <div class="form-group">
            <textarea id="embedCode" class="form-control" rows="5" readonly style="font-family: monospace; font-size: 0.85rem; cursor: pointer;"></textarea>
        </div>
        
        <div class="flex-actions">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('embedModal').style.display='none'">Close</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function showEmbedModal(url) {
        const snippet = `<a href="${url}" target="_blank" style="display: inline-block; background-color: #6366f1; color: #ffffff; padding: 12px 24px; font-family: sans-serif; font-weight: bold; text-decoration: none; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Pay Now</a>`;
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
