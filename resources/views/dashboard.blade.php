@extends('layouts.app')

@section('content')
<div class="space-y-8" x-data="dashboardApp()" x-init="initDashboard()">
    
    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
                Ringkasan Keuangan
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Pantau seluruh pemasukan, pengeluaran, dan arus kas kamu.
            </p>
        </div>

        <!-- Tombol Privasi -->
        <button id="privacy-toggle-btn" type="button"
            @click="togglePrivacy()"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
            <svg id="privacy-eye-open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <svg id="privacy-eye-closed" class="hidden w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.025 10.025 0 0110.141 3.937A10.01 10.01 0 0121.542 12c-.876 2.791-3.024 5.025-5.753 6.136M9.88 9.88a3 3 0 104.243 4.243m-4.242-4.242L3 3m18 18l-18-18" />
            </svg>
            <span id="privacy-btn-text" x-text="isPrivate ? 'Tampilkan Saldo' : 'Sembunyikan Saldo'"></span>
        </button>
    </div>

    <!-- 1. KARTU RINGKASAN SALDO -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- KARTU 1: TOTAL SALDO -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Saldo</span>
                <div class="p-2 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="h-9 flex items-center">
                <!-- Skeleton (tampil saat loading) -->
                <div class="skeleton-item h-8 w-44 bg-gray-200 dark:bg-gray-700 rounded-lg animate-shimmer" 
                     x-show="isLoading"></div>
                <!-- Content (tampil saat selesai loading) -->
                <div class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white" 
                     x-show="!isLoading">
                    <span class="balance-text" 
                          data-value="Rp {{ number_format($totalSaldo ?? 15500000, 0, ',', '.') }}"
                          x-text="isPrivate ? '••••••••' : 'Rp {{ number_format($totalSaldo ?? 15500000, 0, ',', '.') }}'">
                    </span>
                </div>
            </div>
        </div>

        <!-- KARTU 2: PEMASUKAN -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Pemasukan Bulan Ini</span>
                <div class="p-2 bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                    </svg>
                </div>
            </div>
            <div class="h-9 flex items-center">
                <div class="skeleton-item h-8 w-36 bg-gray-200 dark:bg-gray-700 rounded-lg animate-shimmer" 
                     x-show="isLoading"></div>
                <div class="text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400" 
                     x-show="!isLoading">
                    <span class="balance-text" 
                          data-value="Rp {{ number_format($pemasukan ?? 8200000, 0, ',', '.') }}"
                          x-text="isPrivate ? '••••••••' : 'Rp {{ number_format($pemasukan ?? 8200000, 0, ',', '.') }}'">
                    </span>
                </div>
            </div>
        </div>

        <!-- KARTU 3: PENGELUARAN -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Pengeluaran Bulan Ini</span>
                <div class="p-2 bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                    </svg>
                </div>
            </div>
            <div class="h-9 flex items-center">
                <div class="skeleton-item h-8 w-36 bg-gray-200 dark:bg-gray-700 rounded-lg animate-shimmer" 
                     x-show="isLoading"></div>
                <div class="text-3xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400" 
                     x-show="!isLoading">
                    <span class="balance-text" 
                          data-value="Rp {{ number_format($pengeluaran ?? 3450000, 0, ',', '.') }}"
                          x-text="isPrivate ? '••••••••' : 'Rp {{ number_format($pengeluaran ?? 3450000, 0, ',', '.') }}'">
                    </span>
                </div>
            </div>
        </div>

    </div>

    <!-- 2. TABEL TRANSAKSI TERAKHIR -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Transaksi Terakhir</h2>
            <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-full">Bulan Ini</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-700/40 text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-4">Kategori & Transaksi</th>
                        <th scope="col" class="px-6 py-4">Tanggal</th>
                        <th scope="col" class="px-6 py-4">Status</th>
                        <th scope="col" class="px-6 py-4 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">

                    <!-- SKELETON ROWS -->
                    @for ($i = 0; $i < 3; $i++)
                    <tr class="skeleton-item" x-show="isLoading">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-xl animate-shimmer"></div>
                                <div class="space-y-2">
                                    <div class="h-4 w-32 bg-gray-200 dark:bg-gray-700 rounded animate-shimmer"></div>
                                    <div class="h-3 w-20 bg-gray-200 dark:bg-gray-700 rounded animate-shimmer"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4"><div class="h-4 w-24 bg-gray-200 dark:bg-gray-700 rounded animate-shimmer"></div></td>
                        <td class="px-6 py-4"><div class="h-6 w-16 bg-gray-200 dark:bg-gray-700 rounded-full animate-shimmer"></div></td>
                        <td class="px-6 py-4 text-right"><div class="h-4 w-28 bg-gray-200 dark:bg-gray-700 rounded animate-shimmer ml-auto"></div></td>
                    </tr>
                    @endfor

                    <!-- CONTENT ROWS (contoh data statis) -->
                    <tr class="content-item" x-show="!isLoading">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-xl">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">Gaji Bulanan</p>
                                    <p class="text-xs text-gray-400">Transfer Bank</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">01 Aug 2026</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Selesai</span>
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                            <span class="balance-text" 
                                  data-value="+Rp 8.200.000"
                                  x-text="isPrivate ? '••••••••' : '+Rp 8.200.000'">
                            </span>
                        </td>
                    </tr>

                    <tr class="content-item" x-show="!isLoading">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 rounded-xl">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">Belanja Bulanan</p>
                                    <p class="text-xs text-gray-400">Supermarket</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">03 Aug 2026</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Selesai</span>
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                            <span class="balance-text" 
                                  data-value="-Rp 1.450.000"
                                  x-text="isPrivate ? '••••••••' : '-Rp 1.450.000'">
                            </span>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Alpine.js component untuk dashboard -->
<script>
    function dashboardApp() {
        return {
            isLoading: true,
            isPrivate: false,
            
            initDashboard() {
                // Ambil privacy state dari localStorage
                const privacyStored = localStorage.getItem('privacy_mode');
                this.isPrivate = privacyStored === 'enabled';
                
                // Simulasi loading
                this.isLoading = true;
                
                // Gunakan requestAnimationFrame untuk memastikan skeleton dirender
                requestAnimationFrame(() => {
                    // Beri waktu minimal untuk skeleton terlihat
                    const start = performance.now();
                    
                    const waitForRender = () => {
                        if (document.querySelector('.skeleton-item')) {
                            // Skeleton ada di DOM, selesai loading
                            setTimeout(() => {
                                this.isLoading = false;
                            }, 50);
                        } else if (performance.now() - start < 300) {
                            requestAnimationFrame(waitForRender);
                        } else {
                            this.isLoading = false;
                        }
                    };
                    
                    waitForRender();
                });
            },
            
            togglePrivacy() {
                this.isPrivate = !this.isPrivate;
                localStorage.setItem('privacy_mode', this.isPrivate ? 'enabled' : 'disabled');
                
                // Update semua balance-text secara manual (karena Alpine reactive)
                // Tapi karena kita pakai x-text, Alpine sudah handle
            }
        }
    }
</script>
@endsection