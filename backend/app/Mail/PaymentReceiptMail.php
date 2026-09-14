<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public Payment $payment) {}
    public function envelope(): Envelope { return new Envelope(from: new Address((string) config('mail.from.address'), $this->payment->invoice->organization->name), subject: 'Comprovante de pagamento '.$this->payment->invoice->charge_identifier); }
    public function content(): Content { return new Content(view: 'mail.payment-receipt'); }
}
