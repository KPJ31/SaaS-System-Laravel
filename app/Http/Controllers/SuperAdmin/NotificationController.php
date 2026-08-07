<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Concerns\HandlesNotificationAccess;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    use HandlesNotificationAccess;

    public function index(Request $request): View
    {
        $query = auth()->user()->notifications()
            ->when($request->string('status')->toString() === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type));

        return view('super-admin.notifications.index', [
            'notifications' => $query->latest()->paginate(12)->withQueryString(),
            'types' => auth()->user()->notifications()->select('type')->distinct()->pluck('type'),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        $this->abortUnlessOwnNotification($notification);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function open(DatabaseNotification $notification): RedirectResponse
    {
        return $this->openNotification($notification, 'super-admin.notifications.index');
    }

    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
