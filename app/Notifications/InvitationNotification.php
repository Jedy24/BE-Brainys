<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class InvitationNotification extends Notification
{
    use Queueable;

    protected $inviteCode;

    /**
     * Create a new notification instance.
     */
    public function __construct($inviteCode)
    {
        $this->inviteCode = $inviteCode;
    }

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
        return (new MailMessage)
                ->line('Anda telah diundang untuk menggunakan Brainys!')
                ->line(new HtmlString('Kode undangan Anda: <strong>' . $this->inviteCode .'</strong>'))
                ->line('Terima kasih telah menggunakan Brainys!')
                ->salutation(new HtmlString('Regards,<br>Oasys Syllabus'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invite_code' => $this->inviteCode,
        ];
    }
}
