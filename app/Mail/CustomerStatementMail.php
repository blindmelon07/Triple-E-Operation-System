<?php

namespace App\Mail;

use App\Models\Customer;
use Barryvdh\DomPDF\PDF as PdfInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class CustomerStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Customer $customer,
        public PdfInstance $pdf,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Statement of Account - '.$this->customer->name,
        );
    }

    public function content(): Content
    {
        $period = null;

        if ($this->dateFrom || $this->dateTo) {
            $from = $this->dateFrom ? \Illuminate\Support\Carbon::parse($this->dateFrom)->format('F d, Y') : 'the beginning';
            $to = $this->dateTo ? \Illuminate\Support\Carbon::parse($this->dateTo)->format('F d, Y') : 'today';
            $period = "for the period {$from} to {$to}";
        }

        return new Content(
            markdown: 'emails.customer-statement',
            with: [
                'customer' => $this->customer,
                'period'   => $period,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $filename = 'statement-of-account-'.Str::slug($this->customer->name).'-'.now()->format('Y-m-d').'.pdf';

        return [
            Attachment::fromData(fn () => $this->pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}
