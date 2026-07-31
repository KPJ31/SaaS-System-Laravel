<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyRegistrationReceived extends Notification
{
    use Queueable;

    public function __construct(private readonly string $companyName)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Elevanix company registration request')
            ->greeting('Hello '.$notifiable->name)
            ->line($this->companyName.' submitted a company registration request.')
            ->action('Review Requests', route('super-admin.company-requests.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New company registration',
            'message' => $this->companyName.' is waiting for review.',
        ];
    }
}
