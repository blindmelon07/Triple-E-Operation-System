@php
    $record = $getRecord();
    $presenter = new \App\Support\AuditLogPresenter($record);
    $color = \App\Support\AuditLogPresenter::actionColor($record->action);
@endphp

<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div class="min-w-0 space-y-1">
        <div class="flex flex-wrap items-center gap-2">
            <x-filament::badge :color="$color" size="lg">{{ $presenter->actionLabel() }}</x-filament::badge>
            @if ($presenter->modelLabel())
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $presenter->modelLabel() }}</span>
            @endif
        </div>

        <h2 class="truncate text-xl font-semibold text-gray-950 dark:text-white">
            {{ $record->auditable_label ?: $presenter->actionLabel() }}
        </h2>

        <p class="text-sm text-gray-600 dark:text-gray-300">
            by <span class="font-medium text-gray-950 dark:text-white">{{ $record->user_display_name }}</span>
        </p>
    </div>

    <div class="shrink-0 text-sm sm:text-right">
        <div class="font-medium text-gray-950 dark:text-white">{{ $record->created_at?->format('M j, Y') }}</div>
        <div class="text-gray-600 dark:text-gray-300">{{ $record->created_at?->format('g:i:s A') }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $record->created_at?->diffForHumans() }}</div>
    </div>
</div>
