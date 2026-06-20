@extends('install.layout')

@section('title', 'OmniPay Installer - Installing')

@section('styles')
<style>
    .install-status-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin: 20px 0;
    }
    
    .status-step {
        display: flex;
        align-items: center;
        gap: 15px;
        font-weight: 600;
        font-size: 1rem;
        color: var(--gray);
        transition: var(--transition);
    }
    
    .status-step.active {
        color: var(--primary);
    }
    
    .status-step.completed {
        color: var(--success);
    }
    
    .status-step.failed {
        color: var(--danger);
    }
    
    .step-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: rgba(99, 102, 241, 0.04);
        border: 2px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    
    .status-step.active .step-icon {
        border-color: var(--primary);
        background: rgba(99, 102, 241, 0.08);
        color: var(--primary);
        animation: pulse 1.5s infinite;
    }
    
    .status-step.completed .step-icon {
        border-color: var(--success);
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    .status-step.failed .step-icon {
        border-color: var(--danger);
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
        70% { box-shadow: 0 0 0 8px rgba(99, 102, 241, 0); }
        100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
    }
    
    .progress-bar-container {
        width: 100%;
        height: 8px;
        background: var(--border);
        border-radius: 10px;
        overflow: hidden;
        margin-top: 10px;
        position: relative;
    }
    
    .progress-bar-fill {
        width: 0%;
        height: 100%;
        background: var(--primary);
        border-radius: 10px;
        transition: width 0.4s ease;
    }
</style>
@endsection

@section('progress')
<div class="progress-steps">
    <div class="progress-line"></div>
    <div class="progress-line-fill" style="width: 75%;"></div>
    
    <div class="step-node completed">
        <i class="fa-solid fa-check"></i>
        <span class="step-label">Prerequisites</span>
    </div>
    <div class="step-node completed">
        <i class="fa-solid fa-check"></i>
        <span class="step-label">Database</span>
    </div>
    <div class="step-node completed">
        <i class="fa-solid fa-check"></i>
        <span class="step-label">Admin Setup</span>
    </div>
    <div class="step-node active">
        4
        <span class="step-label">Installing</span>
    </div>
    <div class="step-node">
        5
        <span class="step-label">Finish</span>
    </div>
</div>
@endsection

@section('content')
<h1>Installing Application</h1>
<p class="subtitle">Please wait while OmniPay is being installed. We are updating configurations and preparing your database. Do not close or refresh this page.</p>

<div class="progress-bar-container">
    <div class="progress-bar-fill" id="progress-bar"></div>
</div>

<div class="install-status-list">
    <div class="status-step active" id="step-env">
        <div class="step-icon"><i class="fa-solid fa-gears"></i></div>
        <span>Writing configuration to env file</span>
    </div>
    
    <div class="status-step" id="step-migrations">
        <div class="step-icon"><i class="fa-solid fa-database"></i></div>
        <span>Running database migrations</span>
    </div>
    
    <div class="status-step" id="step-seeding">
        <div class="step-icon"><i class="fa-solid fa-seedling"></i></div>
        <span>Seeding default payment methods</span>
    </div>
    
    <div class="status-step" id="step-admin">
        <div class="step-icon"><i class="fa-solid fa-user-shield"></i></div>
        <span>Creating administrator account</span>
    </div>
    
    <div class="status-step" id="step-lock">
        <div class="step-icon"><i class="fa-solid fa-lock"></i></div>
        <span>Writing installation lock file</span>
    </div>
</div>

<div class="alert alert-danger" id="error-box" style="display: none; margin-top: 25px;">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span id="error-message"></span>
</div>

<div class="btn-wrapper" id="action-wrapper" style="display: none;">
    <div></div>
    <a href="{{ route('install.database') }}" class="btn btn-secondary">
        <i class="fa-solid fa-rotate-left"></i>
        <span>Configure Again</span>
    </a>
</div>
@endsection

@section('scripts')
<script>
    const progressBar = document.getElementById('progress-bar');
    const stepEnv = document.getElementById('step-env');
    const stepMigrations = document.getElementById('step-migrations');
    const stepSeeding = document.getElementById('step-seeding');
    const stepAdmin = document.getElementById('step-admin');
    const stepLock = document.getElementById('step-lock');
    const errorBox = document.getElementById('error-box');
    const errorMessage = document.getElementById('error-message');
    const actionWrapper = document.getElementById('action-wrapper');

    window.addEventListener('DOMContentLoaded', async () => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        try {
            // Animate step 1: Env configuration
            updateStep(stepEnv, 'active', 10);
            
            // Trigger AJAX install process
            const response = await fetch("{{ route('install.run-action') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json"
                }
            });
            
            const data = await response.json();
            
            if (response.ok) {
                // Complete all steps in UI
                updateStep(stepEnv, 'completed');
                updateStep(stepMigrations, 'completed', 30);
                updateStep(stepSeeding, 'completed', 65);
                updateStep(stepAdmin, 'completed', 85);
                updateStep(stepLock, 'completed', 100);
                
                // Redirect to finish page after a brief delay
                setTimeout(() => {
                    window.location.href = "{{ route('install.complete') }}";
                }, 1500);
            } else {
                handleFailure(data.message || 'Installation process encountered an error.');
            }
        } catch (error) {
            handleFailure(`Network error occurred: ${error.message}`);
        }
    });

    function updateStep(stepElement, status, progressVal) {
        // Reset classes
        stepElement.classList.remove('active', 'completed', 'failed');
        stepElement.classList.add(status);
        
        // Update icons
        const icon = stepElement.querySelector('.step-icon i');
        if (status === 'completed') {
            icon.className = 'fa-solid fa-check';
        } else if (status === 'failed') {
            icon.className = 'fa-solid fa-xmark';
        }
        
        // Update Progress Bar
        if (progressVal !== undefined) {
            progressBar.style.width = `${progressVal}%`;
        }
    }

    function handleFailure(message) {
        // Set active step to failed
        const activeStep = document.querySelector('.status-step.active');
        if (activeStep) {
            updateStep(activeStep, 'failed');
        }
        
        errorBox.style.display = 'flex';
        errorMessage.textContent = message;
        actionWrapper.style.display = 'flex';
    }
</script>
@endsection
