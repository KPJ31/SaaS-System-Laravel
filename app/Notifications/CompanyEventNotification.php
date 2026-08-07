<?php

namespace App\Notifications;

use App\Models\CompanyEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CompanyEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly CompanyEvent $event,
        private readonly string $title,
        private readonly string $message
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $prefix = $notifiable->role === 'employee' ? 'employee' : 'company-admin';

        return [
            'title' => $this->title,
            'message' => $this->message,
            'company_event_id' => $this->event->id,
            'event_type' => $this->event->event_type,
            'status' => $this->event->status,
            'start_at' => $this->event->start_at?->toDateTimeString(),
            'url' => route($prefix.'.calendar.index', ['focus' => $this->event->start_at?->toDateString()]),
        ];
    }
}
