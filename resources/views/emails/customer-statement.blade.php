<x-mail::message>
# Statement of Account

Hi {{ $customer->name }},

Please find attached your Statement of Account{{ $period ? ' '.$period : '' }}.

If you have any questions about this statement, please don't hesitate to reach out to us.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
