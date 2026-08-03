@extends('layouts.app')

@section('title', $title.' - Elevanix')

@section('content')
@php
    $reportRoutePrefix = auth()->user()->role === 'employee' ? 'employee.reports' : 'company-admin.reports';
    $exportActions = auth()->user()->can('reports.export')
        ? '<a class="btn btn-outline-primary" href="'.route($reportRoutePrefix.'.export', array_merge(['report' => $report], request()->query())).'"><i class="fa-solid fa-file-csv"></i>Download CSV</a>'.
          '<a class="btn btn-primary" href="'.route($reportRoutePrefix.'.pdf', array_merge(['report' => $report], request()->query())).'"><i class="fa-solid fa-file-pdf"></i>Download PDF</a>'
        : '';
@endphp

@include('partials.page-header', [
    'eyebrow' => 'Report Preview',
    'title' => $title,
    'description' => 'Generated for '.$company->name.' on '.$generatedAt->format('M d, Y h:i A').'. Check the data before downloading.',
    'actions' => new Illuminate\Support\HtmlString($exportActions),
])

<section class="content-card mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-lg-3 col-md-6">
            <label class="form-label" for="search">Search</label>
            <input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Keyword">
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label" for="date_from">From</label>
            <input class="form-control" id="date_from" type="date" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label" for="date_to">To</label>
            <input class="form-control" id="date_to" type="date" name="date_to" value="{{ request('date_to') }}">
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label" for="status">Status</label>
            <input class="form-control" id="status" name="status" value="{{ request('status') }}" placeholder="active">
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label" for="employee_id">Employee</label>
            <select class="form-select" id="employee_id" name="employee_id">
                <option value="">All employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Apply Filters</button>
                <a class="btn btn-outline-primary" href="{{ route($reportRoutePrefix.'.show', $report) }}">Clear</a>
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
