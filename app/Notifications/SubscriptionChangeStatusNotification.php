<?php

namespace App\Notifications;

use App\Models\SubscriptionChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionChangeStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly SubscriptionChangeRequest $changeRequest,
        private readonly string $title,
        private readonly string $message
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message)
            ->line('Requested plan: '.$this->changeRequest->requestedPlan?->name)
            ->line('Status: '.str_replace('_', ' ', $this->changeRequest->status));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'subscription_change_request_id' => $this->changeRequest->id,
            'status' => $this->changeRequest->status,
        ];
    }
}
