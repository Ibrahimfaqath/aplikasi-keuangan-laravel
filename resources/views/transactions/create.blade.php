<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Transaksi Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased">

    <div class="max-w-md mx-auto mt-10 p-6 bg-white rounded-xl shadow-md">
        
        <div class="mb-6 pb-3 border-b border-slate-200">
            <h1 class="text-xl font-bold text-slate-900">Tambah Transaksi</h1>
            <a href="/transactions" class="text-xs text-indigo-600 hover:underline">← Kembali ke Daftar Transaksi</a>
        </div>

        <form action="/transactions" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Keterangan Transaksi</label>
                <input type="text" id="title" name="title" placeholder="Misal: Beli Perlengkapan, Bisyarah, dll" required 
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-slate-700 mb-1">Nominal (Rp)</label>
                <input type="number" id="amount" name="amount" placeholder="0" required 
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-slate-700 mb-1">Jenis Transaksi</label>
                <select id="type" name="type" required 
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="expense">Pengeluaran</option>
                    <option value="income">Pemasukan</option>
                </select>
            </div>

            <button type="submit" 
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 shadow text-sm">
                Simpan Transaksi
            </button>
        </form>

    </div>

</body>
</html>