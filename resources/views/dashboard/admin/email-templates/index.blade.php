@extends('dashboard.layout')

@section('title', 'Email Templates')

@section('styles')
<style>
    .templates-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .templates-table th {
        text-align: left;
        padding: 15px 25px;
        color: var(--gray);
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--border);
    }
    .templates-table td {
        padding: 18px 25px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        font-size: 0.95rem;
    }
    .templates-table tr:last-child td {
        border-bottom: none;
    }
    .templates-table tr:hover {
        background-color: rgba(0, 0, 0, 0.015);
    }
    [data-theme="dark"] .templates-table tr:hover {
        background-color: rgba(255, 255, 255, 0.02);
    }
    .template-key {
        font-family: monospace;
        background-color: var(--primary-light);
        color: var(--primary-dark);
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    [data-theme="dark"] .template-key {
        background-color: rgba(99, 102, 241, 0.2);
        color: #818cf8;
    }
</style>
@endsection

@section('content')
<div class="top-nav">
    <div class="page-title">
        <h1>Email Templates</h1>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: 25px 25px 0 25px; margin-bottom: 10px;">
        <h3 style="font-size: 1.25rem; font-weight: 700;">Manage Email Templates</h3>
        <p style="color: var(--gray); font-size: 0.9rem; margin-top: 5px;">Customize the automated emails sent to merchants and customers.</p>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="templates-table">
            <thead>
                <tr>
                    <th>Template Name</th>
                    <th>System Key</th>
                    <th>Email Subject</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                <tr>
                    <td>
                        <strong style="color: var(--primary); font-size: 1rem;">{{ $template->name }}</strong>
                    </td>
                    <td>
                        <span class="template-key">{{ $template->key }}</span>
                    </td>
                    <td style="color: var(--gray);">{{ $template->subject }}</td>
                    <td style="text-align: right;">
                        <a href="{{ route('email-templates.edit', $template->id) }}" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem;">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 50px 20px; color: var(--gray);">
                        <i class="fa-solid fa-envelope-open-text" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p style="font-size: 1.1rem; font-weight: 500;">No email templates found.</p>
                        <p style="font-size: 0.9rem;">Run the database seeder to install the default templates.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
