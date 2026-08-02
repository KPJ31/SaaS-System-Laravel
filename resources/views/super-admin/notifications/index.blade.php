@extends('layouts.app')

@section('title', 'Notifications - Elevanix')

@section('content')
@include('partials.page-header', ['eyebrow' => 'Super Admin', 'title' => 'Notifications', 'description' => 'Review platform notifications and mark them as read.', 'actions' => new Illuminate\Support\HtmlString('<form method="POST" action="'.route('super-admin.notifications.read-all').'">'.csrf_field().'<button class="btn btn-outline-primary" type="submit"><i class="fa-regular fa-circle-check"></i> Mark All Read</button></form>')])
<section class="content-card"><div class="activity-list">@forelse($notifications as $notification)<div><strong>{{ $notification->data['title'] ?? class_basename($notification->type) }}</strong><span>{{ $notification->data['message'] ?? json_encode($notification->data) }}</span><small>{{ $notification->created_at->diffForHumans() }} - {{ $notification->read_at ? 'Read' : 'Unread' }}</small>@unless($notification->read_at)<form method="POST" action="{{ route('super-admin.notifications.read', $notification) }}" class="mt-2">@csrf<button class="btn btn-sm btn-outline-primary" type="submit">Mark read</button></form>@endunless</div>@empty @include('partials.empty-state', ['icon' => 'fa-bell', 'title' => 'No notifications', 'message' => 'Important platform notices will appear here.']) @endforelse</div>{{ $notifications->links() }}</section>
@endsection
