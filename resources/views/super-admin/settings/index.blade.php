@extends('layouts.app')

@section('title', 'System Settings - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Super Admin', 'title' => 'System Settings', 'description' => 'Manage safe platform settings. SMTP passwords and .env secrets are never exposed here.'])
<section class="content-card">
    <form method="POST" action="{{ route('super-admin.settings.update') }}" class="row g-3">
        @csrf @method('PUT')
        <div class="col-12"><h2>General</h2></div>
        <div class="col-md-4"><label class="form-label">Platform name</label><input class="form-control" name="platform_name" value="{{ old('platform_name', $settings['platform_name']) }}"></div>
        <div class="col-md-2"><label class="form-label">Abbreviation</label><input class="form-control" name="platform_abbreviation" value="{{ old('platform_abbreviation', $settings['platform_abbreviation']) }}"></div>
        <div class="col-md-3"><label class="form-label">Support email</label><input class="form-control" name="support_email" value="{{ old('support_email', $settings['support_email']) }}"></div>
        <div class="col-md-3"><label class="form-label">Support phone</label><input class="form-control" name="support_phone" value="{{ old('support_phone', $settings['support_phone']) }}"></div>
        <div class="col-md-6"><label class="form-label">Address</label><input class="form-control" name="platform_address" value="{{ old('platform_address', $settings['platform_address']) }}"></div>
        <div class="col-md-3"><label class="form-label">Timezone</label><input class="form-control" name="timezone" value="{{ old('timezone', $settings['timezone']) }}"></div>
        <div class="col-md-3"><label class="form-label">Date format</label><input class="form-control" name="date_format" value="{{ old('date_format', $settings['date_format']) }}"></div>
        <div class="col-12"><h2 class="mt-3">Branding</h2></div>
        <div class="col-md-3"><label class="form-label">Logo path</label><input class="form-control" name="platform_logo" value="{{ old('platform_logo', $settings['platform_logo']) }}"></div>
        <div class="col-md-3"><label class="form-label">Favicon path</label><input class="form-control" name="favicon" value="{{ old('favicon', $settings['favicon']) }}"></div>
        <div class="col-md-3"><label class="form-label">Primary color</label><input class="form-control" name="primary_color" value="{{ old('primary_color', $settings['primary_color']) }}"></div>
        <div class="col-md-3"><label class="form-label">Login background</label><input class="form-control" name="login_background_image" value="{{ old('login_background_image', $settings['login_background_image']) }}"></div>
        <div class="col-12"><h2 class="mt-3">Registration and Subscription</h2></div>
        <div class="col-md-3 form-check ms-2"><input class="form-check-input" type="checkbox" name="registration_enabled" value="1" @checked(old('registration_enabled', $settings['registration_enabled']))><label class="form-check-label">Enable company registration</label></div>
        <div class="col-md-3 form-check"><input class="form-check-input" type="checkbox" name="company_approval_required" value="1" @checked(old('company_approval_required', $settings['company_approval_required']))><label class="form-check-label">Require approval</label></div>
        <div class="col-md-2"><label class="form-label">Trial days</label><input class="form-control" name="trial_duration_days" value="{{ old('trial_duration_days', $settings['trial_duration_days']) }}"></div>
        <div class="col-md-2"><label class="form-label">Currency</label><input class="form-control" name="currency" value="{{ old('currency', $settings['currency']) }}"></div>
        <div class="col-md-2"><label class="form-label">Reminder days</label><input class="form-control" name="subscription_reminder_days" value="{{ old('subscription_reminder_days', $settings['subscription_reminder_days']) }}"></div>
        <div class="col-md-3 form-check ms-2"><input class="form-check-input" type="checkbox" name="allow_trial_plan" value="1" @checked(old('allow_trial_plan', $settings['allow_trial_plan']))><label class="form-check-label">Allow trial plan</label></div>
        <div class="col-md-3 form-check"><input class="form-check-input" type="checkbox" name="allow_plan_upgrade" value="1" @checked(old('allow_plan_upgrade', $settings['allow_plan_upgrade']))><label class="form-check-label">Allow plan upgrade</label></div>
        <div class="col-12"><h2 class="mt-3">Mail Display</h2></div>
        <div class="col-md-6"><label class="form-label">Sender name</label><input class="form-control" name="email_sender_name" value="{{ old('email_sender_name', $settings['email_sender_name']) }}"></div>
        <div class="col-md-6"><label class="form-label">Sender email display</label><input class="form-control" name="email_sender_email" value="{{ old('email_sender_email', $settings['email_sender_email']) }}"></div>
        <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Settings</button></div>
    </form>
</section>
@endsection
