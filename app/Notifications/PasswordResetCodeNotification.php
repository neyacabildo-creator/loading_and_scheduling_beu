<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCodeNotification extends Notification
{

    public function __construct(private readonly string $code)
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your password reset code')
            ->greeting('Hello!')
            ->line('Use the code below to reset your password:')
            ->line($this->code)
            ->line('This code expires in 15 minutes.')
            ->line('If you did not request a password reset, you can ignore this email.');
    }
}