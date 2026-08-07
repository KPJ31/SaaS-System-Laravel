@extends('layouts.auth')

@section('title', 'Registration Submitted | Elevanix')

@section('content')
<div class="submitted-page">
    <div class="submitted-card">
        <a href="{{ route('home') }}" class="text-decoration-none"><x-brand-logo /></a>
        <div class="success-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div>
        <span class="form-badge">Request Received</span>
        <h1>Registration Submitted</h1>
        <p>Your company registration request has been received and is awaiting platform review. The approval result will be sent by email.</p>
        @if(session('company_registration_email'))
            <p class="submitted-email">Confirmation email: <strong>{{ session('company_registration_email') }}</strong></p>
        @endif
        <div class="next-steps">
            @foreach([
                ['Request submitted', 'Your company and administrator details were received.'],
                ['Platform review', 'A Super Admin reviews the company request.'],
                ['Company approval', 'Approval creates your company workspace and Company Admin account.'],
                ['Workspace activation', 'The Company Admin can sign in using the password created during registration.'],
            ] as $index => [$title, $text])
                <article>
                    <span>{{ $index + 1 }}</span>
                    <strong>{{ $title }}</strong>
                    <small>{{ $text }}</small>
                </article>
            @endforeach
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ route('login') }}" class="btn btn-primary">Sign In</a>
            <a href="{{ route('home') }}" class="btn btn-outline-primary">Return to Home</a>
        </div>
        <p class="auth-note">Need help? Contact {{ config('mail.from.address', 'noreply@example.com') }}.</p>
    </div>
</div>
@endsection
