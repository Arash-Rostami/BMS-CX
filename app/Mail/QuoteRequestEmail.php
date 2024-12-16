<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class QuoteRequestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(protected $emailBody, protected $recipient, protected $sender)
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $recipientName = $this->recipient['company'] ?? 'Our Valued Partner';

        return new Envelope(
            from: $this->getSender(),
            replyTo: [$this->getSender()],
            subject: 'Your Quote Needed: ' . $recipientName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quote-request',
            with: [
                'recipient' => $this->recipient,
                'content' => $this->emailBody,
                'sender' => $this->sender->full_name
            ],
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

    public function getSender()
    {
        return new Address($this->sender->email, $this->sender->full_name);
    }

}
