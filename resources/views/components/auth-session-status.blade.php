@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-neutral-900 dark:text-neutral-100 bg-neutral-100 dark:bg-[#262626] border border-neutral-200 dark:border-[#333333] rounded-xl px-3 py-2']) }}>
        {{ $status }}
    </div>
@endif
