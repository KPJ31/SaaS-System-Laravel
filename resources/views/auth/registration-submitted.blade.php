@extends('layouts.auth')

@section('title', 'Registration Submitted - Elevanix')

@section('content')
<div class="submitted-page">
    <div class="submitted-card">
        @include('partials.brand-logo')
        <i class="fa-solid fa-circle-check"></i>
        <h1>Registration Request Submitted</h1>
        <p>Your company registration request has been received and is waiting for Super Admin approval. You will receive an email after it is reviewed.</p>
        <a href="{{ route('login') }}" class="btn btn-primary">Go to Sign In</a>
    </div>
</div>
@endsection
