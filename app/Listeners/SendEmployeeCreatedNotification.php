<?php

namespace App\Listeners;

use App\Events\EmployeeCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendEmployeeCreatedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\EmployeeCreated  $event
     * @return void
     */
    public function handle(EmployeeCreated $event)
    {
        $employee = $event->employee;
        $email = $employee->contact->personal_email;
        $user = $employee->user;
        $password = $event->password;
        $loginUrl = env('LOGIN_URL');

        Mail::send('emails.employee_created', [
            'employee' => $employee,
            'user' => $user,
            'password' => $password,
            'loginUrl' => $loginUrl
        ], function ($message) use ($email) {
            $message->to($email)
                    ->subject('Bienvenido a al GADMC Colta');
        });
    }
}
