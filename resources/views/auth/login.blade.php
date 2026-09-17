<x-guest-layout
    :title="'Masuk ke dompetku'"
    :subtitle="'Lacak pemasukan dan pengeluaranmu dengan mudah.'"
    >
    <x-slot:icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"/></svg>
    </x-slot:icon>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-2 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Kata Sandi')" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 rounded-md focus:outline-none focus:ring-2 focus:ring-neutral-900" href="{{ route('password.request') }}">
                        {{ __('Lupa kata sandi?') }}
                    </a>
                @endif
            </div>
            <x-text-input id="password" class="mt-2 w-full" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="flex w-max cursor-pointer items-center gap-2.5 select-none">
            <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded-md border-neutral-300 bg-white text-neutral-900 shadow-sm focus:ring-neutral-900 dark:border-[#333333] dark:bg-[#262626] dark:checked:bg-neutral-100 dark:focus:ring-neutral-100">
            <span class="text-sm text-neutral-600 dark:text-neutral-300">{{ __('Ingat saya') }}</span>
        </label>

        <!-- Submit -->
        <x-primary-button class="w-full">
            {{ __('Masuk') }}
        </x-primary-button>

        <p class="text-center text-sm text-neutral-500 dark:text-neutral-400">
            Belum punya akun?
            <a class="font-semibold text-neutral-900 hover:underline dark:text-neutral-100" href="{{ route('register') }}">Daftar gratis</a>
        </p>
    </form>

    @if (\App\Services\DemoMode::isEnabled())
    <div class="mt-6">
        <div class="flex items-center gap-3">
            <span class="h-px flex-1 bg-neutral-200 dark:bg-[#333333]"></span>
            <span class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">atau</span>
            <span class="h-px flex-1 bg-neutral-200 dark:bg-[#333333]"></span>
        </div>

        <form method="POST" action="{{ route('demo.login') }}" class="mt-5">
            @csrf
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-neutral-100 hover:bg-neutral-200 dark:bg-[#262626] dark:hover:bg-[#333333] text-neutral-700 dark:text-neutral-200 rounded-xl text-xs sm:text-sm font-semibold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
                Coba Demo Tanpa Daftar
            </button>
        </form>
        <p class="mt-3 text-center text-xs text-neutral-400 dark:text-neutral-500">
            Masuk dengan data contoh. Tidak bisa di-ubah, aman untuk dicoba.
        </p>
    </div>
    @endif
</x-guest-layout>