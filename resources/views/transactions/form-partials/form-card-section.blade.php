{{-- Card section untuk form grouping --}}
@props([
    'title' => '',
    'icon' => '',
    'class' => ''
])

<div class="bg-slate-50 dark:bg-navy-800/40 border border-slate-200 dark:border-navy-700/60 rounded-xl p-5 space-y-5 {{ $class }}">
    @if ($title)
    <x-transactions.form-partials.section-header 
        :title="$title" 
        :icon="$icon"
    />
    @endif

    {{ $slot }}
</div>
