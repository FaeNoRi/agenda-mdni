<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReglementSigne extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $pdfContent,   // Contenu binaire brut, jamais stocké
        private readonly string $nomAdherent,
        private readonly string $dateSignature,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Règlement intérieur signé — {$this->nomAdherent}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reglement_signe',
            with: [
                'nomAdherent'   => $this->nomAdherent,
                'dateSignature' => $this->dateSignature,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfContent,
                "reglement_signe_{$this->nomAdherent}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}