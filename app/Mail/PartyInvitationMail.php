<?php

namespace App\Mail;

use App\Models\Party;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartyInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Party $party,
        public string $recipientEmail,
        public bool $existingUser
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->existingUser
            ? 'You\'re in: ' . $this->party->name
            : 'You\'re invited: ' . $this->party->name;

        return new Envelope(
            subject: $subject,
            to: [$this->recipientEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.party-invitation',
        );
    }
}
