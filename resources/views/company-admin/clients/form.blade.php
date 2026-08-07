@extends('layouts.app')

@section('title', ($client->exists ? 'Edit' : 'Add').' Client - Elevanix')

@section('content')
@include('partials.page-header', [
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('company-admin.dashboard')],
        ['label' => 'Clients', 'url' => route('company-admin.clients.index')],
        ['label' => $client->exists ? 'Edit Client' : 'Add Client'],
    ],
    'eyebrow' => 'Clients',
    'title' => $client->exists ? 'Edit Client' : 'Add Client',
    'description' => $client->exists ? 'Update client contact and business information.' : 'Create a client record for this company workspace.',
])

<form class="content-card app-card form-page-container" method="POST" action="{{ $client->exists ? route('company-admin.clients.update', $client) : route('company-admin.clients.store') }}" data-loading-form>
    @csrf
    @if($client->exists)
        @method('PUT')
    @endif

    <div class="content-card-header">
        <div>
            <h2>Client Information</h2>
            <p>Keep contact and organization details accurate for projects, invoices and requests.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="name">Name <span class="required-mark">*</span></label>
            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $client->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="company_name">Organization</label>
            <input class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name', $client->company_name) }}">
            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="email">Email</label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email', $client->email) }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="phone">Phone</label>
            <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $client->phone) }}">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="status">Status</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                @foreach(['active','inactive','blocked'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $client->status ?: 'active')===$status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="address">Address</label>
            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $client->address) }}</textarea>
            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="notes">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4">{{ old('notes', $client->notes) }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="{{ route('company-admin.clients.index') }}">Cancel</a>
        <button class="btn btn-primary" type="submit" data-loading-text="Saving client...">
            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
            Save client
        </button>
    </div>
</form>
@endsection
