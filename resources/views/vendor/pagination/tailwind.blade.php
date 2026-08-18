@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between py-3">
        
        <!-- MOBILE VIEW (HANYA BUTTON SEBELUMNYA & SELANJUTNYA) -->
        <div class="flex justify-between flex-1 sm:hidden gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-4 py-2 text-xs font-medium text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 rounded-lg cursor-not-allowed">
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-blue-50 dark:hover:bg-navy-400/20 hover:text-blue-600 dark:text-navy-300 transition-colors">
                    Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-blue-50 dark:hover:bg-navy-400/20 hover:text-blue-600 dark:text-navy-300 transition-colors">
                    Selanjutnya
                </a>
            @else
                <span class="inline-flex items-center px-4 py-2 text-xs font-medium text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 rounded-lg cursor-not-allowed">
                    Selanjutnya
                </span>
            @endif
        </div>

        <!-- DESKTOP / TABLET VIEW -->
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between gap-4">
            
            <!-- LEFT: INFORMATION SUMMARY -->
            <div>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Menampilkan
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->firstItem() ?? 0 }}</span>
                    sampai
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->lastItem() ?? 0 }}</span>
                    dari
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->total() }}</span>
                    transaksi
                </p>
            </div>

            <!-- RIGHT: PAGINATION NUMBERS & ARROWS -->
            <div>
                <span class="relative z-0 inline-flex items-center gap-1.5">
                    
                    <!-- Tombol Prev -->
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center justify-center p-2 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 text-gray-300 dark:text-gray-600 cursor-not-allowed" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center justify-center p-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-navy-400/20 hover:text-blue-600 dark:text-navy-300 dark:hover:text-navy-200 hover:border-blue-200 transition-all duration-150" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        </a>
                    @endif

                    <!-- Angka Halaman -->
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 text-xs font-medium text-gray-400 dark:text-gray-500">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center justify-center px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 dark:bg-navy-400 dark:text-white text-white shadow-sm shadow-blue-600/30">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center justify-center px-3.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-navy-400/20 hover:text-blue-600 dark:text-navy-300 dark:hover:text-navy-200 hover:border-blue-200 transition-all duration-150" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    <!-- Tombol Next -->
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center justify-center p-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-navy-400/20 hover:text-blue-600 dark:text-navy-300 dark:hover:text-navy-200 hover:border-blue-200 transition-all duration-150" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center justify-center p-2 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 text-gray-300 dark:text-gray-600 cursor-not-allowed" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </span>
                        </span>
                    @endif

                </span>
            </div>

        </div>
    </nav>
@endif