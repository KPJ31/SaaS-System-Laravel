@extends('layouts.app')

@section('title', 'Review Company Request - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Request',
    'title' => $request->company_name,
    'description' => 'Review submitted company and administrator information before approving or rejecting access.',
    'actions' => new Illuminate\Support\HtmlString(view('partials.status-badge', ['status' => $request->status])->render()),
])

<div class="content-grid">
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Company Details</h2>
                <p>Primary company profile information supplied by the requester.</p>
            </div>
        </div>
        <dl class="detail-list">
            <dt>Email</dt><dd>{{ $request->company_email }}</dd>
            <dt>Phone</dt><dd>{{ $request->company_phone }}</dd>
            <dt>Website</dt><dd>{{ $request->website ?: 'Not provided' }}</dd>
            <dt>Address</dt><dd>{{ $request->company_address }}</dd>
        </dl>
    </section>
    <section class="content-card">
        <div class="content-card-header">
            <div>
                <h2>Administrator Details</h2>
                <p>The first approved user becomes the Company Admin.</p>
            </div>
        </div>
        <dl class="detail-list">
            <dt>Name</dt><dd>{{ $request->admin_name }}</dd>
            <dt>Email</dt><dd>{{ $request->admin_email }}</dd>
            <dt>Username</dt><dd>{{ $request->username }}</dd>
        </dl>
        @if($request->status === 'pending')
            <div class="d-flex flex-wrap gap-2 mt-3">
                <form method="POST" action="{{ route('super-admin.company-requests.approve', $request) }}" data-confirm="Approve this company request?">
                    @csrf
                    <button class="btn btn-primary" type="submit">
                        <i class="fa-solid fa-check" aria-hidden="true"></i>Approve
                    </button>
                </form>
            </div>
            <form method="POST" action="{{ route('super-admin.company-requests.reject', $request) }}" class="mt-4" data-confirm="Reject this company request?" data-confirm-button="Reject request">
                @csrf
                <label for="rejection_reason" class="form-label">Rejection Reason</label>
                <textarea id="rejection_reason" name="rejection_reason" class="form-control @error('rejection_reason') is-invalid @enderror" required rows="4" placeholder="Explain why this company registration cannot be approved.">{{ old('rejection_reason') }}</textarea>
                @error('rejection_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <button class="btn btn-outline-danger mt-2" type="submit">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>Reject Request
                </button>
            </form>
        @endif
    </section>
</div>
@endsection
