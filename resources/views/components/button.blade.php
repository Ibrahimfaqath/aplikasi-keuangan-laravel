@props([
    'variant' => 'primary', // primary, secondary, danger, ghost
    'type' => 'button',
    'href' => null
])

@php
    $baseClasses = "inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 cursor-pointer select-none";
    
    $variants = [
        'primary'   => "bg-blue-600 hover:bg-blue-700 dark:bg-gold-400 dark:hover:bg-gold-300 dark:text-black active:bg-blue-800 text-white shadow-sm focus:ring-blue-500 dark:focus:ring-offset-neutral-900",
        'secondary' => "bg-white dark:bg-neutral-900 hover:bg-slate-50 dark:hover:bg-neutral-800/80 text-slate-700 dark:text-neutral-100 border border-slate-200 dark:border-neutral-800 shadow-sm focus:ring-slate-400",
        'danger'    => "bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200/60 dark:border-rose-900/60 focus:ring-rose-500",
        'ghost'     => "bg-transparent hover:bg-slate-100 dark:hover:bg-neutral-800 text-slate-600 dark:text-neutral-400 focus:ring-slate-400"
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif