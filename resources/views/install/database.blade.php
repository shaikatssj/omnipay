@extends('install.layout')

@section('title', 'OmniPay Installer - Database Setup')

@section('styles')
<style>
    .db-status-container {
        margin: 10px 0 20px 0;
        display: none;
    }
    
    .spinner {
        display: inline-block;
        width: 18px;
        height: 18px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endsection

@section('progress')
<div class="progress-steps">
    <div class="progress-line"></div>
    <div class="progress-line-fill" style="width: 25%;"></div>
    
    <div class="step-node completed">
        <i class="fa-solid fa-check"></i>
        <span class="step-label">Prerequisites</span>
    </div>
    <div class="step-node active">
        2
        <span class="step-label">Database</span>
    </div>
    <div class="step-node">
        3
        <span class="step-label">Admin Setup</span>
    </div>
    <div class="step-node">
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
<h1>Database Connection</h1>
<p class="subtitle">Specify your database connection settings. You can override the default MySQL port if your environment runs on a different port (such as 3307).</p>

<form action="{{ route('install.database.save') }}" method="POST" id="db-form">
    @csrf
    
    <div class="form-row">
        <!-- DB Host -->
        <div class="form-group">
            <label for="host">Database Host</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-server"></i>
                <input type="text" id="host" name="host" class="form-control" value="{{ old('host', $defaults['host']) }}" required placeholder="127.0.0.1">
            </div>
        </div>

        <!-- DB Port (Override support) -->
        <div class="form-group">
            <label for="port">Database Port</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-plug"></i>
                <input type="text" id="port" name="port" class="form-control" value="{{ old('port', $defaults['port']) }}" required placeholder="3306">
            </div>
        </div>
    </div>

    <div class="form-row">
        <!-- DB Name -->
        <div class="form-group">
            <label for="database">Database Name</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-database"></i>
                <input type="text" id="database" name="database" class="form-control" value="{{ old('database', $defaults['database']) }}" required placeholder="omnipay">
            </div>
        </div>

        <!-- DB Username -->
        <div class="form-group">
            <label for="username">Username</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-user"></i>
                <input type="text" id="username" name="username" class="form-control" value="{{ old('username', $defaults['username']) }}" required placeholder="root">
            </div>
        </div>
    </div>

    <!-- DB Password -->
    <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrapper">
            <i class="fa-solid fa-key"></i>
            <input type="password" id="password" name="password" class="form-control" value="{{ old('password', $defaults['password']) }}" placeholder="Leave empty if none">
        </div>
    </div>

    <!-- AJAX Connection Test Feedback Box -->
    <div class="db-status-container" id="status-box"></div>

    <div class="btn-wrapper">
        <button type="button" class="btn btn-secondary" id="test-connection-btn">
            <span id="test-btn-text"><i class="fa-solid fa-circle-nodes"></i> Test Connection</span>
        </button>
        
        <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
            <span>Next Step</span>
            <i class="fa-solid fa-arrow-right"></i>
        </button>
    </div>
</form>
@endsection

@section('scripts')
<script>
    const testBtn = document.getElementById('test-connection-btn');
    const testBtnText = document.getElementById('test-btn-text');
    const submitBtn = document.getElementById('submit-btn');
    const statusBox = document.getElementById('status-box');

    testBtn.addEventListener('click', async () => {
        // Toggle loading status
        testBtn.disabled = true;
        testBtnText.innerHTML = '<span class="spinner"></span> Testing...';
        statusBox.style.display = 'none';

        const host = document.getElementById('host').value;
        const port = document.getElementById('port').value;
        const database = document.getElementById('database').value;
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const response = await fetch("{{ route('install.database.test') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({ host, port, database, username, password })
            });

            const data = await response.json();
            
            if (response.ok) {
                statusBox.className = 'db-status-container alert alert-success';
                statusBox.innerHTML = `<i class="fa-solid fa-circle-check"></i> <span>${data.message}</span>`;
                statusBox.style.display = 'flex';
                submitBtn.disabled = false;
            } else {
                statusBox.className = 'db-status-container alert alert-danger';
                statusBox.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> <span>${data.message || 'Connection test failed.'}</span>`;
                statusBox.style.display = 'flex';
                submitBtn.disabled = true;
            }
        } catch (error) {
            statusBox.className = 'db-status-container alert alert-danger';
            statusBox.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> <span>Network error: ${error.message}</span>`;
            statusBox.style.display = 'flex';
            submitBtn.disabled = true;
        } finally {
            testBtn.disabled = false;
            testBtnText.innerHTML = '<i class="fa-solid fa-circle-nodes"></i> Test Connection';
        }
    });

    // Disable continue button if any database connection inputs change
    ['host', 'port', 'database', 'username', 'password'].forEach(id => {
        document.getElementById(id).addEventListener('input', () => {
            submitBtn.disabled = true;
        });
    });
</script>
@endsection
