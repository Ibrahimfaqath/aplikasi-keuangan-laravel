<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Kelola profil akun dompetku — nama, email, dan keamanan.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Profil - dompetku">
    <title>Profil - dompetku</title>

    @include('partials.theme-boot')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="app-shell-content min-h-full font-sans antialiased text-base-content">

    <x-sidebar title="Profil Saya" :back="route('transactions.index')" minimal />
    <x-flash :status="session('status')" />

    <div class="flex-1 w-full max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">

        @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
        {{-- `alert` untuk bentuk + radius saja, tata letaknya tetap baris
             (utility `flex` menimpa grid bawaan daisyUI). Warnanya lewat token
             semantic, bukan modifier alert-warning: modifier itu dicampur
             base-100 sehingga kontrasnya pecah di mode gelap. Alasan lengkapnya
             ada di components/flash.blade.php. --}}
        <div class="alert flex items-start gap-2.5 p-3.5 border border-warning/30 bg-warning/10 text-base-content">
            <svg class="w-4 h-4 mt-0.5 shrink-0 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            <p class="text-xs font-semibold text-warning">
                Mode demo — akun ini tidak bisa diubah atau dihapus.
                <a href="{{ route('register') }}" class="link">Daftar gratis</a> untuk akun milikmu sendiri.
            </p>
        </div>
        @endif

        <!-- Avatar Card -->
{{-- `avatar` + `placeholder` untuk lingkaran inisial; `card`
                 untuk permukaannya. daisyUI v4 menuliskannya `.avatar.placeholder`
                 (dua kelas terpisah), bukan `avatar-placeholder`. --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm flex-row items-center gap-4 p-6">
                <div class="avatar placeholder">
                    <div class="w-16 rounded-box bg-base-content text-base-100">
                        <span class="text-2xl font-extrabold">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                    </div>
                </div>
            <div class="min-w-0">
                <h2 class="text-base font-bold text-base-content truncate">{{ Auth::user()->name ?? 'Pengguna' }}</h2>
                <p class="text-xs text-base-content/60 truncate">{{ Auth::user()->email ?? '' }}</p>
            </div>
        </div>

        <!-- Form: Informasi Profil -->
        <section class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-base-300">
                <h3 class="text-sm font-bold text-base-content">Informasi Profil</h3>
                <p class="text-xs text-base-content/60 mt-0.5">Perbarui nama dan email akun kamu.</p>
            </div>

            <form method="post" action="{{ route('profile.update') }}" class="card-body p-6 gap-5">
                @csrf @method('patch')

                <div>
                    <x-input-label for="name" value="Nama Lengkap" class="mb-1.5" />
                    <x-text-input id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name"
                                  placeholder="Nama lengkap kamu" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" class="mb-1.5" />
                    <x-text-input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                                  placeholder="nama@email.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5" />

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                        <p class="text-xs text-warning font-medium mt-2">
                            Email belum terverifikasi.
                        </p>
                    @endif
                </div>

                <div class="pt-3 border-t border-base-300 flex items-center gap-3">
                    @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
                    <span class="btn btn-sm cursor-not-allowed bg-base-200 text-base-content/40">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        Terkunci
                    </span>
                    @else
                    {{-- `x-primary-button` sudah btn btn-primary; `sm` ditambahkan lewat
                         attribute merge, `sm` lebih dekat ke token (btn-sm) daripada
                         `btn-sm` bawaan komponen (btn), jadi utility menang. --}}
                    <x-primary-button class="btn-sm">Simpan</x-primary-button>
                    @endif
                    @if(session('status') === 'profile-updated')
                        <span x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 3000)" x-transition class="text-xs font-semibold text-success">Tersimpan!</span>
                    @endif
                </div>
            </form>
            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
            <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="px-6 pb-6">
                @csrf
                <button type="submit" class="link link-hover text-xs font-bold">Kirim ulang email verifikasi</button>
            </form>
            @endif
        </section>

        <!-- Form: Ubah Password -->
        <section class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-base-300">
                <h3 class="text-sm font-bold text-base-content">Ubah Password</h3>
                <p class="text-xs text-base-content/60 mt-0.5">Gunakan password yang kuat untuk keamanan akun.</p>
            </div>

            <form method="post" action="{{ route('password.update') }}" class="card-body p-6 gap-5">
                @csrf @method('put')

                <div>
                    <x-input-label for="update_password_current_password" value="Password Saat Ini" class="mb-1.5" />
                    <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('updatePassword.current_password')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="update_password_password" value="Password Baru" class="mb-1.5" />
                    <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('updatePassword.password')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="update_password_password_confirmation" value="Konfirmasi Password" class="mb-1.5" />
                    <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                                  placeholder="Ulangi password baru" />
                </div>

                <div class="pt-3 border-t border-base-300 flex items-center gap-3">
                    @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
                    <span class="btn btn-sm cursor-not-allowed bg-base-200 text-base-content/40">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        Terkunci
                    </span>
                    @else
                    <x-primary-button class="btn-sm">Perbarui Password</x-primary-button>
                    @endif
                    @if(session('status') === 'password-updated')
                        <span x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 3000)" x-transition class="text-xs font-semibold text-success">Password diperbarui!</span>
                    @endif
                </div>
            </form>
        </section>

        <!-- Pengaturan (Tema, Privasi, Keluar) -->
        <section class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-base-300">
                <h3 class="text-sm font-bold text-base-content">Pengaturan</h3>
                <p class="text-xs text-base-content/60 mt-0.5">Tema, privasi saldo, dan keluar dari akun.</p>
            </div>
            {{-- Baris-baris ini TIDAK memakai komponen `menu` daisyUI: komponen itu
                 menyetel `li > *` jadi grid 3 kolom (ikon / label / info) dan
                 anak-anaknya harus <li>. Konten di sini justru <button> dan
                 <form>, jadi memaksakan `menu` hanya menambah display yang
                 langsung ditimpa utility — tanpa manfaat. Permukaannya tetap
                 token tema lewat utility. --}}
            <div class="p-3 space-y-1">
                <div class="px-3.5 py-2.5">
                    <span class="block text-xs font-semibold uppercase tracking-wider text-base-content/60 mb-2">Tema Tampilan</span>
                    <button type="button" data-theme-toggle
                            class="w-full flex items-center gap-3 px-3 py-2 rounded-btn text-sm font-semibold text-base-content/70 hover:bg-base-200 transition-colors text-left"
                            aria-label="Ganti tema terang atau gelap">
                        <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <span>Ganti Tema Terang / Gelap</span>
                    </button>
                </div>

                <button type="button" data-privacy-toggle
                        class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-btn text-sm font-semibold text-base-content/70 hover:bg-base-200 transition-colors text-left"
                        aria-label="Sembunyikan atau tampilkan saldo">
                    <svg data-lock-open class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                    </svg>
                    <svg data-lock-closed class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                    </svg>
                    <span data-privacy-label>Sembunyikan Saldo</span>
                </button>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-btn text-sm font-semibold text-base-content/70 hover:bg-base-200 transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </section>

        <!-- Hapus Akun -->
        <section class="card bg-base-100 border-2 border-base-content shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-base-300">
                <h3 class="text-sm font-bold text-base-content">Hapus Akun</h3>
                <p class="text-xs text-base-content/60 mt-0.5">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="p-6">
                {{-- `alert` untuk bentuk + radius, warna netral (bukan error):
                     kartu ini memperingatkan, tombolnya yang destruktif. --}}
                <div class="alert flex items-start gap-3 p-3 bg-base-200 border border-base-300 mb-4">
                    <svg class="w-4 h-4 text-base-content flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-xs font-medium text-base-content/70">Semua transaksi, anggaran, dan data kamu akan dihapus permanen.</p>
                </div>
                @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
                <span class="btn btn-sm cursor-not-allowed bg-base-200 text-base-content/40">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    Terkunci (Mode Demo)
                </span>
                @else
                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                        class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus Akun
                </button>
                @endif
            </div>
        </section>

    </div>

    <!-- Modal Hapus Akun -->
    {{-- Dibangun di atas `modal`/`modal-box` daisyUI, sama seperti
         components/modal.blade.php dan #categoryModal di /categories.

         PERALIHAN VISIBILITAS: sebelumnya `x-show` menyetel `display:none`
         lewat gaya inline. Sekarang daisyUI `.modal` sudah tersembunyi secara
         bawaan (pointer-events:none + opacity 0) dan hanya tampil saat elemennya
         punya kelas `modal-open`, jadi yang ditoggle adalah `modal-open` itu
         sendiri lewat `:class`. `hidden` TIDAK pernah dipakai di sini.

         Alpine state tetap `show`, jadi `x-on:open-modal.window` /
         `x-on:close.window` yang sudah ada tidak perlu berubah. Semua id,
         nama field, dan atribut form tetap sama persis.

         `x-cloak` wajib dipertahankan: `.modal` daisyUI hanya memasang
         `opacity: 0` + `pointer-events: none`, TIDAK `display: none`, jadi
         tanpa cloak field password & tombol di dalamnya masih bisa di-tab
         saat modal tertutup. Alpine menghapus atributnya sendiri begitu
         inisialisasi selesai, sehingga tidak pernah bentrok dengan
         `modal-open`. --}}
    <div x-data="{ show: false }" x-cloak x-on:open-modal.window="show = $event.detail === 'confirm-user-deletion'" x-on:close.window="show = false"
         class="modal"
         :class="{ 'modal-open': show }"
         role="dialog"
         aria-modal="true"
         aria-labelledby="confirm-user-deletion-title">
        <div class="modal-box p-0 sm:max-w-lg" x-show="show"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-8">
                @csrf @method('delete')
                <h3 id="confirm-user-deletion-title" class="text-base font-bold text-base-content mb-2">Yakin ingin menghapus akun?</h3>
                <p class="text-sm text-base-content/60 mb-5">Masukkan password untuk konfirmasi. Semua data akan dihapus permanen.</p>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <x-text-input id="password" name="password" type="password" placeholder="Masukkan password" />
                    <x-input-error :messages="$errors->get('userDeletion.password')" class="mt-1.5" />
                </div>
                <div class="flex items-center justify-end gap-3 mt-6">
                    <button type="button" x-on:click="show = false" class="btn btn-outline btn-sm">Batal</button>
                    <button type="submit" class="btn btn-error btn-sm">Ya, Hapus</button>
                </div>
            </form>
        </div>

        {{-- Overlay + penutup klik-tubuh. `modal-backdrop` membentang penuh di
             dalam grid `.modal`, jadi klik di luar kotak tetap kena elemen ini. --}}
        <div class="modal-backdrop bg-neutral-950/60 backdrop-blur-sm" x-on:click="show = false" aria-hidden="true"></div>
    </div>

    <!-- Export Laporan (PDF/Excel/Print) -->
    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

</body>
</html>