@extends('dashboard.layout')

@section('page_title', 'Email Templates')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-premium">
            <div class="card-header">
                <h3 class="card-title">Manage Email Templates</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Key</th>
                                <th>Subject</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $template)
                            <tr>
                                <td><strong>{{ $template->name }}</strong></td>
                                <td><code>{{ $template->key }}</code></td>
                                <td>{{ $template->subject }}</td>
                                <td style="text-align: right;">
                                    <a href="{{ route('email-templates.edit', $template->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                            @if($templates->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No email templates found.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
