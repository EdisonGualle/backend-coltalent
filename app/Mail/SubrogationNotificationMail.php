<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue; 
use Illuminate\Queue\SerializesModels;

class SubrogationNotificationMail extends Mailable implements ShouldQueue 
{
    use Queueable, SerializesModels;

    public $details;

    /**
     * Create a new message instance.
     *
     * @param array $details
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Notificación de Subrogación')
                    ->view('emails.subrogation_notification')
                    ->with('details', $this->details);
    }
}
