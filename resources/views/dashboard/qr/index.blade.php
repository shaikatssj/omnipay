@extends('dashboard.layout')

@section('page_title', 'QR Code Anti-Hijacking Manager')

@section('styles')
<style>
    .qr-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    @media(max-width: 900px) {
        .qr-grid {
            grid-template-columns: 1fr;
        }
    }

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

    .form-control, .form-textarea {
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

    [data-theme="dark"] .form-control, [data-theme="dark"] .form-textarea {
        background: rgba(0, 0, 0, 0.2);
    }

    .form-control:focus, .form-textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .qr-preview-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid var(--border);
        cursor: pointer;
        transition: var(--transition);
    }

    .qr-preview-img:hover {
        transform: scale(1.1);
    }

    .table-container {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .data-table th, .data-table td {
        padding: 14px 18px;
        font-size: 0.88rem;
        border-bottom: 1px solid var(--border);
    }

    .data-table th {
        font-weight: 700;
        color: var(--gray);
    }
</style>
@endsection

@section('content')
<div class="qr-grid">
    <!-- Left Column: Tables -->
    <div style="display: flex; flex-direction: column; gap: 30px;">
        <!-- List of registered QR codes -->
        <div class="card">
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 20px;">
                <i class="fa-solid fa-list-check" style="color: var(--primary); margin-right: 8px;"></i> Registered QR Codes
            </h3>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>QR Preview</th>
                            <th>MD5 Checksum</th>
                            <th>Decoded Payload</th>
                            @if(Auth::user()->role === 'admin')
                                <th>Owner</th>
                            @endif
                            <th>Registered At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($qrCodes as $qr)
                            <tr>
                                <td>
                                    <a href="{{ asset($qr->file_path) }}" target="_blank">
                                        <img src="{{ asset($qr->file_path) }}" class="qr-preview-img" alt="QR Preview">
                                    </a>
                                </td>
                                <td>
                                    <code style="font-size: 0.8rem; font-family: monospace;">{{ substr($qr->checksum, 0, 16) }}...</code>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem; font-family: monospace; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $qr->qr_data }}">
                                        {{ $qr->qr_data }}
                                    </div>
                                </td>
                                @if(Auth::user()->role === 'admin')
                                    <td>{{ $qr->user->name ?? 'N/A' }}</td>
                                @endif
                                <td>{{ $qr->created_at->diffForHumans() }}</td>
                                <td>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <form action="{{ route('dashboard.qr.delete', ['qrCode' => $qr->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this QR Code?');" style="margin: 0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.78rem; border-color: var(--danger); color: var(--danger);">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </form>
                                        @if(Auth::user()->role === 'admin')
                                            <form action="{{ route('dashboard.qr.blacklist.store') }}" method="POST" onsubmit="return confirm('Are you sure you want to blocklist this QR Code? It will be deregistered.');" style="margin: 0;">
                                                @csrf
                                                <input type="hidden" name="qr_data" value="{{ $qr->qr_data }}">
                                                <input type="hidden" name="note" value="Blocked by Admin from registered list">
                                                <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.78rem; border-color: var(--warning); color: var(--warning);">
                                                    <i class="fa-solid fa-ban"></i> Blocklist
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--gray);">No QR Codes uploaded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Blacklisted QR Entries -->
        <div class="card">
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 20px; color: var(--danger);">
                <i class="fa-solid fa-triangle-exclamation" style="color: var(--danger); margin-right: 8px;"></i> Blacklisted QR Payloads (Anti-Hijack Registry)
            </h3>
            
            <p style="font-size: 0.82rem; color: var(--gray); margin-bottom: 20px; line-height: 1.5;">
                Below is the list of globally blacklisted QR code payloads. If an uploaded QR code contains a payload matching any of these hashes, the system blocks the registration to prevent payment hijacking.
            </p>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SHA256 Payload Hash</th>
                            <th>Reason / Note</th>
                            <th>Added At</th>
                            @if(Auth::user()->role === 'admin')
                                <th>Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blacklist as $entry)
                            <tr>
                                <td>
                                    <code style="font-size: 0.8rem; font-family: monospace;" title="{{ $entry->qr_data_hash }}">{{ substr($entry->qr_data_hash, 0, 24) }}...</code>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem;" title="{{ $entry->note }}">
                                        {{ $entry->note ?? 'No reason provided' }}
                                    </div>
                                </td>
                                <td>{{ $entry->created_at->diffForHumans() }}</td>
                                @if(Auth::user()->role === 'admin')
                                    <td>
                                        <form action="{{ route('dashboard.qr.blacklist.delete', ['id' => $entry->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this entry from the blocklist?');" style="margin: 0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.78rem; border-color: var(--success); color: var(--success);">
                                                <i class="fa-solid fa-unlock"></i> Unblock
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="@if(Auth::user()->role === 'admin') 4 @else 3 @endif" style="text-align: center; color: var(--gray);">No blacklisted QR entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Forms -->
    <div style="display: flex; flex-direction: column; gap: 30px;">
        <!-- Upload QR Code form -->
        <div class="card" style="height: fit-content;">
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 20px;">
                <i class="fa-solid fa-cloud-arrow-up" style="color: var(--primary); margin-right: 8px;"></i> Upload QR Code
            </h3>
            
            <p style="font-size: 0.82rem; color: var(--gray); margin-bottom: 20px; line-height: 1.5;">
                Upload your MFS receive QR code image. OmniPay registers it in the anti-hijack registry and verifies that the payload has not been blacklisted or claimed by another store owner.
            </p>

            @if ($errors->any())
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <ul style="list-style: none; padding: 0;">
                        @foreach ($errors->all() as $error)
                            <li><i class="fa-solid fa-triangle-exclamation"></i> {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="qr_alert" style="display:none; margin-bottom: 20px;" class="alert"></div>
            <div id="qr-reader" style="display: none;"></div>

            <form action="{{ route('dashboard.qr.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="qr_image">Select QR Image</label>
                    <input type="file" name="qr_image" id="qr_image" class="form-control" accept="image/*" required>
                </div>

                <div class="form-group">
                    <label for="qr_data">Decoded Payload / Content</label>
                    <input type="text" name="qr_data" id="qr_data" class="form-control" placeholder="e.g. bkash receive mobile number or URL payload" value="{{ old('qr_data') }}" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 10px;">
                    <i class="fa-solid fa-shield-halved" style="margin-right: 6px;"></i> Register & Verify QR Code
                </button>
            </form>
        </div>

        @if(Auth::user()->role === 'admin')
            <!-- Blocklist QR Code form -->
            <div class="card" style="height: fit-content;">
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 20px; color: var(--warning);">
                    <i class="fa-solid fa-ban" style="color: var(--warning); margin-right: 8px;"></i> Blocklist QR Code
                </h3>
                
                <p style="font-size: 0.82rem; color: var(--gray); margin-bottom: 20px; line-height: 1.5;">
                    Add a decrypted QR Code payload to the global blocklist. This will immediately deregister it from any store and prevent future uploads of the same payload.
                </p>

                <form action="{{ route('dashboard.qr.blacklist.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="blacklist_qr_data">Decoded Payload / Content to Block</label>
                        <input type="text" name="qr_data" id="blacklist_qr_data" class="form-control" placeholder="e.g. bkash receive mobile number or URL payload" required>
                    </div>

                    <div class="form-group">
                        <label for="blacklist_note">Blocklist Reason / Note</label>
                        <input type="text" name="note" id="blacklist_note" class="form-control" placeholder="e.g. Reported fraudulent wallet address" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 10px; background-color: var(--warning); border-color: var(--warning); color: white;">
                        <i class="fa-solid fa-ban" style="margin-right: 6px;"></i> Blocklist Payload
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const qrImageInput = document.getElementById('qr_image');
        const qrDataInput = document.getElementById('qr_data');
        const qrAlert = document.getElementById('qr_alert');

        let html5QrCode = null;
        try {
            html5QrCode = new Html5Qrcode("qr-reader");
        } catch (e) {
            console.error("Failed to initialize Html5Qrcode", e);
        }

        if (qrImageInput) {
            qrImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                // Hide previous alerts
                if (qrAlert) {
                    qrAlert.style.display = 'none';
                    qrAlert.className = 'alert';
                }

                // Stage 1: Try decoding using html5-qrcode (port of ZXing on main thread - CORS/WebWorker safe)
                if (html5QrCode) {
                    html5QrCode.scanFile(file, true) // true = enable image scan optimization
                        .then(decodedText => {
                            checkQrPayload(decodedText);
                        })
                        .catch(err => {
                            console.warn("Primary Html5Qrcode failed, trying fallback jsQR...", err);
                            // Stage 2: Fallback to jsQR multi-scale scanner
                            runJsQrFallback(file);
                        });
                } else {
                    // Fallback directly to jsQR if Html5Qrcode failed to initialize
                    runJsQrFallback(file);
                }
            });
        }

        function checkQrPayload(decodedText) {
            fetch("{{ route('dashboard.qr.check') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ qr_data: decodedText })
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error("HTTP error " + res.status);
                }
                return res.json();
            })
            .then(data => {
                if (data.allowed) {
                    if (qrDataInput) {
                        qrDataInput.value = decodedText;
                    }
                    if (qrAlert) {
                        qrAlert.className = 'alert alert-success';
                        qrAlert.innerHTML = '<i class="fa-solid fa-circle-check"></i> QR Code successfully decoded: <strong>' + escapeHtml(decodedText) + '</strong>';
                        qrAlert.style.display = 'block';
                    }
                } else {
                    if (qrAlert) {
                        qrAlert.className = 'alert alert-danger';
                        qrAlert.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + escapeHtml(data.message);
                        qrAlert.style.display = 'block';
                    }
                    qrImageInput.value = '';
                    if (qrDataInput) {
                        qrDataInput.value = '';
                    }
                }
            })
            .catch(err => {
                console.error("Failed to verify QR payload on server", err);
                if (qrDataInput) {
                    qrDataInput.value = decodedText;
                }
                if (qrAlert) {
                    qrAlert.className = 'alert alert-success';
                    qrAlert.innerHTML = '<i class="fa-solid fa-circle-check"></i> QR Code successfully decoded: <strong>' + escapeHtml(decodedText) + '</strong>';
                    qrAlert.style.display = 'block';
                }
            });
        }

        function runJsQrFallback(file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    // Try original size, 75% scale, and 50% scale
                    const scales = [1.0, 0.75, 0.5];
                    let decoded = false;
                    
                    for (let scale of scales) {
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        const width = Math.round(img.width * scale);
                        const height = Math.round(img.height * scale);
                        canvas.width = width;
                        canvas.height = height;
                        context.drawImage(img, 0, 0, width, height);
                        
                        try {
                            const imageData = context.getImageData(0, 0, width, height);
                            const code = jsQR(imageData.data, imageData.width, imageData.height);
                            
                            if (code && code.data) {
                                checkQrPayload(code.data);
                                decoded = true;
                                break;
                            }
                        } catch (e) {
                            console.error("jsQR scaling parse error at " + scale, e);
                        }
                    }

                    if (!decoded) {
                        if (qrAlert) {
                            qrAlert.className = 'alert alert-warning';
                            qrAlert.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Could not decode QR code automatically. Please ensure the image is clear, or enter the payload manually.';
                            qrAlert.style.display = 'block';
                        }
                    }
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }

        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    });
</script>
@endsection
