@extends('dashboard.layout')

@section('title', 'Edit Email Template')

@section('styles')
<style>
    .edit-grid {
        display: grid;
        grid-template-columns: 2.5fr 1fr;
        gap: 25px;
        align-items: start;
    }
    
    @media(max-width: 900px) {
        .edit-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--dark);
    }
    
    .form-control {
        width: 100%;
        padding: 15px 18px;
        background: transparent;
        border: 1px solid var(--border);
        border-radius: 12px;
        color: var(--dark);
        font-family: inherit;
        font-size: 0.95rem;
        transition: var(--transition);
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.02);
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-light);
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 350px;
        font-family: 'Courier New', Courier, monospace;
        line-height: 1.6;
        background-color: rgba(0,0,0,0.015);
    }
    
    [data-theme="dark"] textarea.form-control {
        background-color: rgba(255,255,255,0.015);
    }
    
    .invalid-feedback {
        color: var(--danger);
        font-size: 0.85rem;
        margin-top: 6px;
        display: block;
    }
    
    .variables-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 20px;
    }
    
    .var-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        background: rgba(99, 102, 241, 0.05);
        border-radius: 10px;
        border: 1px dashed rgba(99, 102, 241, 0.3);
    }
    
    .var-code {
        font-family: monospace;
        color: var(--primary-dark);
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    [data-theme="dark"] .var-code {
        color: #818cf8;
    }
    
    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 5px;
    }
    
    .card-subtitle {
        color: var(--gray);
        font-size: 0.9rem;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border);
    }
</style>
@endsection

@section('content')
<div class="top-nav">
    <div class="page-title">
        <h1>Edit Email Template</h1>
    </div>
    <div class="top-actions">
        <a href="{{ route('email-templates.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Templates
        </a>
    </div>
</div>

<div class="edit-grid">
    <div class="card">
        <h3 class="card-title">{{ $emailTemplate->name }}</h3>
        <div class="card-subtitle">Update the dynamic subject and body content for this email.</div>
        
        <form action="{{ route('email-templates.update', $emailTemplate->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="subject" class="form-label">Email Subject <span style="color: var(--danger);">*</span></label>
                <input type="text" name="subject" id="subject" class="form-control" value="{{ old('subject', $emailTemplate->subject) }}" required>
                @error('subject')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="body" class="form-label">Email Body (HTML supported) <span style="color: var(--danger);">*</span></label>
                <textarea name="body" id="body" class="form-control" required>{{ old('body', $emailTemplate->body) }}</textarea>
                @error('body')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-top: 35px; display: flex; gap: 15px;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 25px; font-size: 1rem;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <div class="card" style="background: linear-gradient(145deg, var(--card-bg), rgba(99, 102, 241, 0.02)); border: 1px solid rgba(99, 102, 241, 0.15);">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 10px; color: var(--dark);">
            <i class="fas fa-code" style="color: var(--primary); margin-right: 8px;"></i> Available Variables
        </h3>
        <p style="color: var(--gray); font-size: 0.9rem; line-height: 1.5;">
            You can use these dynamic variables inside both the subject and the body. The system will automatically replace them when sending the email to the user.
        </p>
        
        <div class="variables-list">
            @if($emailTemplate->variables && is_array($emailTemplate->variables))
                @foreach($emailTemplate->variables as $var)
                    <div class="var-item">
                        <span class="var-code">{{ '{' . $var . '}' }}</span>
                    </div>
                @endforeach
            @else
                <div class="var-item" style="justify-content: center; border-style: solid; background: transparent; border-color: var(--border);">
                    <span style="color: var(--gray); font-size: 0.9rem;">No variables available</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
