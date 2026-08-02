@extends('layouts.app')

@section('title', 'Manage Permissions - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Employee Permissions',
    'title' => 'Manage '.$employee->name,
    'description' => 'Assign selected company features while keeping this user in the Employee role.',
    'actions' => new \Illuminate\Support\HtmlString('<a class="btn btn-outline-primary" href="'.route('company-admin.employees.show', $employee).'"><i class="fa-solid fa-arrow-left"></i>Employee details</a>')
])

<style>
    .permission-page { font-family: Poppins, sans-serif; color: #1F2937; }
    .permission-profile { display: grid; grid-template-columns: 72px 1fr; gap: 16px; align-items: center; }
    .permission-avatar { width: 72px; height: 72px; border-radius: 8px; object-fit: cover; background: #F5F3FF; border: 1px solid #E9D5FF; display: grid; place-items: center; color: #6D28D9; font-size: 24px; }
    .permission-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
    .permission-summary div { border: 1px solid #E9D5FF; border-radius: 8px; padding: 12px; background: #FFFFFF; }
    .permission-summary strong { display: block; font-size: 20px; color: #4C1D95; }
    .permission-summary span { color: #6B7280; font-size: 13px; }
    .permission-toolbar { position: sticky; top: 76px; z-index: 4; border: 1px solid #E9D5FF; border-radius: 8px; background: #FFFFFF; padding: 12px; box-shadow: 0 8px 24px rgba(76, 29, 149, .08); }
    .permission-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; }
    .permission-card { border: 1px solid #E9D5FF; border-radius: 8px; background: #FFFFFF; padding: 16px; }
    .permission-card h2 { font-size: 16px; margin: 0; color: #4C1D95; }
    .permission-card p { font-size: 13px; margin: 4px 0 12px; color: #6B7280; }
    .permission-card .form-check { margin-bottom: 8px; font-size: 14px; }
    .permission-card .form-check-label { overflow-wrap: anywhere; }
    .permission-page .form-check-input:checked { background-color: #6D28D9; border-color: #6D28D9; }
    .permission-page .form-check-input:focus { border-color: #8B5CF6; box-shadow: 0 0 0 .2rem rgba(139, 92, 246, .2); }
    .permission-muted { color: #6B7280; font-size: 13px; }
    .permission-hidden { display: none; }
    @media (max-width: 767px) {
        .permission-toolbar { position: static; }
        .permission-profile { grid-template-columns: 1fr; }
    }
</style>

<div class="permission-page">
    <div class="content-grid mb-3">
        <section class="content-card">
            <div class="permission-profile">
                @if($employee->avatar)
                    <img class="permission-avatar" src="{{ asset('storage/'.$employee->avatar) }}" alt="{{ $employee->name }}">
                @else
                    <div class="permission-avatar"><i class="fa-solid fa-user"></i></div>
                @endif
                <div>
                    <h2 class="mb-1">{{ $employee->name }}</h2>
                    <p class="permission-muted mb-1">{{ $employee->email }}</p>
                    <p class="permission-muted mb-0">{{ $employee->job_title ?? 'No job title' }} | {{ $employee->department ?? 'No department' }}</p>
                    <p class="permission-muted mb-0">Role: {{ str_replace('_', ' ', $employee->role) }} | Status: {{ ucfirst($employee->status) }}</p>
                </div>
            </div>
        </section>

        <section class="content-card">
            <div class="permission-summary">
                <div><strong data-selected-count>{{ count($assignedPermissions) }}</strong><span>Permissions assigned</span></div>
                <div><strong data-module-count>0</strong><span>Modules accessible</span></div>
                <div><strong>{{ $latestUpdate?->created_at?->diffForHumans() ?? 'Never' }}</strong><span>Last permission update</span></div>
                <div><strong>{{ $latestUpdate?->user?->name ?? '-' }}</strong><span>Updated by</span></div>
            </div>
        </section>
    </div>

    <section class="content-card mb-3">
        <form method="POST" action="{{ route('company-admin.employees.permissions.copy', $employee) }}" class="row g-2 align-items-end" data-confirm="Copy permissions from the selected employee?">
            @csrf
            <div class="col-md-7">
                <label class="form-label">Copy permissions from another employee</label>
                <select class="form-select" name="source_employee_id">
                    <option value="">Choose employee</option>
                    @foreach($companyEmployees as $companyEmployee)
                        <option value="{{ $companyEmployee->id }}">{{ $companyEmployee->name }} - {{ $companyEmployee->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-copy"></i>Copy Permissions</button>
            </div>
        </form>
    </section>

    <form method="POST" action="{{ route('company-admin.employees.permissions.update', $employee) }}" data-permission-form>
        @csrf
        @method('PUT')

        <div class="permission-toolbar mb-3">
            <div class="row g-2 align-items-center">
                <div class="col-lg-4">
                    <input class="form-control" type="search" placeholder="Search permissions" data-permission-search>
                </div>
                <div class="col-lg-3">
                    <select class="form-select" data-template-select>
                        <option value="">Apply template</option>
                        @foreach($templates as $key => $template)
                            <option value="{{ $key }}">{{ $template['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-5 d-flex flex-wrap gap-2 justify-content-lg-end">
                    <label class="btn btn-outline-primary mb-0">
                        <input class="form-check-input me-1" type="checkbox" data-select-all>
                        Select All
                    </label>
                    <button class="btn btn-outline-secondary" type="button" data-clear-all><i class="fa-solid fa-eraser"></i>Clear</button>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save Permissions</button>
                </div>
            </div>
        </div>

        <div class="permission-grid">
            @foreach($groups as $module => $group)
                <section class="permission-card" data-permission-group="{{ $module }}">
                    <div class="d-flex justify-content-between gap-2 align-items-start mb-2">
                        <div>
                            <h2>{{ $group['title'] }}</h2>
                            <p>{{ $group['description'] }}</p>
                        </div>
                    </div>
                    <label class="form-check border-bottom pb-2 mb-2">
                        <input class="form-check-input" type="checkbox" data-module-select>
                        <span class="form-check-label">Select all {{ strtolower($group['title']) }} permissions</span>
                    </label>
                    @foreach($group['permissions'] as $permission => $label)
                        <label class="form-check" data-permission-row data-permission-name="{{ $permission }} {{ $label }}">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $assignedPermissions, true))>
                            <span class="form-check-label">{{ $label }} <small class="permission-muted">({{ $permission }})</small></span>
                        </label>
                    @endforeach
                </section>
            @endforeach
        </div>
    </form>

    <form method="POST" action="{{ route('company-admin.employees.permissions.reset', $employee) }}" class="mt-3" data-confirm="Are you sure you want to remove all additional permissions from this Employee?">
        @csrf
        <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-rotate-left"></i>Reset Permissions</button>
    </form>
</div>

@push('scripts')
<script>
    window.elevanixPermissionTemplates = @json(collect($templates)->mapWithKeys(fn ($template, $key) => [$key => $template['permissions']]));

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-permission-form]');
        if (!form) return;

        const checkboxes = Array.from(form.querySelectorAll('input[name="permissions[]"]'));
        const globalSelect = form.querySelector('[data-select-all]');
        const selectedCount = document.querySelector('[data-selected-count]');
        const moduleCount = document.querySelector('[data-module-count]');

        function updateCounts() {
            const checked = checkboxes.filter((checkbox) => checkbox.checked);
            selectedCount.textContent = checked.length;
            moduleCount.textContent = new Set(checked.map((checkbox) => checkbox.closest('[data-permission-group]').dataset.permissionGroup)).size;

            document.querySelectorAll('[data-permission-group]').forEach((group) => {
                const groupChecks = Array.from(group.querySelectorAll('input[name="permissions[]"]'));
                const moduleSelect = group.querySelector('[data-module-select]');
                const selected = groupChecks.filter((checkbox) => checkbox.checked).length;
                moduleSelect.checked = selected === groupChecks.length;
                moduleSelect.indeterminate = selected > 0 && selected < groupChecks.length;
            });

            const selectedAll = checked.length === checkboxes.length;
            globalSelect.checked = selectedAll;
            globalSelect.indeterminate = checked.length > 0 && !selectedAll;
        }

        document.querySelectorAll('[data-module-select]').forEach((moduleSelect) => {
            moduleSelect.addEventListener('change', function () {
                const group = moduleSelect.closest('[data-permission-group]');
                group.querySelectorAll('input[name="permissions[]"]').forEach((checkbox) => checkbox.checked = moduleSelect.checked);
                updateCounts();
            });
        });

        globalSelect.addEventListener('change', function () {
            checkboxes.forEach((checkbox) => checkbox.checked = globalSelect.checked);
            updateCounts();
        });

        form.querySelector('[data-clear-all]').addEventListener('click', function () {
            checkboxes.forEach((checkbox) => checkbox.checked = false);
            updateCounts();
        });

        form.querySelector('[data-permission-search]').addEventListener('input', function (event) {
            const search = event.target.value.toLowerCase();
            document.querySelectorAll('[data-permission-row]').forEach((row) => {
                row.classList.toggle('permission-hidden', !row.dataset.permissionName.toLowerCase().includes(search));
            });
        });

        form.querySelector('[data-template-select]').addEventListener('change', function (event) {
            const selected = window.elevanixPermissionTemplates[event.target.value] || [];
            if (!selected.length) return;
            checkboxes.forEach((checkbox) => checkbox.checked = selected.includes(checkbox.value));
            updateCounts();
        });

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateCounts));
        updateCounts();
    });
</script>
@endpush
@endsection
