<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyRegistrationRejected extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $companyName,
        private readonly string $reason
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Elevanix company registration update')
            ->greeting('Hello')
            ->line($this->companyName.' was not approved at this time.')
            ->line('Reason: '.$this->reason)
            ->line('You may contact the platform administrator for more information.');
    }
}
