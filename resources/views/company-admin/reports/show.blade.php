@extends('layouts.app')

@section('title', $title.' - Elevanix')

@section('content')
@php
    $reportRoutePrefix = auth()->user()->role === 'employee' ? 'employee.reports' : 'company-admin.reports';
    $exportActions = auth()->user()->can('reports.export')
        ? '<a class="btn btn-outline-primary" href="'.route($reportRoutePrefix.'.export', array_merge(['report' => $report], request()->query())).'"><i class="fa-solid fa-file-csv"></i>Export CSV<span class="visually-hidden"> Download CSV</span></a>'.
          '<a class="btn btn-primary" href="'.route($reportRoutePrefix.'.pdf', array_merge(['report' => $report], request()->query())).'"><i class="fa-solid fa-file-pdf"></i>Export PDF<span class="visually-hidden"> Download PDF</span></a>'
        : '';
@endphp

@include('partials.page-header', [
    'eyebrow' => $category ?? 'Report Preview',
    'title' => $title,
    'description' => ($description ?? 'Company report').' Generated for '.$company->name.' from '.$from->format('Y-m-d').' to '.$to->format('Y-m-d').'.',
    'actions' => new Illuminate\Support\HtmlString($exportActions),
])

<section class="content-card mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-lg-3 col-md-6"><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Keyword"></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="date_from">From</label><input class="form-control" id="date_from" type="date" name="date_from" value="{{ request('date_from', $from->format('Y-m-d')) }}"></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="date_to">To</label><input class="form-control" id="date_to" type="date" name="date_to" value="{{ request('date_to', $to->format('Y-m-d')) }}"></div>
        <div class="col-lg-2 col-md-6"><label class="form-label" for="status">Status</label><input class="form-control" id="status" name="status" value="{{ request('status') }}" placeholder="status"></div>
        @if(isset($employees))
            <div class="col-lg-3 col-md-6"><label class="form-label" for="employee_id">Employee</label><select class="form-select" id="employee_id" name="employee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        @endif
        @if(isset($projects))
            <div class="col-lg-3 col-md-6"><label class="form-label" for="project_id">Project</label><select class="form-select" id="project_id" name="project_id"><option value="">All projects</option>@foreach($projects as $projectOption)<option value="{{ $projectOption->id }}" @selected(request('project_id') == $projectOption->id)>{{ $projectOption->name }}</option>@endforeach</select></div>
        @endif
        @if(isset($clients))
            <div class="col-lg-3 col-md-6"><label class="form-label" for="client_id">Client</label><select class="form-select" id="client_id" name="client_id"><option value="">All clients</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>@endforeach</select></div>
        @endif
        <div class="col-lg-3 col-md-6">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Apply Filters</button>
                <a class="btn btn-outline-primary" href="{{ route($reportRoutePrefix.'.show', $report) }}">Clear</a>
            </div>
        </div>
    </form>
</section>

@if(! empty($summary))
    <div class="stat-grid mb-3">
        @foreach($summary as $card)
            @include('partials.stat-card', $card)
        @endforeach
    </div>
@endif

@if(! empty($chartData))
    <section class="content-card mb-3">
        <div class="content-card-header">
            <div>
                <h2>Chart Summary</h2>
                <p>
                    @foreach(($chartData['labels'] ?? $chartData['taskLabels'] ?? []) as $index => $label)
                        {{ $label }}: {{ ($chartData['values'] ?? $chartData['taskValues'] ?? [])[$index] ?? 0 }}@if(! $loop->last), @endif
                    @endforeach
                </p>
            </div>
        </div>
        <div class="dashboard-chart-wrapper chart-small">
            <canvas id="reportAnalyticsChart" data-chart="reportAnalytics" role="img" aria-label="{{ $title }} chart"></canvas>
        </div>
    </section>
@endif

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>Report Records</h2>
            <p>{{ $rows->count() }} records found. Detail links open the source workspace where available.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($rows as $rowIndex => $row)
                    <tr>
                        @foreach($row as $cellIndex => $cell)
                            <td>
                                @if($cellIndex === 0 && ($rowLinks[$rowIndex] ?? null))
                                    <a class="fw-semibold" href="{{ $rowLinks[$rowIndex] }}">{{ $cell }}</a>
                                @else
                                    {{ $cell }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($headers) }}" class="empty-cell">@include('partials.empty-state', ['icon' => 'fa-file-lines', 'title' => 'No report data', 'message' => 'There are no records for this report and filter set.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if(! empty($chartData))
    @push('scripts')
        <script>window.elevanixReportCharts = @json($chartData);</script>
    @endpush
@endif
@endsection
