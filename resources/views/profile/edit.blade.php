<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 dark:bg-slate-950">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Kelola profil akun DompetKu — nama, email, dan keamanan.">
    <meta name="theme-color" content="#4f46e5">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Branding / Icons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="DompetKu">
    <meta property="og:title" content="Profil - DompetKu">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Profil - DompetKu</title>

    <!-- Theme Init -->
    <script>
        (function initTheme() {
            try {
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = stored === 'dark' || (!stored && prefersDark);
                if (isDark) document.documentElement.classList.add('dark');
            } catch (e) {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { overflow-x: hidden; }
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="min-h-full bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased flex flex-col">

    <!-- NAVBAR -->
    <x-navbar />

    <!-- Toast Notification -->
    @if(session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="fixed top-20 right-6 z-50 flex items-center w-full max-w-sm p-4 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="ml-3 text-xs font-semibold text-slate-700 dark:text-slate-200">{{ session('status') }}</div>
        <button @click="show = false" class="ml-auto p-1.5 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <!-- MAIN CONTENT -->
    <div class="flex-1 w-full max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">

        <!-- Page Header -->
        <div class="flex items-center gap-3">
            <div class="p-3 bg-indigo-50 dark:bg-indigo-950/60 rounded-xl text-indigo-600 dark:text-indigo-400 border border-indigo-100/60 dark:border-indigo-900/50">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Profil Saya</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">Kelola informasi akun dan keamanan kamu.</p>
            </div>
        </div>

        <!-- Profile Card — Avatar & Info -->
        <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="relative h-24 sm:h-32 bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-500">
                <div class="absolute -bottom-10 left-6 sm:left-8">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-white dark:bg-slate-900 border-4 border-white dark:border-slate-900 shadow-lg flex items-center justify-center text-2xl sm:text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                </div>
            </div>
            <div class="pt-14 sm:pt-16 pb-6 px-6 sm:px-8">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ Auth::user()->name ?? 'Pengguna' }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ Auth::user()->email ?? '' }}</p>
                <div class="flex items-center gap-2 mt-3">
                    @if(Auth::user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !Auth::user()->hasVerifiedEmail())
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 rounded-full text-xs font-semibold border border-amber-200/60 dark:border-amber-900/50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Email belum terverifikasi
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-full text-xs font-semibold border border-emerald-200/60 dark:border-emerald-900/50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Terverifikasi
                        </span>
                    @endif
                </div>
            </div>
        </section>

        <!-- Profile Information Form -->
        <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 sm:px-8 py-5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Informasi Profil</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Perbarui nama dan alamat email akun kamu.</p>
                    </div>
                </div>
            </div>

            <form method="post" action="{{ route('profile.update') }}" class="p-6 sm:p-8 space-y-5">
                @csrf
                @method('patch')

                <!-- Name -->
                <div class="space-y-2">
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        Nama Lengkap
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition">
                    </div>
                    @error('name')
                        <p class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        Alamat Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition">
                    </div>
                    @error('email')
                        <p class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                        <div class="flex items-start gap-2 p-3 bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/50 rounded-xl">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div>
                                <p class="text-xs font-semibold text-amber-700 dark:text-amber-400">Email belum terverifikasi.</p>
                                <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="mt-1">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-amber-600 dark:text-amber-400 underline underline-offset-2 hover:text-amber-700">Kirim ulang link verifikasi</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-indigo-600/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Perubahan
                    </button>

                    @if(session('status') === 'profile-updated')
                        <span x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition
                              class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Tersimpan!
                        </span>
                    @endif
                </div>
            </form>
        </section>

        <!-- Update Password Form -->
        <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 sm:px-8 py-5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Ubah Password</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pastikan akun kamu menggunakan password yang kuat.</p>
                    </div>
                </div>
            </div>

            <form method="post" action="{{ route('password.update') }}" class="p-6 sm:p-8 space-y-5">
                @csrf
                @method('put')

                <!-- Current Password -->
                <div class="space-y-2">
                    <label for="update_password_current_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        Password Saat Ini
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <input type="password" name="current_password" id="update_password_current_password" autocomplete="current-password"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition">
                    </div>
                    @error('updatePassword.current_password')
                        <p class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- New Password -->
                <div class="space-y-2">
                    <label for="update_password_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        Password Baru
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        </div>
                        <input type="password" name="password" id="update_password_password" autocomplete="new-password"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition">
                    </div>
                    @error('updatePassword.password')
                        <p class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="space-y-2">
                    <label for="update_password_password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        Konfirmasi Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <input type="password" name="password_confirmation" id="update_password_password_confirmation" autocomplete="new-password"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition"
                               placeholder="Ulangi password baru">
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-indigo-600/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Perbarui Password
                    </button>

                    @if(session('status') === 'password-updated')
                        <span x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition
                              class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Password diperbarui!
                        </span>
                    @endif
                </div>
            </form>
        </section>

        <!-- Delete Account -->
        <section class="bg-white dark:bg-slate-900 rounded-2xl border border-rose-200/60 dark:border-rose-900/30 shadow-sm overflow-hidden">
            <div class="px-6 sm:px-8 py-5 border-b border-rose-100 dark:border-rose-900/30">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-rose-50 dark:bg-rose-950/60 rounded-xl text-rose-600 dark:text-rose-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Hapus Akun</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Hapus akun dan semua data secara permanen.</p>
                    </div>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <div class="flex items-start gap-3 p-4 bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200/60 dark:border-rose-900/50 rounded-xl mb-4">
                    <svg class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <p class="text-sm font-semibold text-rose-700 dark:text-rose-400">Peringatan: Tindakan ini tidak dapat dibatalkan.</p>
                        <p class="text-xs text-rose-600/80 dark:text-rose-400/80 mt-1">Semua transaksi, anggaran, dan data lainnya akan dihapus secara permanen.</p>
                    </div>
                </div>

                <button type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold shadow-sm shadow-rose-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus Akun
                </button>
            </div>
        </section>

    </div>

    <!-- Delete Account Modal -->
    <div x-data="{ show: false }" x-show="show" x-cloak
         x-on:open-modal.window="show = $event.detail === 'confirm-user-deletion'"
         x-on:close.window="show = false"
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm" x-on:click="show = false"></div>
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="show" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-8">
                    @csrf
                    @method('delete')

                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2.5 bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Konfirmasi Hapus Akun</h3>
                    </div>

                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-5">
                        Masukkan password kamu untuk mengkonfirmasi penghapusan akun secara permanen.
                    </p>

                    <div class="space-y-2">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                            Password
                        </label>
                        <input type="password" name="password" id="password" placeholder="Masukkan password kamu"
                               class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:bg-white dark:focus:bg-slate-800 transition">
                        @error('userDeletion.password')
                            <p class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" x-on:click="show = false"
                                class="px-4 py-2.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                            Batal
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold shadow-sm shadow-rose-600/20 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Ya, Hapus Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <x-footer />

</body>
</html>
