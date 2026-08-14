@extends('layouts.app')

@section('content')
<div class="space-y-8">

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

    </div>

    <!-- 1. KARTU RINGKASAN SALDO -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- KARTU 1: TOTAL SALDO -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Saldo</span>
                <div class="p-2 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <span class="balance-text block text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white"
                  data-value="Rp {{ number_format($totalSaldo, 0, ',', '.') }}">
                Rp {{ number_format($totalSaldo, 0, ',', '.') }}
            </span>
        </div>

        <!-- KARTU 2: PEMASUKAN BULAN INI -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Pemasukan Bulan Ini</span>
                <div class="p-2 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                    </svg>
                </div>
            </div>
            <span class="balance-text block text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400"
                  data-value="Rp {{ number_format($pemasukan, 0, ',', '.') }}">
                Rp {{ number_format($pemasukan, 0, ',', '.') }}
            </span>
        </div>

        <!-- KARTU 3: PENGELUARAN BULAN INI -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Pengeluaran Bulan Ini</span>
                <div class="p-2 bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                    </svg>
                </div>
            </div>
            <span class="balance-text block text-3xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400"
                  data-value="Rp {{ number_format($pengeluaran, 0, ',', '.') }}">
                Rp {{ number_format($pengeluaran, 0, ',', '.') }}
            </span>
        </div>

    </div>

    <!-- 2. TABEL TRANSAKSI TERAKHIR -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Transaksi Terakhir</h2>
            <a href="{{ route('transactions.index') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                Lihat Semua
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-700/40 text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-4">Transaksi</th>
                        <th scope="col" class="px-6 py-4">Tanggal</th>
                        <th scope="col" class="px-6 py-4">Jenis</th>
                        <th scope="col" class="px-6 py-4 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse ($recentTransactions as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if (!empty($transaction->image))
                                <a href="{{ asset('storage/' . $transaction->image) }}" target="_blank"
                                   class="block w-10 h-10 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 flex-shrink-0">
                                    <img src="{{ asset('storage/' . $transaction->image) }}"
                                         loading="lazy"
                                         class="w-full h-full object-cover"
                                         alt="Bukti {{ $transaction->title }}">
                                </a>
                                @else
                                <div class="p-2.5 {{ $transaction->type === 'income' ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400' }} rounded-xl flex-shrink-0">
                                    @if ($transaction->type === 'income')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    @endif
                                </div>
                                @endif
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $transaction->title }}</p>
                                    <p class="text-xs text-gray-400">
                                        {{ \Carbon\Carbon::parse($transaction->transaction_date)->isoFormat('dddd, D MMM YYYY') }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->type === 'income' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                {{ $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right font-bold whitespace-nowrap {{ $transaction->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            @php $signed = ($transaction->type === 'income' ? '+' : '-') . 'Rp ' . number_format($transaction->amount, 0, ',', '.'); @endphp
                            <span class="balance-text" data-value="{{ $signed }}">{{ $signed }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="w-12 h-12 mx-auto mb-3 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-500 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Belum ada transaksi</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mulai catat transaksi pertamamu sekarang.</p>
                            <a href="{{ route('transactions.create') }}"
                               class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Transaksi
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
