@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[13px] font-semibold text-neutral-800 dark:text-neutral-200']) }}>
    {{ $value ?? $slot }}
</label>