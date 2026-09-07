<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center gap-2 px-4 py-2 bg-neutral-900 hover:bg-black active:bg-black border border-neutral-900 rounded-xl font-semibold text-xs text-white uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:ring-offset-2 dark:focus:ring-neutral-400 dark:focus:ring-offset-[#0A0A0A] transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
