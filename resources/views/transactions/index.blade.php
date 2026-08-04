<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Keuangan Pribadi</title>
    <!-- CDN Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased">

    <div class="max-w-4xl mx-auto mt-10 p-6 bg-white rounded-xl shadow-md">
        
        <!-- Header & Navigasi -->
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-200">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Catatan Keuangan</h1>
                <p class="text-sm text-slate-500">Pantau arus kas pemasukan dan pengeluaranmu</p>
            </div>
            <a href="/transactions/create" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-lg transition duration-200 shadow">
                + Tambah Transaksi
            </a>
        </div>

        <!-- Tabel Transaksi -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs uppercase tracking-wider">
                        <th class="py-3 px-4">No</th>
                        <th class="py-3 px-4">Keterangan</th>
                        <th class="py-3 px-4">Nominal</th>
                        <th class="py-3 px-4">Jenis</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($transactions as $index => $item)
                        <tr class="hover:bg-slate-50 transition duration-150">
                            <td class="py-3 px-4 font-medium text-slate-500">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $item->title }}</td>
                            <td class="py-3 px-4 font-bold text-slate-900">
                                Rp {{ number_format($item->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4">
                                @if($item->type == 'income')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        Pemasukan
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                                        Pengeluaran
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center space-x-2">
                                    <a href="/transactions/{{ $item->id }}/edit" class="text-amber-600 hover:text-amber-800 font-medium hover:underline text-xs">
                                        Edit
                                    </a>
                                    <span class="text-slate-300">|</span>
                                    <form action="/transactions/{{ $item->id }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Yakin ingin menghapus transaksi ini?')" class="text-rose-600 hover:text-rose-800 font-medium hover:underline text-xs">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                Belum ada data transaksi. Silakan tambah data baru!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>