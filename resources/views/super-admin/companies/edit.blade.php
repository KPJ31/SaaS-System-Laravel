@extends('layouts.app')

@section('title', 'Edit Company - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Companies', 'title' => 'Edit '.$company->name, 'description' => 'Update safe company profile fields and status.'])

<section class="content-card">
    <form method="POST" action="{{ route('super-admin.companies.update', $company) }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Company name <span class="required-mark">*</span></label><input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $company->name) }}">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Company email <span class="required-mark">*</span></label><input class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $company->email) }}">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone', $company->phone) }}"></div>
            <div class="col-md-6"><label class="form-label">Website</label><input class="form-control" name="website" value="{{ old('website', $company->website) }}"></div>
            <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status">@foreach(['pending', 'active', 'suspended', 'rejected'] as $status)<option value="{{ $status }}" @selected(old('status', $company->status) === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="3">{{ old('address', $company->address) }}</textarea></div>
        </div>
        <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button><a class="btn btn-outline-primary" href="{{ route('super-admin.companies.show', $company) }}">Cancel</a></div>
    </form>
</section>
@endsection
