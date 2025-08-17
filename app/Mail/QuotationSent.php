<?php

// File: app/Mail/QuotationSent.php

namespace App\Mail;

use App\Models\Penawaran;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationSent extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Penawaran $quotation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Quotation #{$this->quotation->nomor_penawaran} - {$this->quotation->customer->nama}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotation-sent',
            with: [
                'quotation' => $this->quotation,
                'customer' => $this->quotation->customer,
            ],
        );
    }

    public function attachments(): array
    {
        // Generate PDF
        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $this->quotation,
            'customer' => $this->quotation->customer,
        ]);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                "Quotation-{$this->quotation->nomor_penawaran}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}