@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'px-4 py-3 text-sm rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition']) }}>
