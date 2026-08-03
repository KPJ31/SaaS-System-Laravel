@extends('layouts.app')

@section('title', $title.' - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Report Preview',
    'title' => $title,
    'description' => 'Generated '.$generatedAt->format('M d, Y h:i A').'. Check the data below before downloading.',
    'actions' => new Illuminate\Support\HtmlString(
        '<a class="btn btn-outline-primary" href="'.route('super-admin.reports.export', array_merge(['report' => $report], request()->query())).'"><i class="fa-solid fa-file-csv"></i> Download CSV</a>'.
        '<a class="btn btn-primary" href="'.route('super-admin.reports.pdf', array_merge(['report' => $report], request()->query())).'"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>'
    ),
])

<section class="content-card mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="search">Search</label>
            <input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Keyword">
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="date_from">From</label>
            <input class="form-control" id="date_from" type="date" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="date_to">To</label>
            <input class="form-control" id="date_to" type="date" name="date_to" value="{{ request('date_to') }}">
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="status">Status</label>
            <input class="form-control" id="status" name="status" value="{{ request('status') }}" placeholder="active">
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="company_id">Company</label>
            <select class="form-select" id="company_id" name="company_id">
                <option value="">All companies</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="plan_id">Plan</label>
            <select class="form-select" id="plan_id" name="plan_id">
                <option value="">All plans</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" @selected(request('plan_id') == $plan->id)>{{ $plan->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label" for="role">Role</label>
            <select class="form-select" id="role" name="role">
                <option value="">All roles</option>
                @foreach(['super_admin' => 'Super Admin', 'company_admin' => 'Company Admin', 'employee' => 'Employee'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3 col-md-8">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Apply Filters</button>
                <a class="btn btn-outline-primary" href="{{ route('super-admin.reports.show', $report) }}">Clear</a>
            </div>
        </div>
    </form>
</section>

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>Report Records</h2>
            <p>{{ $rows->count() }} records found.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) }}" class="empty-cell">
                            @include('partials.empty-state', ['icon' => 'fa-file-lines', 'title' => 'No report data', 'message' => 'There are no records for this report yet.'])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
