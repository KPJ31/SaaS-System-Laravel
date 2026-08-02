<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('employee.notifications.index', [
            'notifications' => auth()->user()->notifications()->latest()->paginate(10),
        ]);
    }

    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        abort_unless($notification->notifiable_type === auth()->user()::class && (int) $notification->notifiable_id === (int) auth()->id(), 403);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
