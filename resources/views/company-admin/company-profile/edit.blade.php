@extends('layouts.app')

@section('title', 'Edit Company Profile - Elevanix')

@section('content')
@include('partials.page-header', [
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('company-admin.dashboard')],
        ['label' => 'Company Profile', 'url' => route('company-admin.company-profile.show')],
        ['label' => 'Edit'],
    ],
    'eyebrow' => 'Company Profile',
    'title' => 'Edit Company Profile',
    'description' => 'Update company information your team sees across the workspace.',
])
@php
    $timezones = DateTimeZone::listIdentifiers();
    $dateFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd M Y', 'M d, Y'];
@endphp

<form class="content-card app-card form-page-container" method="POST" action="{{ route('company-admin.company-profile.update') }}" enctype="multipart/form-data" data-loading-form>
    @csrf
    @method('PUT')

    <div class="content-card-header">
        <div>
            <h2>Company Identity</h2>
            <p>These fields describe your organization inside Elevanix.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="name">Company Name <span class="required-mark">*</span></label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $company->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="email">Email <span class="required-mark">*</span></label><input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email', $company->email) }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="phone">Phone</label><input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $company->phone) }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="website">Website</label><input class="form-control @error('website') is-invalid @enderror" id="website" type="url" name="website" value="{{ old('website', $company->website) }}">@error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="business_type">Business Type</label><input class="form-control @error('business_type') is-invalid @enderror" id="business_type" name="business_type" value="{{ old('business_type', $company->business_type) }}">@error('business_type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" for="timezone">Timezone <span class="required-mark">*</span></label><select class="form-select @error('timezone') is-invalid @enderror" id="timezone" name="timezone" required>@foreach($timezones as $timezone)<option value="{{ $timezone }}" @selected(old('timezone', $company->timezone ?? 'UTC') === $timezone)>{{ $timezone }}</option>@endforeach</select>@error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" for="date_format">Date Format <span class="required-mark">*</span></label><select class="form-select @error('date_format') is-invalid @enderror" id="date_format" name="date_format" required>@foreach($dateFormats as $format)<option value="{{ $format }}" @selected(old('date_format', $company->date_format ?? 'Y-m-d') === $format)>{{ now()->format($format) }} ({{ $format }})</option>@endforeach</select>@error('date_format')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12"><label class="form-label" for="address">Address</label><textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $company->address) }}</textarea>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $company->description) }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="logo">Logo</label><input class="form-control @error('logo') is-invalid @enderror" id="logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" data-image-preview="#company-logo-preview">@error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="helper-text">JPG, PNG or WEBP up to 2MB.</small></div>
        <div class="col-md-6"><img id="company-logo-preview" src="{{ $company->logo_path ? asset('storage/'.$company->logo_path) : '' }}" alt="" style="width:72px;height:72px;border-radius:8px;object-fit:cover;{{ $company->logo_path ? '' : 'display:none;' }}"></div>
    </div>

    <div class="content-card-header mt-4">
        <div>
            <h2>Read-only Platform Fields</h2>
            <p>Company approval status and subscription state are controlled by platform workflows.</p>
        </div>
    </div>
    <dl class="detail-list">
        <dt>Status</dt><dd>@include('partials.status-badge', ['status' => $company->status])</dd>
        <dt>Created</dt><dd>{{ $company->created_at->format('Y-m-d') }}</dd>
    </dl>

    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="{{ route('company-admin.company-profile.show') }}">Cancel</a>
        <button class="btn btn-primary" type="submit" data-loading-text="Saving company profile...">
            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
            Save changes
        </button>
    </div>
</form>
@endsection
