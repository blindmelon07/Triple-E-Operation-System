<x-mail::message>
# Quotation Approval Request

A new quotation has been submitted and requires your approval.

**Quotation Details:**

| Field | Value |
|:------|:------|
| Quotation # | {{ $quotation->quotation_number }} |
| Date | {{ $quotation->date->format('F d, Y') }} |
| Customer | {{ $quotation->customer?->name ?? 'Walk-in Customer' }} |
| Total Amount | ₱{{ number_format($quotation->total, 2) }} |
| Valid Until | {{ $quotation->valid_until?->format('F d, Y') ?? 'N/A' }} |
@if($quotation->creator)
| Requested By | {{ $quotation->creator->name }} |
@endif

@if($quotation->notes)
**Notes:** {{ $quotation->notes }}
@endif

Please review and approve or reject this quotation.

<x-mail::button :url="$approvalUrl">
Review Quotation
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
