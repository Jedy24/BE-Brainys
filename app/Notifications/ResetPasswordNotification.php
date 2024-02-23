<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $resetToken;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
        $url = url("https://brainys.oasys.id/forget-password?token={$this->user->reset_token}");

        return (new MailMessage)
            ->line('Anda menerima pesan reset password karena kami menerima pesan reset password untuk akun Anda.')
            ->action('Reset Password', $url)
            ->line('Jika Anda tidak merasa meminta reset password, ubah password akun Anda sekarang juga untuk menghindari
            tindakan yang tidak sah.')
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
            //
        ];
    }
}
