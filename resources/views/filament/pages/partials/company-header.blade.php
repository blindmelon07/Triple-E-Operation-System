@php
    $companyLogoDataUri = \App\Support\CompanyLogo::dataUri();
@endphp

<div class="flex items-center gap-3 mb-4">
    @if($companyLogoDataUri)
        <img src="{{ $companyLogoDataUri }}" alt="Company Logo" class="h-10 w-auto">
    @endif
    <span class="text-base font-bold text-blue-800 dark:text-blue-300">Tri-e Enterprises OPC</span>
</div>
