<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PendingApprovalNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $subject;

    public function __construct($data, $subject)
    {
        $this->data = $data;
        $this->subject = $subject;
    }

    public function build()
    {
        return $this->view('emails.pending_approval_notification')
            ->subject($this->subject)
            ->with([
                'approverName' => $this->data['approverName'],
                'applicantName' => $this->data['applicantName'],
                'startDate' => $this->data['startDate'],
                'endDate' => $this->data['endDate'],
                'startTime' => $this->data['startTime'],
                'endTime' => $this->data['endTime'],
                'duration' => $this->data['duration'],
                'leaveType' => $this->data['leaveType'],
                'leaveReason' => $this->data['leaveReason'],
            ]);
    }
}
