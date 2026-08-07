<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\Concerns\HandlesNotificationAccess;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    use HandlesNotificationAccess;

    public function index(): View
    {
        return view('company-admin.notifications.index', [
            'notifications' => auth()->user()->notifications()->latest()->paginate(15),
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
        return $this->openNotification($notification, 'company-admin.notifications.index');
    }

    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
