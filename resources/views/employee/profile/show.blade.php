@extends('layouts.app')

@section('title', 'My Profile - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Employee', 'title' => 'My Profile', 'description' => 'Manage personal contact details. Employment details are read-only.'])
@include('partials.profile-header-card', ['user' => $user, 'roleLabel' => $user->job_title ?: 'Employee', 'subtitle' => $user->email, 'status' => $user->status])

<div class="content-grid">
    <form class="content-card" method="POST" action="{{ route('employee.profile.update') }}" enctype="multipart/form-data" data-loading-form>
        @csrf
        @method('PUT')
        <div class="content-card-header"><div><h2>Personal Information</h2><p>These are the contact fields you can keep current yourself.</p></div></div>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="name">Name</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="username">Username</label><input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}">@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="phone">Phone</label><input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="address">Address</label><textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $user->address) }}</textarea>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="avatar">Profile image</label><input class="form-control @error('avatar') is-invalid @enderror" id="avatar" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" data-image-preview="#employee-avatar-preview">@error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="helper-text">JPG, PNG or WEBP up to 2MB.</small></div>
            <div class="col-12"><img id="employee-avatar-preview" src="{{ $user->avatar ? asset('storage/'.$user->avatar) : '' }}" alt="" style="width:72px;height:72px;border-radius:50%;object-fit:cover;{{ $user->avatar ? '' : 'display:none;' }}"></div>
            @if($user->avatar)<div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="remove_avatar" value="1"> Remove current image</label></div>@endif
        </div>
        <button class="btn btn-primary mt-4" type="submit" data-loading-text="Updating profile..."><i class="fa-solid fa-floppy-disk"></i>Update Profile</button>
    </form>

    <div>
        <section class="content-card mb-3">
            <div class="content-card-header"><div><h2>Employment Details</h2><p>Managed by your Company Admin.</p></div></div>
            <dl class="detail-list mt-3">
                <dt>Company</dt><dd>{{ $user->company?->name ?? '-' }}</dd>
                <dt>Role</dt><dd>Employee</dd>
                <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $user->status])</dd>
                <dt>Employee Code</dt><dd>{{ $user->employee_code ?? '-' }}</dd>
                <dt>Job Title</dt><dd>{{ $user->job_title ?? '-' }}</dd>
                <dt>Department</dt><dd>{{ $user->department ?? '-' }}</dd>
                <dt>Joined</dt><dd>{{ $user->join_date?->format('Y-m-d') ?? '-' }}</dd>
            </dl>
        </section>
        <section class="content-card">
            <div class="content-card-header"><div><h2>Account Information</h2><p>Read-only login and account context.</p></div></div>
            <dl class="detail-list mt-3">
                <dt>Created</dt><dd>{{ $user->created_at->format('Y-m-d') }}</dd>
                <dt>Last Login</dt><dd>{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Not recorded' }}</dd>
                <dt>Password</dt><dd><a href="{{ route('employee.password.edit') }}">Change password</a></dd>
            </dl>
        </section>
    </div>
</div>
@endsection
