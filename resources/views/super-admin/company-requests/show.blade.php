@extends('layouts.app')

@section('title', 'Review Company Request - Elevanix')

@section('content')
<div class="page-header">
    <div>
        <span>Company Request</span>
        <h1>{{ $request->company_name }}</h1>
    </div>
    @include('partials.status-badge', ['status' => $request->status])
</div>

<div class="content-grid">
    <section class="content-card">
        <h2>Company Details</h2>
        <dl class="detail-list">
            <dt>Email</dt><dd>{{ $request->company_email }}</dd>
            <dt>Phone</dt><dd>{{ $request->company_phone }}</dd>
            <dt>Website</dt><dd>{{ $request->website ?: 'Not provided' }}</dd>
            <dt>Address</dt><dd>{{ $request->company_address }}</dd>
        </dl>
    </section>
    <section class="content-card">
        <h2>Administrator Details</h2>
        <dl class="detail-list">
            <dt>Name</dt><dd>{{ $request->admin_name }}</dd>
            <dt>Email</dt><dd>{{ $request->admin_email }}</dd>
            <dt>Username</dt><dd>{{ $request->username }}</dd>
        </dl>
        @if($request->status === 'pending')
            <div class="d-flex flex-wrap gap-2 mt-3">
                <form method="POST" action="{{ route('super-admin.company-requests.approve', $request) }}" data-confirm="Approve this company request?">
                    @csrf
                    <button class="btn btn-primary" type="submit">Approve</button>
                </form>
            </div>
            <form method="POST" action="{{ route('super-admin.company-requests.reject', $request) }}" class="mt-4">
                @csrf
                <label for="rejection_reason" class="form-label">Rejection Reason</label>
                <textarea id="rejection_reason" name="rejection_reason" class="form-control @error('rejection_reason') is-invalid @enderror" required>{{ old('rejection_reason') }}</textarea>
                @error('rejection_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <button class="btn btn-outline-danger mt-2" type="submit">Reject Request</button>
            </form>
        @endif
    </section>
</div>
@endsection
