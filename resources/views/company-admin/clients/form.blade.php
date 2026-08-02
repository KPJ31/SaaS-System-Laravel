@extends('layouts.app')

@section('title', ($client->exists ? 'Edit' : 'Add').' Client - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Clients', 'title' => $client->exists ? 'Edit Client' : 'Add Client'])
<form class="content-card" method="POST" action="{{ $client->exists ? route('company-admin.clients.update', $client) : route('company-admin.clients.store') }}" data-loading-form>
    @csrf @if($client->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Name <span class="required-mark">*</span></label><input class="form-control" name="name" value="{{ old('name', $client->name) }}" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $client->email) }}"></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone', $client->phone) }}"></div>
        <div class="col-md-4"><label class="form-label">Organization</label><input class="form-control" name="company_name" value="{{ old('company_name', $client->company_name) }}"></div>
        <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status">@foreach(['active','inactive','blocked'] as $status)<option value="{{ $status }}" @selected(old('status', $client->status ?: 'active')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="3">{{ old('address', $client->address) }}</textarea></div>
        <div class="col-md-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3">{{ old('notes', $client->notes) }}</textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save client</button><a class="btn btn-outline-primary" href="{{ route('company-admin.clients.index') }}">Cancel</a></div>
</form>
@endsection
