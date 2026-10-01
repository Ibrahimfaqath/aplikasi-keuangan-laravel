<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Terlalu Banyak Permintaan - dompetku</title>
    @include('partials.theme-boot')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex items-center justify-center px-4 py-12 text-base-content font-sans antialiased">
    <main class="w-full max-w-md text-center bg-base-100 border border-base-300 rounded-2xl shadow-sm p-8 space-y-4">
        <p class="text-5xl" aria-hidden="true">⏳</p>
        <h1 class="text-xl font-bold tracking-tight">Sabar ya, kamu terlalu cepat!</h1>
        <p class="text-sm text-base-content/60">
            Terlalu banyak permintaan. Coba lagi dalam
            <span class="font-bold text-base-content">{{ $retryAfter ?? 60 }} detik</span>.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            <a href="{{ url()->previous() === url()->current() ? route('transactions.index') : url()->previous() }}"
               class="btn btn-primary w-full sm:w-auto">
                Kembali
            </a>
            <a href="{{ route('transactions.index') }}"
               class="btn btn-outline w-full sm:w-auto">
                Ke Dashboard
            </a>
        </div>
    </main>
</body>
</html>
