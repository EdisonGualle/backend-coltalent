<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveActionNotificationMail extends Mailable
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
        return $this->view('emails.leave_action_notification')
            ->subject($this->subject)
            ->with([
                'employeeName' => $this->data['employeeName'],
                'startDate' => $this->data['startDate'],
                'endDate' => $this->data['endDate'],
                'startTime' => $this->data['startTime'],
                'endTime' => $this->data['endTime'],
                'duration' => $this->data['duration'],
                'leaveType' => $this->data['leaveType'],
                'leaveReason' => $this->data['leaveReason'],
                'approverName' => $this->data['approverName'],
                'action' => $this->data['action'],
                'isFinalApproval' => $this->data['isFinalApproval'],
                'evaluationDate' => $this->data['evaluationDate'],
                'comment' => $this->data['comment'],
                'rejectionReason' => $this->data['rejectionReason'],
                'nextApprover' => $this->data['nextApprover'],
                'headerColor' => $this->data['headerColor'],
                'isRejection' => $this->data['isRejection'],
            ]);
    }
}
