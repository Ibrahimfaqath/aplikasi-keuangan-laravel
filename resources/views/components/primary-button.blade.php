<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-gold-400 dark:hover:bg-gold-300 dark:text-black active:bg-blue-800 border border-transparent rounded-lg font-semibold text-xs text-white tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-900 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
