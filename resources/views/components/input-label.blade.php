@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-neutral-800 dark:text-neutral-200']) }}>
    {{ $value ?? $slot }}
</label>
