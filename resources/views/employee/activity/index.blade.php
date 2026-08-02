@extends('layouts.app')
@section('title', 'Activity History - Elevanix')
@section('content')
@include('partials.page-header', ['eyebrow' => 'Employee', 'title' => 'Activity History', 'description' => 'Your own system activity only.'])
<section class="content-card mb-3"><form class="row g-2"><div class="col-md-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search activity"></div><div class="col-md-3"><input class="form-control" name="module" value="{{ request('module') }}" placeholder="Module"></div><div class="col-md-3"><input class="form-control" type="date" name="date" value="{{ request('date') }}"></div><div class="col-md-2"><button class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i>Filter</button></div></form></section>
<section class="content-card"><div class="activity-list">@forelse($activities as $activity)<div><strong>{{ str_replace('_', ' ', ucfirst($activity->action)) }}</strong><span>{{ $activity->description }} | {{ $activity->created_at->format('M d, Y H:i') }}</span></div>@empty @include('partials.empty-state', ['icon' => 'fa-clipboard-list', 'title' => 'No activity', 'message' => 'Your activity history appears here.']) @endforelse</div>{{ $activities->links() }}</section>
@endsection
