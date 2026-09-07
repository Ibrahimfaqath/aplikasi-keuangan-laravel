@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'px-4 py-3 text-sm rounded-xl border border-neutral-300 dark:border-[#333333] bg-white dark:bg-[#171717] text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 dark:placeholder-neutral-500 shadow-sm focus:border-neutral-900 dark:focus:border-neutral-100 focus:ring-1 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition']) }}>
