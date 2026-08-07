@extends('layouts.app')

@section('title', 'Notifications - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Super Admin',
    'title' => 'Notifications',
    'description' => 'Review platform notifications and mark important items as read.',
    'badge' => $unreadCount.' unread',
    'actions' => new Illuminate\Support\HtmlString('<form method="POST" action="'.route('super-admin.notifications.read-all').'">'.csrf_field().'<button class="btn btn-outline-primary" type="submit"><i class="fa-regular fa-circle-check"></i> Mark All Read</button></form>'),
])

<section class="content-card">
    @include('partials.filter-bar', [
        'action' => route('super-admin.notifications.index'),
        'resetUrl' => route('super-admin.notifications.index'),
        'controls' => new Illuminate\Support\HtmlString(
            '<div><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All notifications</option><option value="unread" '.(request('status') === 'unread' ? 'selected' : '').'>Unread only</option></select></div>'.
            '<div><label class="form-label" for="type">Type</label><select class="form-select" id="type" name="type"><option value="">All types</option>'.
                $types->map(fn ($type) => '<option value="'.e($type).'" '.(request('type') === $type ? 'selected' : '').'>'.e(class_basename($type)).'</option>')->implode('').
            '</select></div>'
        ),
    ])

    <div class="notification-list mt-3">
        @forelse($notifications as $notification)
            <article class="notification-row {{ $notification->read_at ? '' : 'is-unread' }}">
                <a href="{{ route('super-admin.notifications.open', $notification) }}">
                    <strong>{{ $notification->data['title'] ?? class_basename($notification->type) }}</strong>
                    <span>{{ $notification->data['message'] ?? 'Open the related platform record for details.' }}</span>
                    <small>{{ $notification->created_at->diffForHumans() }} | {{ $notification->read_at ? 'Read' : 'Unread' }}</small>
                </a>
                @unless($notification->read_at)
                    <form method="POST" action="{{ route('super-admin.notifications.read', $notification) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary" type="submit">Mark read</button>
                    </form>
                @endunless
            </article>
        @empty
            @include('partials.empty-state', ['icon' => 'fa-bell', 'title' => request()->query() ? 'No notifications match your filters' : 'No notifications', 'message' => request()->query() ? 'Try changing the status or type filter.' : 'Important platform notices will appear here.'])
        @endforelse
    </div>
    {{ $notifications->links() }}
</section>
@endsection
