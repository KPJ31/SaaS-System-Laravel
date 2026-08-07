@extends('layouts.app')

@section('title', 'Change Password - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Employee', 'title' => 'Change Password', 'description' => 'Use your current password to protect your account.'])

<section class="content-card">
    <div class="content-card-header"><div><h2>Account Security</h2><p>Password changes require your current password.</p></div></div>
    <form method="POST" action="{{ route('employee.password.update') }}" data-loading-form>
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="current_password">Current password</label><div class="input-group"><input class="form-control @error('current_password') is-invalid @enderror" id="current_password" type="password" name="current_password" required><button class="btn btn-outline-secondary" type="button" data-password-toggle="#current_password" aria-label="Show current password"><i class="fa-regular fa-eye"></i></button></div>@error('current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label" for="password">New password</label><div class="input-group"><input class="form-control @error('password') is-invalid @enderror" id="password" type="password" name="password" required data-password-strength><button class="btn btn-outline-secondary" type="button" data-password-toggle="#password" aria-label="Show new password"><i class="fa-regular fa-eye"></i></button></div><div class="password-strength" data-password-strength-output><span></span><small>Use at least 8 characters with a mix of letters, numbers and symbols.</small></div>@error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label" for="password_confirmation">Confirm password</label><div class="input-group"><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" required><button class="btn btn-outline-secondary" type="button" data-password-toggle="#password_confirmation" aria-label="Show password confirmation"><i class="fa-regular fa-eye"></i></button></div></div>
        </div>
        <button class="btn btn-primary mt-4" type="submit" data-loading-text="Changing password..."><i class="fa-solid fa-key"></i>Change Password</button>
    </form>
</section>
@endsection
