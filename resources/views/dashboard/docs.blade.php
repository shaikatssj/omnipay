@extends('dashboard.layout')

@section('page_title', 'Developer API Documentation')

@section('styles')
<style>
    .docs-layout {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    /* Sticky Navigation Topbar */
    .docs-topbar-nav {
        position: sticky;
        top: 0;
        z-index: 50;
        background: var(--card-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--border);
        border-radius: var(--border-radius);
        padding: 12px 18px;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    @media (max-width: 992px) {
        .docs-topbar-nav {
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding: 10px 14px;
        }
        .docs-topbar-nav::-webkit-scrollbar {
            display: none;
        }
    }

    .docs-nav-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--gray);
        text-decoration: none;
        transition: var(--transition);
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .docs-nav-link:hover {
        background: rgba(99, 102, 241, 0.05);
        color: var(--primary);
        transform: translateY(-1px);
    }

    .docs-nav-link.active {
        background: rgba(99, 102, 241, 0.08);
        color: var(--primary);
        border-color: rgba(99, 102, 241, 0.15);
    }

    /* Content Area */
    .docs-content {
        display: flex;
        flex-direction: column;
        gap: 35px;
        min-width: 0;
    }

    .docs-section {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--border-radius);
        padding: 35px;
        box-shadow: var(--shadow);
        scroll-margin-top: 110px;
    }

    .docs-section h2 {
        font-size: 1.4rem;
        font-weight: 800;
        margin-bottom: 18px;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 12px;
        letter-spacing: -0.3px;
    }

    .docs-section h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 28px 0 14px 0;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .docs-section p {
        font-size: 0.95rem;
        line-height: 1.65;
        color: var(--gray);
        margin-bottom: 18px;
    }

    .docs-section ul, .docs-section ol {
        margin-bottom: 18px;
        padding-left: 24px;
        color: var(--gray);
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .docs-section li {
        margin-bottom: 6px;
    }

    /* Endpoint Blocks */
    .endpoint-block {
        display: flex;
        align-items: center;
        background: rgba(99, 102, 241, 0.03);
        border: 1px dashed rgba(99, 102, 241, 0.2);
        padding: 12px 18px;
        border-radius: 10px;
        margin: 18px 0;
        flex-wrap: wrap;
        gap: 10px;
    }

    [data-theme="dark"] .endpoint-block {
        background: rgba(99, 102, 241, 0.05);
        border-color: rgba(99, 102, 241, 0.3);
    }

    .endpoint-path {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.96rem;
        font-weight: 700;
        color: var(--dark);
    }

    .method-badge {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-weight: 800;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
        text-transform: uppercase;
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .method-get {
        background: #10b981;
    }

    .method-post {
        background: #6366f1;
    }

    .method-delete {
        background: #ef4444;
    }

    /* Tables */
    .params-table-wrapper {
        overflow-x: auto;
        margin: 18px 0;
        border-radius: 12px;
        border: 1px solid var(--border);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        min-width: 500px;
    }

    table th, table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        text-align: left;
    }

    table th {
        background: rgba(99, 102, 241, 0.04);
        font-weight: 700;
        color: var(--dark);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    table tr:last-child td {
        border-bottom: none;
    }

    .header-badge {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-weight: 600;
        padding: 3px 6px;
        border-radius: 4px;
        background: rgba(0, 0, 0, 0.05);
        color: var(--dark);
        font-size: 0.82rem;
    }

    [data-theme="dark"] .header-badge {
        background: rgba(255, 255, 255, 0.08);
    }

    .type-badge {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.78rem;
        padding: 2px 6px;
        border-radius: 4px;
        background: rgba(99, 102, 241, 0.08);
        color: var(--primary);
        font-weight: 600;
    }

    /* Pre & Codes */
    pre {
        background: #0f172a;
        color: #f8fafc;
        padding: 20px;
        border-radius: 12px;
        overflow-x: auto;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.85rem;
        margin: 18px 0;
        border: 1px solid rgba(255, 255, 255, 0.04);
        box-shadow: inset 0 2px 8px rgba(0,0,0,0.3);
        line-height: 1.5;
        max-width: 100%;
        box-sizing: border-box;
    }

    code {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        background: rgba(0, 0, 0, 0.04);
        padding: 3px 6px;
        border-radius: 6px;
        font-size: 0.88em;
        color: var(--primary);
        font-weight: 600;
    }

    [data-theme="dark"] code {
        background: rgba(255, 255, 255, 0.06);
    }

    .info-alert {
        background: rgba(99, 102, 241, 0.04);
        border: 1px solid rgba(99, 102, 241, 0.1);
        border-left: 4px solid var(--primary);
        padding: 18px 22px;
        border-radius: 0 12px 12px 0;
        margin: 22px 0;
        font-size: 0.92rem;
        color: var(--gray);
        line-height: 1.6;
    }

    .info-alert strong {
        color: var(--dark);
    }

    /* Integration Cards Grid */
    .integration-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 24px;
        margin-top: 22px;
    }

    @media (max-width: 992px) {
        .integration-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }
    .integration-column {
        display: flex;
        flex-direction: column;
        gap: 24px;
        margin-top: 22px;
    }

    .integration-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 28px;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        gap: 16px;
        min-width: 0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.01);
    }

    [data-theme="light"] .integration-card {
        background: rgba(0, 0, 0, 0.01);
    }

    .integration-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(99, 102, 241, 0.06);
        border-color: rgba(99, 102, 241, 0.25);
    }

    .integration-card h3 {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
        font-size: 1.15rem;
        color: var(--primary);
        font-weight: 700;
    }

    .integration-card .icon-wrapper {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: rgba(99, 102, 241, 0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        color: var(--primary);
    }

    .step-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        font-size: 0.75rem;
        font-weight: 800;
        margin-right: 8px;
        flex-shrink: 0;
    }

    .step-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .step-list li {
        font-size: 0.92rem;
        color: var(--gray);
        line-height: 1.55;
        display: flex;
        align-items: flex-start;
    }

    .step-list li strong {
        color: var(--dark);
    }
</style>
@endsection

@section('content')
<div class="docs-layout">
    <!-- Navigation Topbar -->
    <div class="docs-topbar-nav">
        <a href="#getting-started" class="docs-nav-link active"><i class="fa-solid fa-rocket"></i> Getting Started</a>
        <a href="#verify-key" class="docs-nav-link"><i class="fa-solid fa-key"></i> Verify Key</a>
        <a href="#mobile-login" class="docs-nav-link"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
        <a href="#sync-sms" class="docs-nav-link"><i class="fa-solid fa-mobile-screen"></i> SMS Sync</a>
        <a href="#create-payment" class="docs-nav-link"><i class="fa-solid fa-file-invoice-dollar"></i> Create Invoice</a>
        <a href="#list-invoices" class="docs-nav-link"><i class="fa-solid fa-list-check"></i> List Invoices</a>
        <a href="#get-invoice" class="docs-nav-link"><i class="fa-solid fa-magnifying-glass-dollar"></i> Details</a>
        <a href="#mark-paid" class="docs-nav-link"><i class="fa-solid fa-circle-check"></i> Mark Paid</a>
        <a href="#mark-cancelled" class="docs-nav-link"><i class="fa-solid fa-circle-xmark"></i> Mark Cancelled</a>
        <a href="#delete-invoice" class="docs-nav-link"><i class="fa-solid fa-trash-can"></i> Delete</a>
        <a href="#synced-transactions" class="docs-nav-link"><i class="fa-solid fa-coins"></i> Synced Logs</a>
        <a href="#webhooks" class="docs-nav-link"><i class="fa-solid fa-bullhorn"></i> Webhooks</a>
        <a href="#mobile-integrations" class="docs-nav-link"><i class="fa-solid fa-code"></i> Integration</a>
        <a href="#sandbox-simulation" class="docs-nav-link"><i class="fa-solid fa-vial"></i> Sandbox</a>
        <a href="#downloads" class="docs-nav-link"><i class="fa-solid fa-download"></i> Downloads</a>
    </div>

    <!-- Main Content Area -->
    <div class="docs-content">
        <!-- Section 1: Intro -->
        <div class="docs-section" id="getting-started">
            <h2><i class="fa-solid fa-circle-info"></i> Getting Started & Authentication</h2>
            <p>
                OmniPay is a modular, multi-store checkout processing gateway. It offers unified endpoints for creating payment invoices, tracking customer receipts, and auto-matching deposits with SMS logs.
            </p>
            <p>
                Every API call must be authenticated using the custom <span class="header-badge">X-API-KEY</span> header (or using the request payload parameter <code>api_key</code>):
            </p>
            <ul>
                <li><strong>Store Scope Key</strong>: Required for public invoice creation or checkout generation. Uses store-level API keys.</li>
                <li><strong>Merchant Scope Key</strong>: Required for mobile sync tools, transactions listings, invoice status alterations, and MFS sync pools. Uses user-level API keys (or `sms_sync_key` generated on login).</li>
            </ul>
            <div class="info-alert">
                <strong>Default Sandbox Verification Keys:</strong><br>
                - Merchant Account API Key: <code>merchant_key_123</code><br>
                - Store-Level Public API Key: <code>store_key_456</code>
            </div>
        </div>

        <!-- Section 2: Key Verification -->
        <div class="docs-section" id="verify-key">
            <h2><i class="fa-solid fa-key"></i> Key Verification API</h2>
            <p>Clients can call this endpoint to test their API keys. This verifies key validity and identifies the key scope (merchant account level vs store level).</p>
            
            <div class="endpoint-block">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/verify-key</span>
            </div>

            <h3>Request Headers</h3>
            <div class="params-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Header</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="header-badge">X-API-KEY</span></td>
                            <td><span class="type-badge">string</span></td>
                            <td>Yes</td>
                            <td>The API key to verify (merchant account key, SMS sync key, or store key).</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Example Response (Merchant Scope)</h3>
            <pre>{
  "valid": true,
  "type": "merchant",
  "name": "Merchant User",
  "email": "merchant@test.com",
  "stores_count": 1
}</pre>

            <h3>Example Response (Store Scope)</h3>
            <pre>{
  "valid": true,
  "type": "store",
  "name": "Test Store",
  "domain": "localhost",
  "merchant_name": "Merchant User"
}</pre>
        </div>

        <!-- Section 3: Mobile Connection Login -->
        <div class="docs-section" id="mobile-login">
            <h2><i class="fa-solid fa-right-to-bracket"></i> Mobile Connection Login API</h2>
            <p>Retrieve user API sync credentials dynamically by logging in with standard email and password. This is commonly used by mobile apps during setup.</p>
            
            <div class="endpoint-block">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/auth/login</span>
            </div>

            <h3>Request Body Parameters</h3>
            <div class="params-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>email</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td>Yes</td>
                            <td>The account email address.</td>
                        </tr>
                        <tr>
                            <td><code>password</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td>Yes</td>
                            <td>The account password.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Success Response (200 OK)</h3>
            <pre>{
  "success": true,
  "status": "success",
  "api_key": "sync_abc123xyz...",
  "sms_sync_key": "sync_abc123xyz...",
  "name": "Merchant User",
  "email": "merchant@test.com",
  "role": "merchant"
}</pre>
        </div>

        <!-- Section 4: SMS Sync -->
        <div class="docs-section" id="sync-sms">
            <h2><i class="fa-solid fa-mobile-screen"></i> SMS Syncing Ingestion API</h2>
            <p>Called by SMS listener applications to feed raw transactional messages into the server's verification pool.</p>
            
            <div class="endpoint-block">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/sync-sms</span>
            </div>

            <h3>Request Body Parameters</h3>
            <div class="params-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>sender</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td>Yes</td>
                            <td>The SMS sender display ID (e.g., <code>bKash</code>, <code>Nagad</code>, <code>16216</code>).</td>
                        </tr>
                        <tr>
                            <td><code>msg_data</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td>Yes</td>
                            <td>The raw message content.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Request Body Example</h3>
            <pre>{
  "sender": "bKash",
  "msg_data": "You have received Tk 1,300.00 from 01711223344. Ref: Shop. Fee Tk 0.00. Balance Tk 5,000.00. TrxID 9J87X65Y4 at 19/06/2026 22:00"
}</pre>

            <h3>Success Response (210 Created)</h3>
            <pre>{
  "status": "success",
  "message": "SMS data synced successfully",
  "amount": 1300,
  "trxid": "9J87X65Y4",
  "sender": "bkash"
}</pre>
        </div>

        <!-- Section 5: Create Invoice -->
        <div class="docs-section" id="create-payment">
            <h2><i class="fa-solid fa-file-invoice-dollar"></i> Create Invoice API</h2>
            <p>Generate a secure payment invoice and get a unique checkout redirection link.</p>
            
            <div class="endpoint-block">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/payment</span>
            </div>

            <h3>Request Headers</h3>
            <div class="params-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Header</th>
                            <th>Value</th>
                            <th>Required</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="header-badge">X-API-KEY</span></td>
                            <td>Your <code>store_key_456</code> (Store Scope Key)</td>
                            <td>Yes</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Request Body Parameters</h3>
            <div class="params-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>amount</code></td>
                            <td><span class="type-badge">numeric</span></td>
                            <td>Yes</td>
                            <td>Target value in USD/USDT (e.g., <code>10.00</code>).</td>
                        </tr>
                        <tr>
                            <td><code>customer_name</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td>Yes</td>
                            <td>Customer's full name.</td>
                        </tr>
                        <tr>
                            <td><code>customer_email</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td>Yes</td>
                            <td>Customer's email address.</td>
                        </tr>
                        <tr>
                            <td><code>currency</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td>No</td>
                            <td><code>USDT</code> (default) or <code>BDT</code>.</td>
                        </tr>
                        <tr>
                            <td><code>callback_url</code></td>
                            <td><span class="type-badge">string (url)</span></td>
                            <td>No</td>
                            <td>Target webhook callback destination.</td>
                        </tr>
                        <tr>
                            <td><code>cancel_url</code></td>
                            <td><span class="type-badge">string (url)</span></td>
                            <td>No</td>
                            <td>Redirection URL if cancelled.</td>
                        </tr>
                        <tr>
                            <td><code>is_sandbox</code></td>
                            <td><span class="type-badge">boolean</span></td>
                            <td>No</td>
                            <td>Set to <code>true</code> to mark as test/sandbox (default <code>false</code>).</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Success Response (201 Created)</h3>
            <pre>{
  "success": true,
  "invoice_id": "INV-F388X99Y",
  "amount": 10,
  "expected_amount": 10.000452,
  "currency": "USDT",
  "payment_link": "http://localhost:8000/checkout/NjAwLjAwMDU3M...",
  "expires_at": "2026-06-20T05:10:30.000000Z"
}</pre>
        </div>

        <!-- Section 6: List Invoices -->
        <div class="docs-section" id="list-invoices">
            <h2><i class="fa-solid fa-list-check"></i> List Invoices API</h2>
            <p>Retrieve a paginated list of invoices associated with the authenticated Store key or Merchant User key.</p>
            
            <div class="endpoint-block">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/transactions</span>
                <span class="endpoint-path" style="margin-left:10px; color:var(--gray)">or /api/v1/invoices</span>
            </div>

            <h3>Query Parameters</h3>
            <div class="params-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>status</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td>No</td>
                            <td>Filter by status: <code>paid</code>, <code>pending</code>, <code>expired</code>, or <code>cancelled</code> (accepts both `canceled` and `cancelled`).</td>
                        </tr>
                        <tr>
                            <td><code>invoice_id</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td>No</td>
                            <td>Filter by a specific invoice ID.</td>
                        </tr>
                        <tr>
                            <td><code>customer_email</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td>No</td>
                            <td>Filter by customer email address.</td>
                        </tr>
                        <tr>
                            <td><code>per_page</code></td>
                            <td><span class="type-badge">integer</span></td>
                            <td>No</td>
                            <td>Items per page (default: 25).</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Success Response (200 OK)</h3>
            <pre>{
  "success": true,
  "invoices": {
    "current_page": 1,
    "data": [
      {
        "id": 14,
        "store_id": 7,
        "invoice_id": "INV-F388X99Y",
        "customer_name": "John Doe",
        "customer_email": "john@example.com",
        "amount": 10,
        "expected_amount": 10.000452,
        "currency": "USDT",
        "status": "pending",
        "expires_at": "2026-06-20T05:10:30.000000Z",
        "created_at": "2026-06-20T04:40:30.000000Z"
      }
    ],
    "total": 1
  }
}</pre>
        </div>

        <!-- Section 7: Get Invoice -->
        <div class="docs-section" id="get-invoice">
            <h2><i class="fa-solid fa-magnifying-glass-dollar"></i> Get Invoice Details API</h2>
            <p>Retrieve the full details and current status of a specific invoice.</p>
            
            <div class="endpoint-block">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/transactions/{invoice_id}</span>
                <span class="endpoint-path" style="margin-left:10px; color:var(--gray)">or /api/v1/invoices/{invoice_id}</span>
            </div>

            <h3>Success Response (200 OK)</h3>
            <pre>{
  "success": true,
  "invoice": {
    "id": 14,
    "invoice_id": "INV-F388X99Y",
    "customer_name": "John Doe",
    "amount": 10,
    "expected_amount": 10.000452,
    "status": "paid",
    "paid_at": "2026-06-20T04:45:12.000000Z"
  }
}</pre>
        </div>

        <!-- Section 8: Mark Paid -->
        <div class="docs-section" id="mark-paid">
            <h2><i class="fa-solid fa-circle-check"></i> Mark Invoice as Paid API</h2>
            <p>Manually mark a pending invoice as paid. This updates the database, sets the payment timestamp, and automatically triggers the standard merchant notification email and webhook callback pipeline.</p>
            
            <div class="endpoint-block">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/transactions/{invoice_id}/mark-paid</span>
                <span class="endpoint-path" style="margin-left:10px; color:var(--gray)">or /api/v1/invoices/{invoice_id}/mark-paid</span>
            </div>

            <h3>Success Response (200 OK)</h3>
            <pre>{
  "success": true,
  "message": "Invoice marked as paid successfully",
  "invoice": {
    "invoice_id": "INV-F388X99Y",
    "status": "paid",
    "paid_at": "2026-06-20T04:50:00.000000Z"
  }
}</pre>
        </div>

        <!-- Section 9: Mark Cancelled -->
        <div class="docs-section" id="mark-cancelled">
            <h2><i class="fa-solid fa-circle-xmark"></i> Mark Invoice as Cancelled API</h2>
            <p>Manually mark a pending invoice as cancelled.</p>
            
            <div class="endpoint-block">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/transactions/{invoice_id}/mark-cancelled</span>
                <span class="endpoint-path" style="margin-left:10px; color:var(--gray)">or /api/v1/invoices/{invoice_id}/mark-cancelled</span>
            </div>

            <h3>Success Response (200 OK)</h3>
            <pre>{
  "success": true,
  "message": "Invoice marked as cancelled successfully",
  "invoice": {
    "invoice_id": "INV-F388X99Y",
    "status": "cancelled"
  }
}</pre>
        </div>

        <!-- Section 10: Delete Invoice -->
        <div class="docs-section" id="delete-invoice">
            <h2><i class="fa-solid fa-trash-can"></i> Delete Invoice API</h2>
            <p>Remove an invoice from the database permanently.</p>
            
            <div class="endpoint-block">
                <span class="method-badge method-delete">DELETE</span>
                <span class="endpoint-path">/api/v1/transactions/{invoice_id}</span>
                <span class="endpoint-path" style="margin-left:10px; color:var(--gray)">or /api/v1/invoices/{invoice_id}</span>
            </div>

            <h3>Success Response (200 OK)</h3>
            <pre>{
  "success": true,
  "message": "Invoice deleted successfully"
}</pre>
        </div>

        <!-- Section 11: Synced Transactions -->
        <div class="docs-section" id="synced-transactions">
            <h2><i class="fa-solid fa-coins"></i> Synced MFS Transactions API</h2>
            <p>Retrieve the paginated history of raw mobile SMS transactions parsed and synced to this merchant account.</p>
            
            <div class="endpoint-block">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/synced-transactions</span>
            </div>

            <h3>Query Parameters</h3>
            <div class="params-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>sender</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td>No</td>
                            <td>Filter by specific gateway sender: <code>bkash</code>, <code>nagad</code>, <code>rocket</code>, <code>upay</code>, etc.</td>
                        </tr>
                        <tr>
                            <td><code>is_used</code></td>
                            <td><span class="type-badge">boolean</span></td>
                            <td>No</td>
                            <td>Filter by whether the SMS deposit has already been matched to an invoice (<code>true</code> or <code>false</code>).</td>
                        </tr>
                        <tr>
                            <td><code>per_page</code></td>
                            <td><span class="type-badge">integer</span></td>
                            <td>No</td>
                            <td>Items per page (default: 25).</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Success Response (200 OK)</h3>
            <pre>{
  "success": true,
  "transactions": {
    "current_page": 1,
    "data": [
      {
        "id": 8,
        "user_id": 2,
        "sender": "bkash",
        "amount": 1300,
        "trxid": "TRX123456",
        "raw_message": "You have received Tk 1,300.00 from 01711223344. Ref: Shop. TrxID TRX123456",
        "is_used": true,
        "timestamp": 1781914532,
        "created_at": "2026-06-20T04:45:00.000000Z"
      }
    ]
  }
}</pre>
        </div>

        <!-- Section 12: Webhooks -->
        <div class="docs-section" id="webhooks">
            <h2><i class="fa-solid fa-bullhorn"></i> Webhook Callback Notifications</h2>
            <p>OmniPay triggers signature-verified POST callbacks to the configured <code>callback_url</code> on invoice payment completion or manual status changes.</p>
            
            <h3>Webhook Payload</h3>
            <pre>{
  "invoice_id": "INV-F388X99Y",
  "amount": 10.00,
  "expected_amount": 10.000452,
  "currency": "USDT",
  "status": "paid",
  "paid_at": "2026-06-20 04:45:12",
  "timestamp": 1781914532
}</pre>

            <h3>Webhook Signature Header Verification</h3>
            <p>Every callback request includes the header <span class="header-badge">X-OMNIPAY-SIGNATURE</span> containing the HMAC-SHA256 hash of the JSON body using your **Store API Key** as the shared secret key.</p>
            <p><strong>Verification (PHP):</strong></p>
            <pre>$expectedSign = hash_hmac('sha256', $requestBody, $storeApiKey);
if (hash_equals($expectedSign, $request->header('X-OMNIPAY-SIGNATURE'))) {
    // Valid webhook! Safe to process order fulfillment.
}</pre>
        </div>

        <!-- Section 13: Mobile Code Example -->
        <div class="docs-section" id="mobile-integrations">
            <h2><i class="fa-solid fa-mobile-screen-button"></i> Mobile Client Integrations</h2>
            <p>Use these reference implementation blocks to build background listeners or manual quick action shortcuts on Android and iOS.</p>

            <div class="integration-column">
                <!-- Card A: Android -->
                <div class="integration-card">
                    <h3>
                        <div class="icon-wrapper">
                            <i class="fa-brands fa-android"></i>
                        </div>
                        Android Background Sync App
                    </h3>
                    <p>
                        Listen to incoming device notifications or SMS messages, parse transactions, and sync them automatically to the server.
                    </p>

                    <strong style="color: var(--primary); font-size: 0.95rem;">Workflow Steps:</strong>
                    <ul class="step-list">
                        <li>
                            <span class="step-badge">1</span>
                            <strong>Handshake:</strong> Call <code>GET /api/v1/verify-key</code> to check API key validity.
                        </li>
                        <li>
                            <span class="step-badge">2</span>
                            <strong>SMS Receiver:</strong> Hook into <code>android.provider.Telephony.SMS_RECEIVED</code>.
                        </li>
                        <li>
                            <span class="step-badge">3</span>
                            <strong>Forward:</strong> POST raw messages to <code>/api/v1/sync-sms</code> with the User's API key.
                        </li>
                    </ul>

                    <strong style="color: var(--primary); font-size: 0.85rem; margin-top: 10px;">Receiver Code (Kotlin snippet):</strong>
                    <pre style="font-size: 0.78rem; max-height: 250px; margin: 5px 0;">class SmsSyncReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action == Telephony.Sms.Intents.SMS_RECEIVED_ACTION) {
            val messages = Telephony.Sms.Intents.getMessagesFromIntent(intent)
            for (sms in messages) {
                val sender = sms.originatingAddress ?: continue
                val body = sms.messageBody ?: continue

                if (isMfsSender(sender)) {
                    forwardSmsToServer(sender, body)
                }
            }
        }
    }
}</pre>
                </div>

                <!-- Card B: iOS Shortcuts -->
                <div class="integration-card">
                    <h3>
                        <div class="icon-wrapper">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                        </div>
                        Apple iOS Shortcuts App
                    </h3>
                    <p>
                        Perform merchant actions directly from your iPhone widgets or Siri shortcuts.
                    </p>

                    <strong style="color: var(--primary); font-size: 0.95rem;">Shortcut workflows:</strong>
                    <ul class="step-list">
                        <li>
                            <span class="step-badge">1</span>
                            <strong>Create Link:</strong> Ask for **Amount** input, trigger <code>POST /api/v1/payment</code>, extract <code>payment_link</code>, and open it.
                        </li>
                        <li>
                            <span class="step-badge">2</span>
                            <strong>Sync Clipboard:</strong> Fetch copied SMS from clipboard, choose sender, and POST to <code>/api/v1/sync-sms</code>.
                        </li>
                        <li>
                            <span class="step-badge">3</span>
                            <strong>Check Invoices:</strong> Call <code>GET /api/v1/invoices?status=pending</code> and print a list of pending checkout requests.
                        </li>
                        <li>
                            <span class="step-badge">4</span>
                            <strong>Native Notifications:</strong> After Sync, use <strong>Get Dictionary from Input</strong> on the URL response, extract the <code>message</code> key, and trigger <strong>Show Notification</strong> for gorgeous native banners.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Section 14: Sandbox Testing -->
        <div class="docs-section" id="sandbox-simulation">
            <h2><i class="fa-solid fa-vial"></i> Local Sandbox Simulation Rules</h2>
            <p>Accelerate local integration testing by simulating checkouts and deposit detections without requiring live gateway credentials:</p>
            
            <h3>1. MFS Sync & Checkout</h3>
            <p>Generate a test invoice, then use the Sync SMS API endpoint shown above to submit transactional mock messages. The checkout page's polling loop will detect the synced amount and present the matching TrxID select modal.</p>

            <h3>2. Exchange Deposit Systems (Binance / Bybit)</h3>
            <p>Configure gateway credentials with mock strings. Simulate customer deposit arrivals by changing session baseline variables inside your server script:</p>
            <pre>// Binance deposit baseline simulation
session(['simulated_binance_balance' => 110.000321]);</pre>

            <h3>3. Web3 Crypto Transactions</h3>
            <p>Open the checkout page, choose Web3, select network, then trigger a simulated verification scan check by writing: <code>session(['simulate_web3_success' => true])</code>.</p>
        </div>

        <!-- Section 15: Plugins & Downloads -->
        <div class="docs-section" id="downloads">
            <h2><i class="fa-solid fa-download"></i> Plugins & SDK Downloads</h2>
            <p>Bootstrap your integrations using our official pre-compiled plugins. Legacy PipraPay integrations are fully backward-compatible by simply replacing the base endpoint URL to point to this server.</p>

            <div class="integration-grid">
                <div class="integration-card">
                    <h3>
                        <div class="icon-wrapper">
                            <i class="fa-solid fa-server"></i>
                        </div>
                        WHMCS Plugin
                    </h3>
                    <p>Process invoice checkouts automatically inside WHMCS systems.</p>
                    <a href="{{ asset('downloads/whmcs-omnipay.zip') }}" class="btn btn-primary" style="margin-top:auto; width: 100%;">
                        <i class="fa-solid fa-file-zipper"></i> Download WHMCS
                    </a>
                </div>

                <div class="integration-card">
                    <h3>
                        <div class="icon-wrapper">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                        Blesta Plugin
                    </h3>
                    <p>Accept checkouts and process instant callback triggers inside Blesta.</p>
                    <a href="{{ asset('downloads/blesta-omnipay.zip') }}" class="btn btn-primary" style="margin-top:auto; width: 100%;">
                        <i class="fa-solid fa-file-zipper"></i> Download Blesta
                    </a>
                </div>

                <div class="integration-card">
                    <h3>
                        <div class="icon-wrapper">
                            <i class="fa-solid fa-users-gear"></i>
                        </div>
                        SMM Panel Plugin
                    </h3>
                    <p>Automate user fund deposits on SMM Panel scripts.</p>
                    <a href="{{ asset('downloads/smmpanel-omnipay.zip') }}" class="btn btn-primary" style="margin-top:auto; width: 100%;">
                        <i class="fa-solid fa-file-zipper"></i> Download SMM Panel
                    </a>
                </div>

                <div class="integration-card">
                    <h3>
                        <div class="icon-wrapper">
                            <i class="fa-brands fa-laravel"></i>
                        </div>
                        Laravel SDK
                    </h3>
                    <p>Laravel integration SDK containing payment models and verification helpers.</p>
                    <a href="{{ asset('downloads/laravel-sdk-omnipay.zip') }}" class="btn btn-primary" style="margin-top:auto; width: 100%;">
                        <i class="fa-solid fa-file-zipper"></i> Download SDK
                    </a>
                </div>

                <div class="integration-card">
                    <h3>
                        <div class="icon-wrapper">
                            <i class="fa-brands fa-android"></i>
                        </div>
                        Android Java SDK
                    </h3>
                    <p>Integrate invoice generation in native mobile client code.</p>
                    <a href="{{ asset('downloads/android-sdk-omnipay.zip') }}" class="btn btn-primary" style="margin-top:auto; width: 100%;">
                        <i class="fa-solid fa-file-zipper"></i> Download Android SDK
                    </a>
                </div>

                <div class="integration-card">
                    <h3>
                        <div class="icon-wrapper">
                            <i class="fa-brands fa-wordpress"></i>
                        </div>
                        WooCommerce
                    </h3>
                    <p>Full WooCommerce plugin supporting classic and block checkouts.</p>
                    <a href="{{ asset('downloads/woocommerce-omnipay.zip') }}" class="btn btn-primary" style="margin-top:auto; width: 100%;">
                        <i class="fa-solid fa-file-zipper"></i> Download Plugin
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sections = document.querySelectorAll(".docs-section");
        const navLinks = document.querySelectorAll(".docs-nav-link");

        // Dynamic Active class switching on scroll
        window.addEventListener("scroll", () => {
            let current = "";
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (window.scrollY >= sectionTop - 150) {
                    current = section.getAttribute("id");
                }
            });

            navLinks.forEach(link => {
                link.classList.remove("active");
                if (link.getAttribute("href") === `#${current}`) {
                    link.classList.add("active");
                }
            });
        });

        // Smooth scroll for nav clicks
        navLinks.forEach(link => {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                const targetId = this.getAttribute("href");
                const targetSection = document.querySelector(targetId);
                
                if (targetSection) {
                    window.scrollTo({
                        top: targetSection.offsetTop - 140,
                        behavior: "smooth"
                    });
                }
            });
        });
    });
</script>
@endsection
