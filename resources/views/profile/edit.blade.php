<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Kelola profil akun DompetKu — nama, email, dan keamanan.">
    <meta name="theme-color" content="#0A0A0A">
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
                const isDark = savedTheme !== 'light';
                if (isDark) document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = isDark ? '#0A0A0A' : '#FAFAFA';
            } catch(e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="app-shell-content min-h-full bg-neutral-50 dark:bg-[#0A0A0A] text-neutral-900 dark:text-neutral-100 font-sans antialiased flex flex-col">

    <x-sidebar title="Profil Saya" />

    @if(session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
         class="fixed top-20 right-6 z-50 flex items-center w-full max-w-sm p-4 bg-white dark:bg-[#171717] rounded-2xl shadow-sm border border-neutral-200 dark:border-[#333333]">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 bg-green-50 text-green-600 border border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="ml-3 text-xs font-semibold text-neutral-700 dark:text-neutral-200">{{ session('status') }}</div>
        <button @click="show = false" class="ml-auto p-1.5 text-neutral-400 hover:text-neutral-900 dark:hover:text-white rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <div class="flex-1 w-full max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">

        <!-- Header -->
        <div class="flex items-center gap-3">
            <a href="{{ route('transactions.index') }}" class="p-2 bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-xl text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-neutral-50 tracking-tight">Profil Saya</h1>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Kelola informasi akun kamu.</p>
            </div>
        </div>

        <!-- Avatar Card -->
        <div class="bg-white dark:bg-[#171717] rounded-2xl border border-neutral-200 dark:border-[#333333] shadow-sm p-6 flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 flex items-center justify-center text-2xl font-extrabold flex-shrink-0">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="min-w-0">
                <h2 class="text-base font-bold text-neutral-900 dark:text-neutral-50 truncate">{{ Auth::user()->name ?? 'Pengguna' }}</h2>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">{{ Auth::user()->email ?? '' }}</p>
            </div>
        </div>

        <!-- Form: Informasi Profil -->
        <div class="bg-white dark:bg-[#171717] rounded-2xl border border-neutral-200 dark:border-[#333333] shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Informasi Profil</h3>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Perbarui nama dan email akun kamu.</p>
            </div>

            <form method="post" action="{{ route('profile.update') }}" class="p-6 space-y-5">
                @csrf @method('patch')

                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300 mb-1.5">Nama Lengkap</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required autocomplete="name"
                           class="w-full px-4 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 transition">
                    @error('name') <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300 mb-1.5">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                           class="w-full px-4 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 transition">
                    @error('email') <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1.5">{{ $message }}</p> @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                        <p class="text-xs text-amber-700 dark:text-amber-400 font-medium mt-2">
                            Email belum terverifikasi.
                        </p>
                    @endif
                </div>

                <div class="pt-3 border-t border-neutral-200 dark:border-[#333333] flex items-center gap-3">
                    <button type="submit" class="px-5 py-2.5 bg-neutral-900 hover:bg-neutral-800 dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-sm font-semibold shadow-sm transition">Simpan</button>
                    @if(session('status') === 'profile-updated')
                        <span x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 3000)" x-transition class="text-xs font-semibold text-green-700 dark:text-green-400">Tersimpan!</span>
                    @endif
                </div>
            </form>
            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
            <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="px-6 pb-6">
                @csrf
                <button type="submit" class="text-xs font-bold text-neutral-900 dark:text-neutral-100 underline hover:text-black">Kirim ulang email verifikasi</button>
            </form>
            @endif
        </div>

        <!-- Form: Ubah Password -->
        <div class="bg-white dark:bg-[#171717] rounded-2xl border border-neutral-200 dark:border-[#333333] shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Ubah Password</h3>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Gunakan password yang kuat untuk keamanan akun.</p>
            </div>

            <form method="post" action="{{ route('password.update') }}" class="p-6 space-y-5">
                @csrf @method('put')

                <div>
                    <label for="update_password_current_password" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300 mb-1.5">Password Saat Ini</label>
                    <input type="password" name="current_password" id="update_password_current_password" autocomplete="current-password"
                           class="w-full px-4 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 transition">
                    @error('updatePassword.current_password') <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="update_password_password" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300 mb-1.5">Password Baru</label>
                    <input type="password" name="password" id="update_password_password" autocomplete="new-password"
                           class="w-full px-4 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-sm text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 transition">
                    @error('updatePassword.password') <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="update_password_password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-300 mb-1.5">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" id="update_password_password_confirmation" autocomplete="new-password" placeholder="Ulangi password baru"
                           class="w-full px-4 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 transition">
                </div>

                <div class="pt-3 border-t border-neutral-200 dark:border-[#333333] flex items-center gap-3">
                    <button type="submit" class="px-5 py-2.5 bg-neutral-900 hover:bg-neutral-800 dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-sm font-semibold shadow-sm transition">Perbarui Password</button>
                    @if(session('status') === 'password-updated')
                        <span x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 3000)" x-transition class="text-xs font-semibold text-green-700 dark:text-green-400">Password diperbarui!</span>
                    @endif
                </div>
            </form>
        </div>

        <!-- Pengaturan (Tema, Privasi, Keluar) -->
        <div class="bg-white dark:bg-[#171717] rounded-2xl border border-neutral-200 dark:border-[#333333] shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Pengaturan</h3>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Tema, privasi saldo, dan keluar dari akun.</p>
            </div>
            <div class="p-3 space-y-1">
                <div class="px-3.5 py-2.5">
                    <span class="block text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">Tema Tampilan</span>
                    <div class="grid grid-cols-2 gap-1 p-1 rounded-xl bg-neutral-100 dark:bg-[#262626] border border-neutral-200 dark:border-[#333333]" role="radiogroup" aria-label="Appearance">
                        <button type="button" role="radio" data-theme-option="light" aria-checked="false" onclick="window.setTheme('light')"
                                class="flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg text-xs font-semibold text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                            <span>Light</span>
                        </button>
                        <button type="button" role="radio" data-theme-option="dark" aria-checked="false" onclick="window.setTheme('dark')"
                                class="flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg text-xs font-semibold text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            <span>Dark</span>
                        </button>
                    </div>
                </div>

                <button type="button" data-privacy-toggle
                        class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-[#262626] transition-colors text-left"
                        aria-label="Sembunyikan atau tampilkan saldo">
                    <svg data-eye-open class="w-4 h-4 block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    <svg data-eye-closed class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Sembunyikan Saldo</span>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-[#262626] transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Hapus Akun -->
        <div class="bg-white dark:bg-[#171717] rounded-2xl border-2 border-neutral-900 dark:border-neutral-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Hapus Akun</h3>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="p-6">
                <div class="flex items-start gap-3 p-3 bg-neutral-100 dark:bg-[#262626] border border-neutral-200 dark:border-[#333333] rounded-xl mb-4">
                    <svg class="w-4 h-4 text-neutral-900 dark:text-neutral-100 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-xs font-medium text-neutral-700 dark:text-neutral-200">Semua transaksi, anggaran, dan data kamu akan dihapus permanen.</p>
                </div>
                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-sm font-semibold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus Akun
                </button>
            </div>
        </div>

    </div>

    <!-- Modal Hapus Akun -->
    <div x-data="{ show: false }" x-show="show" x-cloak x-on:open-modal.window="show = $event.detail === 'confirm-user-deletion'" x-on:close.window="show = false"
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-neutral-900/60 dark:bg-black/70 backdrop-blur-sm" x-on:click="show = false"></div>
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="show" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-left shadow-sm sm:my-8 sm:w-full sm:max-w-lg">
                <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-8">
                    @csrf @method('delete')
                    <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-50 mb-2">Yakin ingin menghapus akun?</h3>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-5">Masukkan password untuk konfirmasi. Semua data akan dihapus permanen.</p>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input type="password" name="password" id="password" placeholder="Masukkan password"
                               class="w-full px-4 py-3 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition">
                        @error('userDeletion.password') <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1.5">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button type="button" x-on:click="show = false" class="px-4 py-2.5 bg-white dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 border border-neutral-300 dark:border-[#333333] rounded-xl text-xs font-semibold hover:bg-neutral-50 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs font-semibold shadow-sm transition">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Export Laporan (PDF/Excel/Print) -->
    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

</body>
</html>
