<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailTwoFactorCode extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  string  $code  The 6-digit verification code
     * @param  string  $purpose  The purpose of the code ('login' or 'disable')
     */
    public function __construct(
        public string $code,
        public string $purpose = 'login'
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $subject = $this->purpose === 'disable'
            ? __('auth.two_factor.email.disable_subject')
            : __('auth.two_factor.email.code_subject');

        $line1 = $this->purpose === 'disable'
            ? __('auth.two_factor.email.disable_line1')
            : __('auth.two_factor.email.login_line1');

        $line2 = $this->purpose === 'disable'
            ? __('auth.two_factor.email.disable_line2')
            : __('auth.two_factor.email.login_line2');

        return (new MailMessage)
            ->subject($subject)
            ->greeting(__('auth.two_factor.email.greeting', ['name' => $notifiable->name]))
            ->line($line1)
            ->line($line2)
            ->line('**'.$this->code.'**')
            ->line(__('auth.two_factor.email.validity'))
            ->line(__('auth.two_factor.email.ignore_warning'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'code' => $this->code,
            'purpose' => $this->purpose,
        ];
    }
}
