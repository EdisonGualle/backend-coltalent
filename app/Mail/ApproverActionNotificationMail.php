<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApproverActionNotificationMail extends Mailable
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
        return $this->view('emails.approver_action_notification')
            ->subject($this->subject)
            ->with([
                'approverName' => $this->data['approverName'],
                'employeeName' => $this->data['employeeName'],
                'startDate' => $this->data['startDate'],
                'endDate' => $this->data['endDate'],
                'startTime' => $this->data['startTime'],
                'endTime' => $this->data['endTime'],
                'duration' => $this->data['duration'],
                'leaveType' => $this->data['leaveType'],
                'leaveReason' => $this->data['leaveReason'],
                'action' => $this->data['action'],
                'comment' => $this->data['comment'],
                'rejectionReason' => $this->data['rejectionReason'],
                'evaluationDate' => $this->data['evaluationDate'],
                'headerColor' => $this->data['headerColor'],
                'isRejection' => $this->data['isRejection'],
            ]);
    }
}
