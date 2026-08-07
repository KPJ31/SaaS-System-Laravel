@extends('layouts.app')

@section('title', 'Notifications - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Company Admin',
    'title' => 'Notifications',
    'description' => 'Review company admin notifications and open the related work item.',
    'actions' => new \Illuminate\Support\HtmlString('<form method="POST" action="'.route('company-admin.notifications.read-all').'">'.csrf_field().'<button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-check-double"></i>Mark all read</button></form>'),
])

<div class="stat-grid">
    @include('partials.stat-card', ['label' => 'Unread', 'value' => $unreadCount, 'icon' => 'fa-bell', 'tone' => 'yellow'])
</div>

<section class="content-card">
    <div class="notification-list">
        @forelse($notifications as $notification)
            <article class="notification-row {{ $notification->read_at ? '' : 'is-unread' }}">
                <a href="{{ route('company-admin.notifications.open', $notification) }}">
                    <strong>{{ $notification->data['title'] ?? class_basename($notification->type) }}</strong>
                    <span>{{ $notification->data['message'] ?? $notification->created_at->diffForHumans() }}</span>
                    <small>{{ $notification->created_at->format('M d, Y H:i') }} @if(! $notification->read_at) | Unread @endif</small>
                </a>
                @unless($notification->read_at)
                    <form method="POST" action="{{ route('company-admin.notifications.read', $notification) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary" type="submit"><i class="fa-solid fa-check"></i>Mark read</button>
                    </form>
                @endunless
            </article>
        @empty
            @include('partials.empty-state', ['icon' => 'fa-bell', 'title' => 'No notifications', 'message' => 'New company notifications will appear here.'])
        @endforelse
    </div>
    {{ $notifications->links() }}
</section>
@endsection
