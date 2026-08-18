<!DOCTYPE html>
<html lang="id" class="h-full bg-white dark:bg-navy-950">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Kelola profil akun DompetKu — nama, email, dan keamanan.">
    <meta name="theme-color" content="#0A1128">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="DompetKu">
    <meta property="og:title" content="Profil - DompetKu">
    <title>Profil - DompetKu</title>

    <script>
        (function initTheme() {
            try {
                const savedTheme = localStorage.getItem('theme');
                // Default tema gelap navy (#0A1128) — mode terang hanya jika user memilihnya
                const isDark = savedTheme !== 'light';
                if (isDark) document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = isDark ? '#0A1128' : '#f8fafc';
            } catch(e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="min-h-full bg-white dark:bg-navy-950 text-slate-900 dark:text-white font-sans antialiased flex flex-col">

    <x-navbar />

    @if(session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
         class="fixed top-20 right-6 z-50 flex items-center w-full max-w-sm p-4 bg-white dark:bg-navy-900 rounded-2xl shadow-xl border border-slate-100 dark:border-navy-800">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="ml-3 text-xs font-semibold text-slate-700 dark:text-white/90">{{ session('status') }}</div>
        <button @click="show = false" class="ml-auto p-1.5 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <div class="flex-1 w-full max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">

        <!-- Header -->
        <div class="flex items-center gap-3">
            <a href="{{ route('transactions.index') }}" class="p-2 bg-white dark:bg-navy-900 border border-slate-200 dark:border-navy-800 rounded-xl text-slate-500 dark:text-white/70 hover:bg-slate-50 dark:hover:bg-navy-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Profil Saya</h1>
                <p class="text-xs text-slate-500 dark:text-white/70">Kelola informasi akun kamu.</p>
            </div>
        </div>

        <!-- Avatar Card -->
        <div class="bg-white dark:bg-navy-900 rounded-2xl border border-slate-200/80 dark:border-navy-800 shadow-sm p-6 flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 dark:from-navy-400 dark:to-navy-500 dark:text-white flex items-center justify-center text-white text-2xl font-extrabold shadow-lg shadow-blue-500/20 flex-shrink-0">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="min-w-0">
                <h2 class="text-base font-bold text-slate-900 dark:text-white truncate">{{ Auth::user()->name ?? 'Pengguna' }}</h2>
                <p class="text-xs text-slate-500 dark:text-white/70 truncate">{{ Auth::user()->email ?? '' }}</p>
            </div>
        </div>

        <!-- Form: Informasi Profil -->
        <div class="bg-white dark:bg-navy-900 rounded-2xl border border-slate-200/80 dark:border-navy-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-navy-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Informasi Profil</h3>
                <p class="text-xs text-slate-500 dark:text-white/70 mt-0.5">Perbarui nama dan email akun kamu.</p>
            </div>

            <form method="post" action="{{ route('profile.update') }}" class="p-6 space-y-5">
                @csrf @method('patch')

                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70 mb-1.5">Nama Lengkap</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                           class="w-full px-4 py-3 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    @error('name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70 mb-1.5">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                           class="w-full px-4 py-3 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    @error('email') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p> @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                            Email belum terverifikasi.
                            <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="inline">
                                @csrf
                                <button type="submit" class="underline font-bold hover:text-amber-700">Kirim ulang</button>
                            </form>
                        </p>
                    @endif
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-navy-800 flex items-center gap-3">
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white rounded-xl text-sm font-semibold shadow-md shadow-blue-600/20 transition">Simpan</button>
                    @if(session('status') === 'profile-updated')
                        <span x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 3000)" x-transition class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Tersimpan!</span>
                    @endif
                </div>
            </form>
        </div>

        <!-- Form: Ubah Password -->
        <div class="bg-white dark:bg-navy-900 rounded-2xl border border-slate-200/80 dark:border-navy-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-navy-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Ubah Password</h3>
                <p class="text-xs text-slate-500 dark:text-white/70 mt-0.5">Gunakan password yang kuat untuk keamanan akun.</p>
            </div>

            <form method="post" action="{{ route('password.update') }}" class="p-6 space-y-5">
                @csrf @method('put')

                <div>
                    <label for="update_password_current_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70 mb-1.5">Password Saat Ini</label>
                    <input type="password" name="current_password" id="update_password_current_password" autocomplete="current-password"
                           class="w-full px-4 py-3 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    @error('updatePassword.current_password') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="update_password_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70 mb-1.5">Password Baru</label>
                    <input type="password" name="password" id="update_password_password" autocomplete="new-password"
                           class="w-full px-4 py-3 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    @error('updatePassword.password') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="update_password_password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-white/70 mb-1.5">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" id="update_password_password_confirmation" autocomplete="new-password" placeholder="Ulangi password baru"
                           class="w-full px-4 py-3 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-navy-800 flex items-center gap-3">
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-navy-600 dark:hover:bg-navy-500 dark:text-white text-white rounded-xl text-sm font-semibold shadow-md shadow-blue-600/20 transition">Perbarui Password</button>
                    @if(session('status') === 'password-updated')
                        <span x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 3000)" x-transition class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Password diperbarui!</span>
                    @endif
                </div>
            </form>
        </div>

        <!-- Hapus Akun -->
        <div class="bg-white dark:bg-navy-900 rounded-2xl border border-rose-200/60 dark:border-rose-900/30 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-rose-100 dark:border-rose-900/30">
                <h3 class="text-sm font-bold text-rose-700 dark:text-rose-400">Hapus Akun</h3>
                <p class="text-xs text-slate-500 dark:text-white/70 mt-0.5">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="p-6">
                <div class="flex items-start gap-3 p-3 bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200/60 dark:border-rose-900/50 rounded-xl mb-4">
                    <svg class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-xs text-rose-600 dark:text-rose-400">Semua transaksi, anggaran, dan data kamu akan dihapus permanen.</p>
                </div>
                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold shadow-sm shadow-rose-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus Akun
                </button>
            </div>
        </div>

    </div>

    <!-- Modal Hapus Akun -->
    <div x-data="{ show: false }" x-show="show" x-cloak x-on:open-modal.window="show = $event.detail === 'confirm-user-deletion'" x-on:close.window="show = false"
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-navy-950/80 backdrop-blur-sm" x-on:click="show = false"></div>
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="show" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-navy-900 border border-slate-200 dark:border-navy-800 text-left shadow-2xl sm:my-8 sm:w-full sm:max-w-lg">
                <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-8">
                    @csrf @method('delete')
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Yakin ingin menghapus akun?</h3>
                    <p class="text-sm text-slate-500 dark:text-white/70 mb-5">Masukkan password untuk konfirmasi. Semua data akan dihapus permanen.</p>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input type="password" name="password" id="password" placeholder="Masukkan password"
                               class="w-full px-4 py-3 bg-slate-50 dark:bg-navy-800/60 border border-slate-200 dark:border-navy-700/80 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500 transition">
                        @error('userDeletion.password') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button type="button" x-on:click="show = false" class="px-4 py-2.5 bg-white dark:bg-navy-800 text-slate-700 dark:text-white/80 border border-slate-200 dark:border-navy-700 rounded-xl text-xs font-semibold hover:bg-slate-50 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold shadow-sm shadow-rose-600/20 transition">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Export Laporan (PDF/Excel/Print) -->
    @include('components.export-modal')

    <x-footer />

</body>
</html>
