{{-- Section header reusable --}}
@props([
    'icon' => '',
    'title' => '',
    'description' => ''
])

<div class="flex items-start gap-3 mb-4">
    @if ($icon)
    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-50 dark:bg-navy-800/60 flex items-center justify-center text-blue-600 dark:text-navy-300">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            {!! $icon !!}
        </svg>
    </div>
    @endif
    <div class="flex-1">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ $title }}</h3>
        @if ($description)
        <p class="text-xs text-slate-500 dark:text-white/60 mt-0.5">{{ $description }}</p>
        @endif
    </div>
</div>
