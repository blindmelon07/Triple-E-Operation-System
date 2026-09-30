<x-filament-panels::page>
    @include('filament.pages.partials.company-header')

    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$viewMode === 'summary'"
            icon="heroicon-o-list-bullet"
            wire:click="setViewMode('summary')"
        >
            Summary per Product
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$viewMode === 'detailed'"
            icon="heroicon-o-queue-list"
            wire:click="setViewMode('detailed')"
        >
            Detailed (every movement)
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{ $this->table }}
</x-filament-panels::page>
