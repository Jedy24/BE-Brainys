<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    public $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct($otp)
    {
        $this->otp = $otp;
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
                ->subject('Kode OTP untuk Verifikasi')
                ->line('Kode OTP untuk verifikasi adalah: ' . $this->otp)
                ->action('Verifikasi Sekarang', url('/verify-otp'))
                ->line('Jika anda tidak meminta kode OTP, abaikan pesan ini.')
                ->salutation('Regards, Oasys Syllabus')
                ->line('Jika Anda mengalami masalah pada tombol "Verifikasi Sekarang", salin URL dibawah pada web browser: ' . url('/verify-otp'));
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
