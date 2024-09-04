<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentPendingNotification extends Mailable
{
    public $user;
    public $transaction;
    public $transactionPayment;
    public $paymentMethod;

    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $transaction, $transactionPayment, $paymentMethod)
    {
        $this->user = $user;
        $this->transaction = $transaction;
        $this->transactionPayment = $transactionPayment;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Menunggu Pembayaran ' . $this->transactionPayment->service_name . ' untuk pembayaran - '. $this->transaction->transaction_code,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-pending-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
