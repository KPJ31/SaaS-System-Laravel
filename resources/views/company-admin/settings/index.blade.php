@extends('layouts.app')

@section('title', 'Company Settings - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Settings', 'title' => 'Company Settings', 'description' => 'Company behavior settings. Personal profile and company identity are managed separately.'])
@php
    $settings = $setting->settings ?? [];
    $attendance = array_replace(\App\Models\CompanySetting::defaultAttendanceSettings(), $settings['attendance'] ?? []);
    $timezones = DateTimeZone::listIdentifiers();
    $currencies = ['USD', 'LKR', 'EUR', 'GBP', 'AUD', 'CAD', 'INR', 'SGD'];
    $projectStatuses = ['planning', 'pending', 'approved', 'active', 'in_progress', 'on_hold', 'testing', 'completed', 'cancelled'];
@endphp

<form class="content-card" method="POST" action="{{ route('company-admin.settings.update') }}" data-loading-form>
    @csrf
    @method('PUT')
    <div class="content-card-header"><div><h2>General Settings</h2><p>Timezone affects attendance, calendar display and date-based reports. Historical timestamps are not rewritten.</p></div></div>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="timezone">Timezone</label><select class="form-select @error('timezone') is-invalid @enderror" id="timezone" name="timezone" required>@foreach($timezones as $timezone)<option value="{{ $timezone }}" @selected(old('timezone', $setting->timezone) === $timezone)>{{ $timezone }}</option>@endforeach</select>@error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" for="currency">Currency</label><select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency" required>@foreach($currencies as $currency)<option value="{{ $currency }}" @selected(old('currency', $setting->currency) === $currency)>{{ $currency }}</option>@endforeach</select>@error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" for="invoice_prefix">Invoice Prefix</label><input class="form-control @error('invoice_prefix') is-invalid @enderror" id="invoice_prefix" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}">@error('invoice_prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    </div>

    <div class="content-card-header mt-4"><div><h2>Finance Defaults</h2><p>Used by invoice creation and payment-facing company finance screens.</p></div></div>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="default_tax_percentage">Default Tax %</label><input class="form-control @error('default_tax_percentage') is-invalid @enderror" id="default_tax_percentage" type="number" step="0.01" min="0" max="100" name="default_tax_percentage" value="{{ old('default_tax_percentage', $settings['default_tax_percentage'] ?? 0) }}">@error('default_tax_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label" for="default_project_status">Default Project Status</label><select class="form-select @error('default_project_status') is-invalid @enderror" id="default_project_status" name="default_project_status" required>@foreach($projectStatuses as $status)<option value="{{ $status }}" @selected(old('default_project_status', $settings['default_project_status'] ?? 'planning') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select>@error('default_project_status')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label" for="default_task_priority">Default Task Priority</label><select class="form-select @error('default_task_priority') is-invalid @enderror" id="default_task_priority" name="default_task_priority">@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('default_task_priority', $settings['default_task_priority'] ?? 'medium') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select>@error('default_task_priority')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-12"><label class="form-label" for="payment_instructions">Payment Instructions</label><textarea class="form-control @error('payment_instructions') is-invalid @enderror" id="payment_instructions" name="payment_instructions" rows="4">{{ old('payment_instructions', $settings['payment_instructions'] ?? '') }}</textarea>@error('payment_instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    </div>

    <div class="content-card-header mt-4"><div><h2>Company Notifications</h2><p>Company-level operational notification defaults. Personal notification preferences are deferred.</p></div></div>
    <div class="d-flex flex-wrap gap-3">
        <label class="form-check"><input class="form-check-input" type="checkbox" name="email_notifications" value="1" @checked(old('email_notifications', $settings['email_notifications'] ?? false))> Email notifications</label>
        <label class="form-check"><input class="form-check-input" type="checkbox" name="task_due_reminder" value="1" @checked(old('task_due_reminder', $settings['task_due_reminder'] ?? false))> Task due reminder</label>
        <label class="form-check"><input class="form-check-input" type="checkbox" name="payment_reminder" value="1" @checked(old('payment_reminder', $settings['payment_reminder'] ?? false))> Payment reminder</label>
    </div>

    <div class="content-card-header mt-4"><div><h2>Working Hours and Attendance</h2><p>Used by check-in, check-out, absence marking and attendance reporting.</p></div></div>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label" for="work_start_time">Work Start Time</label><input class="form-control @error('work_start_time') is-invalid @enderror" id="work_start_time" type="time" name="work_start_time" value="{{ old('work_start_time', $attendance['work_start_time']) }}" required>@error('work_start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" for="work_end_time">Work End Time</label><input class="form-control @error('work_end_time') is-invalid @enderror" id="work_end_time" type="time" name="work_end_time" value="{{ old('work_end_time', $attendance['work_end_time']) }}" required>@error('work_end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" for="lunch_break_minutes">Lunch Break Minutes</label><input class="form-control @error('lunch_break_minutes') is-invalid @enderror" id="lunch_break_minutes" type="number" min="0" max="180" name="lunch_break_minutes" value="{{ old('lunch_break_minutes', $attendance['lunch_break_minutes']) }}" required>@error('lunch_break_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" for="late_grace_minutes">Late Grace Minutes</label><input class="form-control @error('late_grace_minutes') is-invalid @enderror" id="late_grace_minutes" type="number" min="0" max="120" name="late_grace_minutes" value="{{ old('late_grace_minutes', $attendance['late_grace_minutes']) }}" required>@error('late_grace_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" for="early_check_in_allowance_minutes">Early Check-In Allowance</label><input class="form-control @error('early_check_in_allowance_minutes') is-invalid @enderror" id="early_check_in_allowance_minutes" type="number" min="0" max="180" name="early_check_in_allowance_minutes" value="{{ old('early_check_in_allowance_minutes', $attendance['early_check_in_allowance_minutes']) }}" required>@error('early_check_in_allowance_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" for="early_departure_grace_minutes">Early Departure Grace</label><input class="form-control @error('early_departure_grace_minutes') is-invalid @enderror" id="early_departure_grace_minutes" type="number" min="0" max="120" name="early_departure_grace_minutes" value="{{ old('early_departure_grace_minutes', $attendance['early_departure_grace_minutes']) }}" required>@error('early_departure_grace_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" for="full_day_minutes">Full-Day Minutes</label><input class="form-control @error('full_day_minutes') is-invalid @enderror" id="full_day_minutes" type="number" min="1" max="1440" name="full_day_minutes" value="{{ old('full_day_minutes', $attendance['full_day_minutes']) }}" required>@error('full_day_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label" for="half_day_minutes">Half-Day Minutes</label><input class="form-control @error('half_day_minutes') is-invalid @enderror" id="half_day_minutes" type="number" min="1" max="1440" name="half_day_minutes" value="{{ old('half_day_minutes', $attendance['half_day_minutes']) }}" required>@error('half_day_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
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

    <button class="btn btn-primary mt-4" type="submit" data-loading-text="Saving company settings..."><i class="fa-solid fa-floppy-disk"></i>Save Changes</button>
</form>
@endsection
