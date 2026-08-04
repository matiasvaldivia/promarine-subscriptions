<?php

namespace App\Mail;

use App\Models\MembershipSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MembershipSubscription $membership) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recibimos tu solicitud de membresía anual Promarine');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.membership-requested');
    }

    public function attachments(): array
    {
        return [];
    }
}
