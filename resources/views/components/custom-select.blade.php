{{-- ============================================================
  CUSTOM SELECT — pengganti <select> native agar daftar pilihan
  bisa di-style penuh (rounded, hover, check, dark mode, search).

  Pemakaian:
    <x-custom-select name="type" id="filterType"
        label="Filter berdasarkan tipe transaksi"
        :options="['' => 'Semua Tipe', 'income' => 'Pemasukan']"
        :selected="request('type', '')" />

  Props:
    name       -> nama hidden input (dikirim saat submit, sama spt select)
    id         -> id tombol trigger (untuk <label for>)
    label      -> teks label (sr-only, kecuali hideLabel)
    options    -> array asosiatif value => label
    selected   -> value terpilih awal (mis. dari request())
    searchable -> true = ada kotak cari di dalam daftar (utk opsi banyak)
    onchange   -> nama fungsi JS global yg dipanggil dgn value terpilih
    hideLabel  -> true = label tidak dirender (induk sudah punya label)
  Aksesibilitas: roles combobox/listbox/option, aria-expanded,
  aria-selected, aria-activedescendant; keyboard panah/Enter/Esc/Home/End.
  ============================================================ --}}
@props([
    'name',
    'id',
    'label' => '',
    'options' => [],
    'selected' => '',
    'searchable' => false,
    'onchange' => '',
    'hideLabel' => false,
])

<div
    x-data="customSelect({{ Js::from(['options' => $options, 'selected' => (string) $selected, 'onchange' => (string) $onchange, 'searchable' => (bool) $searchable]) }})"
    @click.outside="close()"
    @keydown.tab="close()"
    @keydown.escape.prevent="close(true)"
    @keydown="typeAhead($event)"
    class="relative"
>
    @if(! $hideLabel && $label)
        <label for="{{ $id }}" class="sr-only">{{ $label }}</label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="value">

    <button
        type="button"
        id="{{ $id }}"
        x-ref="trigger"
        role="combobox"
        aria-haspopup="listbox"
        aria-autocomplete="none"
        @click="toggle()"
        @keydown.enter.prevent="open ? chooseActive() : openPanel()"
        @keydown.arrow-down.prevent="open ? move(1) : openPanel()"
        @keydown.arrow-up.prevent="open ? move(-1) : openPanel()"
        :aria-expanded="open.toString()"
        :aria-activedescendant="open && activeIndex >= 0 ? '{{ $id }}-opt-' + activeIndex : null"
        x-bind:title="selectedLabel"
        class="flex w-full items-center gap-2 rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2.5 text-left text-xs text-neutral-900 transition hover:border-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 dark:border-[#333333] dark:bg-[#262626] dark:text-neutral-100 dark:hover:border-neutral-500 dark:focus:border-neutral-100 dark:focus:ring-neutral-100 sm:text-sm"
    >
        <span class="min-w-0 flex-1 truncate" x-text="selectedLabel"></span>
        <svg class="h-4 w-4 shrink-0 text-neutral-400 transition-transform duration-150 dark:text-neutral-500" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="absolute z-30 w-full"
        :class="dropUp ? 'bottom-full mb-2' : 'top-full mt-2'"
    >
        <div
            x-ref="list"
            role="listbox"
            aria-labelledby="{{ $id }}"
            class="max-h-52 overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1.5 shadow-lg dark:border-[#333333] dark:bg-[#171717]"
        >
            @if($searchable)
            <div class="sticky top-0 bg-white pb-1.5 dark:bg-[#171717]">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400 dark:text-neutral-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input
                        type="text"
                        x-ref="search"
                        x-model="filter"
                        @input="activeIndex = 0"
                        placeholder="Cari…"
                        autocomplete="off"
                        aria-label="Cari pilihan"
                        @keydown="
                            if ($event.key === 'Escape') { close(true); }
                            else if ($event.key === 'Enter') { $event.preventDefault(); chooseActive(); }
                            else if ($event.key === 'ArrowDown') { $event.preventDefault(); move(1); }
                            else if ($event.key === 'ArrowUp') { $event.preventDefault(); move(-1); }
                            $event.stopPropagation();
                        "
                        class="w-full rounded-lg border border-neutral-200 bg-neutral-50 py-2 pl-9 pr-3 text-sm text-neutral-900 placeholder-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 dark:border-[#333333] dark:bg-[#262626] dark:text-neutral-100 dark:placeholder-neutral-500 dark:focus:border-neutral-100 dark:focus:ring-neutral-100"
                    >
                </div>
            </div>
            @endif

            <template x-for="(opt, i) in filtered" :key="opt.value + '-' + i">
                <div
                    role="option"
                    :id="'{{ $id }}-opt-' + i"
                    :data-index="i"
                    :aria-selected="(opt.value === value).toString()"
                    @click="choose(opt)"
                    @mouseenter="activeIndex = i"
                    class="flex min-h-[44px] cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm text-neutral-700 transition-colors dark:text-neutral-200"
                    :class="i === activeIndex ? 'bg-neutral-100 dark:bg-[#262626]' : (opt.value === value ? 'bg-neutral-50 dark:bg-[#262626]/50' : '')"
                >
                    <span class="min-w-0 flex-1 truncate" :class="(opt.value === value) && 'font-semibold text-neutral-900 dark:text-neutral-50'" x-text="opt.label"></span>
                    <svg x-show="opt.value === value" class="h-4 w-4 shrink-0 text-neutral-900 dark:text-neutral-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
            </template>

            <div x-show="filtered.length === 0" class="px-3 py-6 text-center text-xs text-neutral-400 dark:text-neutral-500">
                Tidak ada hasil untuk &ldquo;<span x-text="filter" class="font-semibold"></span>&rdquo;
            </div>
        </div>
    </div>
</div>
