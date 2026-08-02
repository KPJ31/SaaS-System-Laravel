@extends('layouts.app')

@section('title', 'Notifications - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Company Admin', 'title' => 'Notifications', 'description' => 'Review and mark your company admin notifications as read.', 'actions' => new \Illuminate\Support\HtmlString('<form method="POST" action="'.route('company-admin.notifications.read-all').'">'.csrf_field().'<button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-check-double"></i>Mark all read</button></form>')])
<section class="content-card">
    <div class="activity-list">
        @forelse($notifications as $notification)
            <div>
                <strong>{{ $notification->data['title'] ?? class_basename($notification->type) }}</strong>
                <span>{{ $notification->data['message'] ?? $notification->created_at->diffForHumans() }}</span>
                @unless($notification->read_at)<form class="mt-2" method="POST" action="{{ route('company-admin.notifications.read', $notification) }}">@csrf<button class="btn btn-sm btn-outline-primary" type="submit">Mark read</button></form>@endunless
            </div>
        @empty
            @include('partials.empty-state', ['icon' => 'fa-bell', 'title' => 'No notifications', 'message' => 'New company notifications will appear here.'])
        @endforelse
    </div>
    {{ $notifications->links() }}
</section>
@endsection
