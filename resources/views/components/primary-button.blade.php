<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 h-11 px-6 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white border border-transparent rounded-xl font-semibold text-sm shadow-sm shadow-indigo-600/15 transition focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus:ring-indigo-400 dark:focus:ring-offset-slate-900']) }}>
    {{ $slot }}
</button>
