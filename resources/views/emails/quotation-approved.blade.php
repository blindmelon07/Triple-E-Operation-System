<x-mail::message>
# Quotation Approved

Great news! Your quotation has been approved and is now ready for printing.

**Quotation Details:**

| Field | Value |
|:------|:------|
| Quotation # | {{ $quotation->quotation_number }} |
| Date | {{ $quotation->date->format('F d, Y') }} |
| Customer | {{ $quotation->customer?->name ?? 'Walk-in Customer' }} |
| Total Amount | ₱{{ number_format($quotation->total, 2) }} |
| Valid Until | {{ $quotation->valid_until?->format('F d, Y') ?? 'N/A' }} |

You can now print this quotation by clicking the button below.

<x-mail::button :url="$printUrl">
Print Quotation
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
