@extends('layouts.app')

@section('title', 'Edit Company Profile - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Company Profile', 'title' => 'Edit Company Profile'])

<form class="content-card" method="POST" action="{{ route('company-admin.company-profile.update') }}" enctype="multipart/form-data" data-loading-form>
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Company Name <span class="required-mark">*</span></label><input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $company->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label">Email <span class="required-mark">*</span></label><input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $company->email) }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone', $company->phone) }}"></div>
        <div class="col-md-6"><label class="form-label">Website</label><input class="form-control" type="url" name="website" value="{{ old('website', $company->website) }}"></div>
        <div class="col-md-6"><label class="form-label">Business Type</label><input class="form-control" name="business_type" value="{{ old('business_type', $company->business_type) }}"></div>
        <div class="col-md-3"><label class="form-label">Timezone <span class="required-mark">*</span></label><input class="form-control" name="timezone" value="{{ old('timezone', $company->timezone ?? 'UTC') }}" required></div>
        <div class="col-md-3"><label class="form-label">Date Format <span class="required-mark">*</span></label><input class="form-control" name="date_format" value="{{ old('date_format', $company->date_format ?? 'Y-m-d') }}" required></div>
        <div class="col-md-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="3">{{ old('address', $company->address) }}</textarea></div>
        <div class="col-md-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4">{{ old('description', $company->description) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Logo</label><input class="form-control @error('logo') is-invalid @enderror" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp">@error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="helper-text">JPG, PNG or WEBP up to 2MB.</small></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save changes</button><a class="btn btn-outline-primary" href="{{ route('company-admin.company-profile.show') }}">Cancel</a></div>
</form>
@endsection
