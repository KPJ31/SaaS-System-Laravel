@extends('layouts.app')

@section('title', 'Company Settings - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Settings', 'title' => 'Company Settings', 'description' => 'Update company-level defaults without changing platform settings.'])
@php($settings = $setting->settings ?? [])
@php($attendance = array_replace(\App\Models\CompanySetting::defaultAttendanceSettings(), $settings['attendance'] ?? []))
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
    <hr class="my-4">
    <div class="content-card-header"><div><h2>Working Hours and Attendance</h2><p>Configure daily attendance rules for Employees in this company.</p></div></div>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Work Start Time</label><input class="form-control" type="time" name="work_start_time" value="{{ old('work_start_time', $attendance['work_start_time']) }}" required></div>
        <div class="col-md-3"><label class="form-label">Work End Time</label><input class="form-control" type="time" name="work_end_time" value="{{ old('work_end_time', $attendance['work_end_time']) }}" required></div>
        <div class="col-md-3"><label class="form-label">Lunch Break Minutes</label><input class="form-control" type="number" min="0" max="180" name="lunch_break_minutes" value="{{ old('lunch_break_minutes', $attendance['lunch_break_minutes']) }}" required></div>
        <div class="col-md-3"><label class="form-label">Late Grace Minutes</label><input class="form-control" type="number" min="0" max="120" name="late_grace_minutes" value="{{ old('late_grace_minutes', $attendance['late_grace_minutes']) }}" required></div>
        <div class="col-md-3"><label class="form-label">Early Check-In Allowance</label><input class="form-control" type="number" min="0" max="180" name="early_check_in_allowance_minutes" value="{{ old('early_check_in_allowance_minutes', $attendance['early_check_in_allowance_minutes']) }}" required></div>
        <div class="col-md-3"><label class="form-label">Early Departure Grace</label><input class="form-control" type="number" min="0" max="120" name="early_departure_grace_minutes" value="{{ old('early_departure_grace_minutes', $attendance['early_departure_grace_minutes']) }}" required></div>
        <div class="col-md-3"><label class="form-label">Full-Day Minutes</label><input class="form-control" type="number" min="1" max="1440" name="full_day_minutes" value="{{ old('full_day_minutes', $attendance['full_day_minutes']) }}" required></div>
        <div class="col-md-3"><label class="form-label">Half-Day Minutes</label><input class="form-control" type="number" min="1" max="1440" name="half_day_minutes" value="{{ old('half_day_minutes', $attendance['half_day_minutes']) }}" required></div>
        <div class="col-12 d-flex flex-wrap gap-3">
            <label class="form-check"><input class="form-check-input" type="checkbox" name="attendance_enabled" value="1" @checked(old('attendance_enabled', $attendance['attendance_enabled']))> Attendance tracking enabled</label>
            <label class="form-check"><input class="form-check-input" type="checkbox" name="auto_absence_enabled" value="1" @checked(old('auto_absence_enabled', $attendance['auto_absence_enabled']))> Automatic absence marking enabled</label>
        </div>
        <div class="col-12">
            <label class="form-label">Working Days</label>
            <div class="d-flex flex-wrap gap-3">
                @foreach([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $dayNumber => $dayName)
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="working_days[]" value="{{ $dayNumber }}" @checked(in_array($dayNumber, old('working_days', $attendance['working_days'])))> {{ $dayName }}</label>
                @endforeach
            </div>
            @error('working_days')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>
    <button class="btn btn-primary mt-4" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save settings</button>
</form>
@endsection
