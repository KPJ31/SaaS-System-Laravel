@extends('layouts.app')
@section('title', 'Performance - Elevanix')
@section('content')
@include('partials.page-header', ['eyebrow' => 'Employee', 'title' => 'Performance', 'description' => 'Personal score based on completed task rate, time efficiency and work consistency.'])
@if($score === null)<section class="content-card">@include('partials.empty-state', ['icon' => 'fa-chart-line', 'title' => 'Not enough data to calculate performance.', 'message' => 'Performance appears after you receive and complete tasks.'])</section>@else
<div class="stat-grid">@include('partials.stat-card', ['label' => 'Score', 'value' => $score.'%', 'icon' => 'fa-star', 'tone' => 'green']) @include('partials.stat-card', ['label' => 'Label', 'value' => $label, 'icon' => 'fa-award', 'tone' => 'blue']) @include('partials.stat-card', ['label' => 'Completed Tasks', 'value' => $completed, 'icon' => 'fa-check']) @include('partials.stat-card', ['label' => 'Work Hours', 'value' => $workHours, 'icon' => 'fa-clock'])</div>
<div class="content-grid"><section class="content-card"><h2>Score Breakdown</h2><dl class="detail-list mt-3"><dt>Completed task rate</dt><dd>{{ $completedRate }}%</dd><dt>Time efficiency</dt><dd>{{ $timeEfficiency === null ? 'Not enough data' : $timeEfficiency.'%' }}</dd><dt>Work consistency</dt><dd>{{ $consistency }}%</dd></dl></section><section class="content-card"><h2>Monthly Completed Tasks</h2><canvas data-chart="employeePerformance" height="160"></canvas></section></div>
<section class="content-card mt-3"><div class="content-card-header"><h2>Recent Completed Tasks</h2></div>@include('employee.tasks._table', ['tasks' => $recentCompleted])</section>
@endif
@endsection
@push('scripts')<script>window.elevanixEmployeePerformance = @json($chartData);</script>@endpush
