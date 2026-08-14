@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-slate-700 dark:bg-slate-800/60 dark:text-white dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm']) }}>
