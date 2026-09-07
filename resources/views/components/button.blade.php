@props([
    'variant' => 'primary', // primary, secondary, danger, ghost, success
    'type' => 'button',
    'href' => null
])

@php
    $baseClasses = "inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-[#0A0A0A] disabled:opacity-50 cursor-pointer select-none";

    $variants = [
        'primary'   => "bg-neutral-900 hover:bg-neutral-800 active:bg-black text-white shadow-sm focus:ring-neutral-900 dark:focus:ring-neutral-400",
        'secondary' => "bg-white dark:bg-[#171717] hover:bg-neutral-50 dark:hover:bg-[#262626] text-neutral-900 dark:text-neutral-100 border border-neutral-300 dark:border-[#333333] shadow-sm focus:ring-neutral-400",
        // Destructive tetap beda via bobot + border tebal + icon (monochrome, tanpa merah mencolok)
        'danger'    => "bg-white dark:bg-transparent hover:bg-neutral-900 dark:hover:bg-neutral-100 hover:text-white dark:hover:text-neutral-900 text-neutral-900 dark:text-neutral-100 border-2 border-neutral-900 dark:border-neutral-100 focus:ring-neutral-900 dark:focus:ring-neutral-400",
        'ghost'     => "bg-transparent hover:bg-neutral-100 dark:hover:bg-[#262626] text-neutral-700 dark:text-neutral-300 focus:ring-neutral-400",
        // Success: HANYA untuk aksi dengan semantic success yang jelas (bukan default)
        'success'   => "bg-green-600 hover:bg-green-700 active:bg-green-800 text-white shadow-sm focus:ring-green-600 dark:focus:ring-green-500",
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
