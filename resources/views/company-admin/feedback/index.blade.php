@extends('layouts.app')

@section('title', 'Feedback - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Company Admin', 'title' => 'Feedback', 'description' => 'Review client feedback and approve public testimonials.'])
<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Average Rating', 'value' => number_format($averageRating ?? 0, 1), 'icon' => 'fa-star', 'tone' => 'yellow'])
    @include('partials.stat-card', ['label' => 'Feedback Count', 'value' => $feedbackCount, 'icon' => 'fa-comments', 'tone' => 'blue'])
</div>
<section class="content-card">
    <form class="row g-2 mb-3"><div class="col-md-4"><select class="form-select" name="rating"><option value="">All ratings</option>@for($i=1;$i<=5;$i++)<option value="{{ $i }}" @selected(request('rating')==$i)>{{ $i }} stars</option>@endfor</select></div><div class="col-md-4"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['pending','approved','hidden'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-filter"></i>Filter</button></div></form>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Client</th><th>Project</th><th>Rating</th><th>Message</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>@forelse($feedback as $item)<tr><td>{{ $item->client?->name ?? '-' }}</td><td>{{ $item->project?->name ?? '-' }}</td><td>{{ $item->rating }}/5</td><td>{{ Str::limit($item->message, 90) }}</td><td>@include('partials.status-badge', ['status' => $item->status])</td><td class="text-end">@foreach(['approved','hidden'] as $status)<form class="d-inline" method="POST" action="{{ route('company-admin.feedback.status', [$item, $status]) }}" data-confirm="Update feedback status?">@csrf<button class="btn btn-sm btn-outline-primary" type="submit">{{ ucfirst($status) }}</button></form>@endforeach</td></tr>@empty<tr><td colspan="6" class="empty-cell">No feedback found.</td></tr>@endforelse</tbody></table></div>
    {{ $feedback->links() }}
</section>
@endsection
