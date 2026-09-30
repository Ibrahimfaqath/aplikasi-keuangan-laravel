<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Tambahkan transaksi pemasukan atau pengeluaran dengan bukti foto di dompetku.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Tambah Transaksi - dompetku">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Tambah Transaksi - dompetku</title>

    @include('partials.theme-boot')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('transactions.partials.form-styles')
</head>

<body class="app-shell-content min-h-full bg-neutral-50 dark:bg-[#0A0A0A] text-neutral-900 dark:text-neutral-100 font-sans antialiased">

    <x-sidebar title="Tambah Transaksi" :back="route('transactions.index')" minimal />

    <x-flash />


    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <form action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            @include('transactions.partials.form-fields', ['transaction' => null, 'submitLabel' => 'Simpan Transaksi'])
        </form>
    </div>

    @include('transactions.partials.form-scripts', ['transaction' => null])

    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

</body>
</html>
