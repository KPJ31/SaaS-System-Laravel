<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

trait HandlesNotificationAccess
{
    protected function abortUnlessOwnNotification(DatabaseNotification $notification): void
    {
        abort_unless($notification->notifiable_type === auth()->user()::class && (int) $notification->notifiable_id === (int) auth()->id(), 403);
    }

    protected function openNotification(DatabaseNotification $notification, string $fallbackRoute): RedirectResponse
    {
        $this->abortUnlessOwnNotification($notification);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        if (is_string($url) && $this->isSafeNotificationUrl($url)) {
            return redirect()->to($url);
        }

        return redirect()->route($fallbackRoute)->with('info', 'Notification destination is no longer available.');
    }

    private function isSafeNotificationUrl(string $url): bool
    {
        return str_starts_with($url, '/') || str_starts_with($url, url('/'));
    }
}
