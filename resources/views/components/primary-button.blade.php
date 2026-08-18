<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white active:bg-blue-800 border border-transparent rounded-lg font-semibold text-xs text-white tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-navy-950 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
