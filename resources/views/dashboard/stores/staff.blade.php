@extends('dashboard.layout')

@section('page_title', 'Store Staff: ' . $store->name)

@section('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .table-container {
        border-radius: var(--border-radius);
        overflow: hidden;
        background: var(--card-bg);
        border: 1px solid var(--border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }

    .styled-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        white-space: nowrap;
    }
    .styled-table th, .styled-table td {
        padding: 18px 24px;
        border-bottom: 1px solid var(--border);
        font-size: 0.9rem;
    }
    .styled-table th {
        color: var(--gray);
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background-color: rgba(0,0,0,0.02);
    }
    [data-theme="dark"] .styled-table th {
        background-color: rgba(255,255,255,0.02);
    }
    .styled-table tr {
        transition: background-color 0.2s ease;
    }
    .styled-table tr:hover {
        background-color: rgba(99, 102, 241, 0.03);
    }
    [data-theme="dark"] .styled-table tr:hover {
        background-color: rgba(99, 102, 241, 0.1);
    }
    
    .role-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: inline-block;
    }
    .role-manager { background: rgba(99, 102, 241, 0.15); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.2); }
    .role-cashier { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
    .role-accountant { background: rgba(245, 158, 11, 0.15); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.2); }

    /* Modal Styling */
    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .modal-backdrop.show {
        opacity: 1;
    }

    .modal-content {
        background: var(--light);
        padding: 40px;
        border-radius: 24px;
        width: 90%;
        max-width: 480px;
        border: 1px solid var(--border);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: translateY(20px) scale(0.95);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    [data-theme="dark"] .modal-content {
        background: var(--dark);
    }

    .modal-backdrop.show .modal-content {
        transform: translateY(0) scale(1);
    }

    .modal-header {
        margin-bottom: 25px;
    }

    .modal-header h3 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 8px;
    }
    
    [data-theme="dark"] .modal-header h3 {
        color: var(--light);
    }

    .modal-header p {
        color: var(--gray);
        font-size: 0.9rem;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--dark);
    }
    
    [data-theme="dark"] .form-label {
        color: var(--light);
    }

    .form-input, .form-select-custom {
        width: 100%;
        padding: 12px 16px;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: var(--sidebar-bg);
        color: var(--dark);
        font-family: inherit;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        outline: none;
    }
    
    [data-theme="dark"] .form-input, [data-theme="dark"] .form-select-custom {
        color: var(--light);
    }

    .form-input:focus, .form-select-custom:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 30px;
    }
    
    .modal-actions .btn {
        padding: 12px 24px;
        font-size: 0.95rem;
        border-radius: 12px;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800;">Staff Management</h2>
        <p style="font-size: 0.9rem; color: var(--gray); margin-top: 5px;">Manage who has access to this store.</p>
    </div>
    @if(Auth::user()->id === $store->user_id || Auth::user()->role === 'admin' || (Auth::user()->staffStores->where('id', $store->id)->first()?->pivot->role === 'manager'))
    <button class="btn btn-primary" onclick="openModal()" style="padding: 12px 24px; font-size: 0.95rem; border-radius: 12px;">
        <i class="fa-solid fa-user-plus"></i> Add Staff
    </button>
    @endif
</div>

<div class="table-container">
    <div style="overflow-x: auto;">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Added On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Owner -->
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 12px; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem;">
                                {{ strtoupper(substr($store->user->name, 0, 2)) }}
                            </div>
                            <strong style="font-size: 0.95rem;">{{ $store->user->name }}</strong>
                        </div>
                    </td>
                    <td style="color: var(--gray);">{{ $store->user->email }}</td>
                    <td><span class="role-badge role-manager">Owner</span></td>
                    <td style="color: var(--gray); font-style: italic;">-</td>
                    <td style="color: var(--gray); font-style: italic;">-</td>
                </tr>
                
                @foreach($staff as $member)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 12px; background: var(--gray); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem; opacity: 0.8;">
                                {{ strtoupper(substr($member->name, 0, 2)) }}
                            </div>
                            <strong style="font-size: 0.95rem;">{{ $member->name }}</strong>
                        </div>
                    </td>
                    <td style="color: var(--gray);">{{ $member->email }}</td>
                    <td><span class="role-badge role-{{ $member->pivot->role }}">{{ ucfirst($member->pivot->role) }}</span></td>
                    <td style="color: var(--gray);">{{ $member->pivot->created_at->format('M d, Y') }}</td>
                    <td>
                        @if(Auth::user()->id === $store->user_id || Auth::user()->role === 'admin' || (Auth::user()->staffStores->where('id', $store->id)->first()?->pivot->role === 'manager'))
                        <form action="{{ route('stores.staff.remove', [$store->id, $member->id]) }}" method="POST" onsubmit="return confirm('Remove this staff member?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm" style="background: transparent; color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 6px 12px; transition: all 0.2s;">
                                <i class="fa-solid fa-user-minus"></i> Remove
                            </button>
                        </form>
                        @else
                        <span style="color: var(--gray); font-size: 0.85rem;">No Access</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal-backdrop">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Staff Member</h3>
            <p>Invite an existing OmniPay user to manage this store.</p>
        </div>
        <form action="{{ route('stores.staff.add', $store->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">User Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="e.g. staff@example.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Assign Role</label>
                <div style="position: relative;">
                    <select name="role" class="form-select-custom" required appearance="none" style="-webkit-appearance: none; appearance: none;">
                        <option value="manager">Manager (Can manage staff & API keys)</option>
                        <option value="cashier">Cashier (Can create & refund invoices)</option>
                        <option value="accountant">Accountant (Read-only analytics & logs)</option>
                    </select>
                    <div style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--gray);">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send Invite</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const modal = document.getElementById('addModal');
    
    function openModal() {
        modal.style.display = 'flex';
        // Small delay to allow display block to apply before adding class for transition
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }
    
    function closeModal() {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300); // Matches transition duration
    }
    
    // Close on click outside
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>
@endsection
