{{-- Form group dengan label & error handling --}}
@props([
    'label' => '',
    'error' => null,
    'required' => false,
    'hint' => ''
])

<div class="space-y-2">
    @if ($label)
    <label {{ $attributes->only(['for']) }} class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70">
        {{ $label }}
        @if ($required)
        <span class="text-rose-500">*</span>
        @endif
    </label>
    @endif

    {{ $slot }}

    @if ($error)
    <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1 font-medium">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $error }}
    </p>
    @endif

    @if ($hint)
    <p class="text-xs text-slate-500 dark:text-white/60 font-medium flex items-center gap-1">
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        {{ $hint }}
    </p>
    @endif
</div>
