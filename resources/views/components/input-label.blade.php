@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-slate-700 dark:text-neutral-300']) }}>
    {{ $value ?? $slot }}
</label>
