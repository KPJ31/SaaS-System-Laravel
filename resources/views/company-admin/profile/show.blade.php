@extends('layouts.app')

@section('title', 'My Profile - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Company Admin', 'title' => 'My Profile', 'description' => 'Update your account details and password.'])
<div class="content-grid">
    <form class="content-card" method="POST" action="{{ route('company-admin.profile.update') }}" enctype="multipart/form-data" data-loading-form>@csrf @method('PUT')<h2>Profile Details</h2><div class="row g-3 mt-1"><div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ old('name', $user->name) }}" required></div><div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" required></div><div class="col-md-6"><label class="form-label">Username</label><input class="form-control" name="username" value="{{ old('username', $user->username) }}"></div><div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone', $user->phone) }}"></div><div class="col-md-12"><label class="form-label">Profile Image</label><input class="form-control" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp"></div></div><button class="btn btn-primary mt-4" type="submit">Save profile</button></form>
    <form class="content-card" id="change-password" method="POST" action="{{ route('company-admin.profile.password') }}" data-loading-form>@csrf @method('PUT')<h2>Change Password</h2><div class="row g-3 mt-1"><div class="col-md-12"><label class="form-label">Current Password</label><input class="form-control" type="password" name="current_password" required></div><div class="col-md-12"><label class="form-label">New Password</label><input class="form-control" type="password" name="password" required></div><div class="col-md-12"><label class="form-label">Confirm Password</label><input class="form-control" type="password" name="password_confirmation" required></div></div><button class="btn btn-primary mt-4" type="submit">Update password</button></form>
</div>
@endsection
