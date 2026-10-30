@php
    $presenter = new \App\Support\AuditLogPresenter($getRecord());
    $changes = $presenter->changes();
    $isUpdate = $presenter->mode() === 'update';
@endphp

@if ($changes === [])
    <p class="text-sm text-gray-500 dark:text-gray-400">No field details were recorded for this action.</p>
@else
    <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
        <table class="w-full table-auto divide-y divide-gray-200 text-sm dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="w-1/4 px-4 py-2.5 text-left font-semibold text-gray-950 dark:text-white">Field</th>
                    @if ($isUpdate)
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-950 dark:text-white">Before</th>
                        <th class="w-8 px-0 py-2.5"></th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-950 dark:text-white">After</th>
                    @else
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-950 dark:text-white">Value</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @foreach ($changes as $change)
                    <tr class="align-top hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-700 dark:text-gray-200">{{ $change['field'] }}</td>

                        @if ($isUpdate)
                            <td class="px-4 py-3">
                                @include('filament.infolists.partials.audit-log-value', ['value' => $change['old'], 'tone' => 'old'])
                            </td>
                            <td class="px-0 py-3 text-center text-gray-400 dark:text-gray-500">→</td>
                            <td class="px-4 py-3">
                                @include('filament.infolists.partials.audit-log-value', ['value' => $change['new'], 'tone' => 'new'])
                            </td>
                        @else
                            <td class="px-4 py-3">
                                @include('filament.infolists.partials.audit-log-value', ['value' => $change['new'], 'tone' => 'plain'])
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
