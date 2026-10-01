@props(['value'])

<label {{ $attributes->merge(['class' => 'label-text block text-[13px] font-semibold']) }}>
    {{ $value ?? $slot }}
</label>