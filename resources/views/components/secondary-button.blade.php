<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white dark:bg-[#171717] border border-neutral-300 dark:border-[#333333] rounded-xl font-semibold text-xs text-neutral-900 dark:text-neutral-100 uppercase tracking-widest shadow-sm hover:bg-neutral-50 dark:hover:bg-[#262626] focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-400 focus:ring-offset-2 dark:focus:ring-offset-[#0A0A0A] disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
