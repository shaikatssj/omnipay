@extends('dashboard.layout')

@section('page_title', 'Edit Email Template')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-premium">
            <div class="card-header">
                <h3 class="card-title">Edit Template: {{ $emailTemplate->name }}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('email-templates.update', $emailTemplate->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group mb-3">
                        <label for="subject">Email Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject', $emailTemplate->subject) }}" required>
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="body">Email Body (HTML supported) <span class="text-danger">*</span></label>
                        <textarea name="body" id="body" class="form-control @error('body') is-invalid @enderror" rows="12" required style="font-family: monospace;">{{ old('body', $emailTemplate->body) }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                        <a href="{{ route('email-templates.index') }}" class="btn btn-outline-secondary ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-premium">
            <div class="card-header">
                <h3 class="card-title">Available Variables</h3>
            </div>
            <div class="card-body">
                <p class="text-muted text-sm">You can use these variables in both the subject and body. They will be dynamically replaced when the email is sent.</p>
                <div class="list-group list-group-flush">
                    @if($emailTemplate->variables && is_array($emailTemplate->variables))
                        @foreach($emailTemplate->variables as $var)
                            <div class="list-group-item px-0 py-2">
                                <code>{{ '{' . $var . '}' }}</code>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">No variables available for this template.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
