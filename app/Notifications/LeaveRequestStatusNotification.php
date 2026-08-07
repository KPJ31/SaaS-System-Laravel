<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly LeaveRequest $leaveRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = str_replace('_', ' ', $this->leaveRequest->status);

        return [
            'title' => 'Leave request '.ucfirst($status),
            'message' => 'Your '.$this->leaveRequest->leave_type.' leave from '.$this->leaveRequest->start_date->toDateString().' to '.$this->leaveRequest->end_date->toDateString().' was '.$status.'.',
            'leave_request_id' => $this->leaveRequest->id,
            'status' => $this->leaveRequest->status,
            'review_note' => $this->leaveRequest->review_note,
            'url' => route('employee.leave-requests.index'),
        ];
    }
}
