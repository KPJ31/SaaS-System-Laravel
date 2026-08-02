@extends('layouts.app')
@section('title', 'Change Password - Elevanix')
@section('content')
@include('partials.page-header', ['eyebrow' => 'Employee', 'title' => 'Change Password', 'description' => 'Use a secure password and keep it private.'])
<section class="content-card"><form method="POST" action="{{ route('employee.password.update') }}" data-loading-form>@csrf @method('PUT')<div class="row g-3"><div class="col-md-4"><label class="form-label">Current password</label><input class="form-control" type="password" name="current_password" required></div><div class="col-md-4"><label class="form-label">New password</label><input class="form-control" type="password" name="password" required data-password-strength><div class="password-strength" data-password-strength-output><span></span><small>Use at least 8 characters with a mix of letters, numbers and symbols.</small></div></div><div class="col-md-4"><label class="form-label">Confirm password</label><input class="form-control" type="password" name="password_confirmation" required></div></div><div class="mt-3"><button class="btn btn-primary"><i class="fa-solid fa-key"></i>Change Password</button></div></form></section>
@endsection
