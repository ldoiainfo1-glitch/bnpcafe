<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuestAccessCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your secure sign-in code')
            ->line('Use this code to continue signing in to your account.')
            ->line($this->code)
            ->line('This code expires in 10 minutes. If you did not request it, you can ignore this email.');
    }
}
