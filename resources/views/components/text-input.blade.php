@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'px-4 py-3 text-sm rounded-xl border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-neutral-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition']) }}>
