<?php

namespace App\Mail;

use App\Models\MockOrder;
use App\Models\MockPayment;
use App\Models\MockSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MockPurchaseConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MockSubscription $subscription,
        public object $customer,
        public MockPayment $payment,
        public ?MockOrder $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu compra simulada Promarine fue aprobada');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.mock-purchase-confirmed');
    }

    public function attachments(): array
    {
        return [];
    }
}
