<!DOCTYPE html>
<html lang="id" class="h-full bg-base-200">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Perbarui detail transaksi atau ganti bukti foto di dompetku.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Edit Transaksi - dompetku">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Edit Transaksi - dompetku</title>

    @include('partials.theme-boot')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('transactions.partials.form-styles')
</head>

<body class="app-shell-content min-h-full bg-base-200 text-base-content font-sans antialiased">

    <x-sidebar title="Edit Transaksi" :back="route('transactions.index')" minimal />

    <x-flash />


    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <form action="{{ route('transactions.update', $transaction->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            @include('transactions.partials.form-fields', ['transaction' => $transaction, 'submitLabel' => 'Simpan Perubahan'])
        </form>
    </div>

    @include('transactions.partials.form-scripts', ['transaction' => $transaction])

    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

</body>
</html>
