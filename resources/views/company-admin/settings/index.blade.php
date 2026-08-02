@extends('layouts.app')

@section('title', 'Company Settings - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Settings', 'title' => 'Company Settings', 'description' => 'Update company-level defaults without changing platform settings.'])
@php($settings = $setting->settings ?? [])
<form class="content-card" method="POST" action="{{ route('company-admin.settings.update') }}" data-loading-form>
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Timezone</label><input class="form-control" name="timezone" value="{{ old('timezone', $setting->timezone) }}" required></div>
        <div class="col-md-4"><label class="form-label">Currency</label><input class="form-control" name="currency" value="{{ old('currency', $setting->currency) }}" required></div>
        <div class="col-md-4"><label class="form-label">Invoice Prefix</label><input class="form-control" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}"></div>
        <div class="col-md-4"><label class="form-label">Default Tax %</label><input class="form-control" type="number" step="0.01" name="default_tax_percentage" value="{{ old('default_tax_percentage', $settings['default_tax_percentage'] ?? 0) }}"></div>
        <div class="col-md-4"><label class="form-label">Default Project Status</label><input class="form-control" name="default_project_status" value="{{ old('default_project_status', $settings['default_project_status'] ?? 'planning') }}" required></div>
        <div class="col-md-4"><label class="form-label">Default Task Priority</label><select class="form-select" name="default_task_priority">@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('default_task_priority', $settings['default_task_priority'] ?? 'medium')===$priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
        <div class="col-md-12"><label class="form-label">Payment Instructions</label><textarea class="form-control" name="payment_instructions" rows="4">{{ old('payment_instructions', $settings['payment_instructions'] ?? '') }}</textarea></div>
        <div class="col-md-12 d-flex flex-wrap gap-3"><label class="form-check"><input class="form-check-input" type="checkbox" name="email_notifications" value="1" @checked($settings['email_notifications'] ?? false)> Email notifications</label><label class="form-check"><input class="form-check-input" type="checkbox" name="task_due_reminder" value="1" @checked($settings['task_due_reminder'] ?? false)> Task due reminder</label><label class="form-check"><input class="form-check-input" type="checkbox" name="payment_reminder" value="1" @checked($settings['payment_reminder'] ?? false)> Payment reminder</label></div>
    </div>
    <button class="btn btn-primary mt-4" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save settings</button>
</form>
@endsection
