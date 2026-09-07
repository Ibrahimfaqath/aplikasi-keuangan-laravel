@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between py-3">

        <!-- MOBILE VIEW (HANYA BUTTON SEBELUMNYA & SELANJUTNYA) -->
        <div class="flex justify-between flex-1 sm:hidden gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-4 py-2 text-xs font-medium text-neutral-400 dark:text-neutral-500 bg-neutral-100 dark:bg-[#262626] border border-neutral-200 dark:border-[#333333] rounded-xl cursor-not-allowed">
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center px-4 py-2 text-xs font-medium text-neutral-700 dark:text-neutral-200 bg-white dark:bg-[#171717] border border-neutral-300 dark:border-[#333333] rounded-xl hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-white transition-colors">
                    Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center px-4 py-2 text-xs font-medium text-neutral-700 dark:text-neutral-200 bg-white dark:bg-[#171717] border border-neutral-300 dark:border-[#333333] rounded-xl hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 dark:hover:text-white transition-colors">
                    Selanjutnya
                </a>
            @else
                <span class="inline-flex items-center px-4 py-2 text-xs font-medium text-neutral-400 dark:text-neutral-500 bg-neutral-100 dark:bg-[#262626] border border-neutral-200 dark:border-[#333333] rounded-xl cursor-not-allowed">
                    Selanjutnya
                </span>
            @endif
        </div>

        <!-- DESKTOP / TABLET VIEW -->
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between gap-4">

            <!-- LEFT: INFORMATION SUMMARY -->
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Menampilkan
                    <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $paginator->firstItem() ?? 0 }}</span>
                    sampai
                    <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $paginator->lastItem() ?? 0 }}</span>
                    dari
                    <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $paginator->total() }}</span>
                    transaksi
                </p>
            </div>

            <!-- RIGHT: PAGINATION NUMBERS & ARROWS -->
            <div>
                <span class="relative z-0 inline-flex items-center gap-1.5">

                    <!-- Tombol Prev -->
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center justify-center p-2 rounded-xl border border-neutral-200 dark:border-[#333333] bg-neutral-50 dark:bg-[#262626]/40 text-neutral-300 dark:text-neutral-600 cursor-not-allowed" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center justify-center p-2 rounded-xl border border-neutral-300 dark:border-[#333333] bg-white dark:bg-[#171717] text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 hover:border-neutral-400 transition-all duration-150" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        </a>
                    @endif

                    <!-- Angka Halaman -->
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center justify-center px-3 py-1.5 rounded-xl border border-neutral-200 dark:border-[#333333] bg-white dark:bg-[#171717] text-xs font-medium text-neutral-400 dark:text-neutral-500">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center justify-center px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-neutral-900 dark:bg-neutral-100 dark:text-neutral-900 text-white shadow-sm">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center justify-center px-3.5 py-1.5 rounded-xl border border-neutral-300 dark:border-[#333333] bg-white dark:bg-[#171717] text-xs font-medium text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 hover:border-neutral-400 transition-all duration-150" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    <!-- Tombol Next -->
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center justify-center p-2 rounded-xl border border-neutral-300 dark:border-[#333333] bg-white dark:bg-[#171717] text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 hover:border-neutral-400 transition-all duration-150" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center justify-center p-2 rounded-xl border border-neutral-200 dark:border-[#333333] bg-neutral-50 dark:bg-[#262626]/40 text-neutral-300 dark:text-neutral-600 cursor-not-allowed" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </span>
                        </span>
                    @endif

                </span>
            </div>

        </div>
    </nav>
@endif
