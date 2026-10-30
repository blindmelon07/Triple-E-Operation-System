@if ($value === null || $value['empty'])
    <span class="italic text-gray-400 dark:text-gray-500">Empty</span>
@else
    <span @class([
        'inline-block rounded px-1.5 py-0.5 break-words' => $tone !== 'plain',
        'bg-danger-50 text-danger-700 line-through decoration-danger-400/60 dark:bg-danger-400/10 dark:text-danger-300' => $tone === 'old',
        'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-300' => $tone === 'new',
        'text-gray-950 dark:text-white break-words' => $tone === 'plain',
    ])>
        @if ($value['mono'])
            <pre class="whitespace-pre-wrap font-mono text-xs">{{ $value['text'] }}</pre>
        @else
            {{ $value['text'] }}
        @endif
    </span>
    @if ($value['hint'])
        <span class="ml-1 text-xs text-gray-400 dark:text-gray-500">{{ $value['hint'] }}</span>
    @endif
@endif
