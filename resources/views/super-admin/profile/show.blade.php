@extends('layouts.app')

@section('title', 'My Profile - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Super Admin', 'title' => 'My Profile', 'description' => 'Manage your personal account separately from platform settings.'])
@include('partials.profile-header-card', ['user' => $user, 'roleLabel' => 'Super Admin', 'status' => $user->status])

<div class="content-grid">
    <form class="content-card" method="POST" action="{{ route('super-admin.profile.update') }}" enctype="multipart/form-data" data-loading-form>
        @csrf
        @method('PUT')
        <div class="content-card-header"><div><h2>Personal Information</h2><p>Name, email and contact details for your account.</p></div></div>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="name">Name</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="username">Username</label><input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}" required>@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="phone">Phone</label><input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="avatar">Profile image</label><input class="form-control @error('avatar') is-invalid @enderror" id="avatar" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" data-image-preview="#super-admin-avatar-preview">@error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="helper-text">JPG, PNG or WEBP up to 2MB.</small></div>
            <div class="col-12"><img id="super-admin-avatar-preview" src="{{ $user->avatar ? asset('storage/'.$user->avatar) : '' }}" alt="" style="width:72px;height:72px;border-radius:50%;object-fit:cover;{{ $user->avatar ? '' : 'display:none;' }}"></div>
            @if($user->avatar)<div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="remove_avatar" value="1"> Remove current image</label></div>@endif
        </div>
        <button class="btn btn-primary mt-4" type="submit" data-loading-text="Saving profile..."><i class="fa-solid fa-floppy-disk"></i>Save Profile</button>
    </form>

    <div>
        <section class="content-card mb-3">
            <div class="content-card-header"><div><h2>Account Information</h2><p>Read-only platform account details.</p></div></div>
            <dl class="detail-list mt-3">
                <dt>Role</dt><dd>Super Admin</dd>
                <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $user->status])</dd>
                <dt>Created</dt><dd>{{ $user->created_at->format('Y-m-d') }}</dd>
                <dt>Last Login</dt><dd>{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Not recorded' }}</dd>
            </dl>
        </section>
        <section class="content-card" id="change-password">
            <div class="content-card-header"><div><h2>Security</h2><p>Change your password with current-password verification.</p></div></div>
            <form method="POST" action="{{ route('super-admin.profile.password') }}" class="row g-3 mt-1" data-loading-form>
                @csrf
                @method('PUT')
                <div class="col-12"><label class="form-label" for="current_password">Current password</label><div class="input-group"><input class="form-control @error('current_password') is-invalid @enderror" id="current_password" type="password" name="current_password" required><button class="btn btn-outline-secondary" type="button" data-password-toggle="#current_password" aria-label="Show current password"><i class="fa-regular fa-eye"></i></button></div>@error('current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="password">New password</label><div class="input-group"><input class="form-control @error('password') is-invalid @enderror" id="password" type="password" name="password" required data-password-strength><button class="btn btn-outline-secondary" type="button" data-password-toggle="#password" aria-label="Show new password"><i class="fa-regular fa-eye"></i></button></div><div class="password-strength" data-password-strength-output><span></span><small>Use at least 8 characters with a mix of letters, numbers and symbols.</small></div>@error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="password_confirmation">Confirm password</label><div class="input-group"><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" required><button class="btn btn-outline-secondary" type="button" data-password-toggle="#password_confirmation" aria-label="Show password confirmation"><i class="fa-regular fa-eye"></i></button></div></div>
                <div class="col-12"><button class="btn btn-primary" type="submit" data-loading-text="Changing password..."><i class="fa-solid fa-key"></i>Change Password</button></div>
            </form>
        </section>
    </div>
</div>
@endsection
