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
                    <a class="rounded-md text-xs font-semibold text-base-content/60 hover:text-base-content focus:outline-none focus:ring-2 focus:ring-base-content" href="{{ route('password.request') }}">
                        {{ __('Lupa kata sandi?') }}
                    </a>
                @endif
            </div>
            <x-text-input id="password" class="mt-2 w-full" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="flex w-max cursor-pointer items-center gap-2.5 select-none">
            <input id="remember_me" type="checkbox" name="remember" class="checkbox checkbox-xs">
            <span class="text-sm text-base-content/60">{{ __('Ingat saya') }}</span>
        </label>

        <!-- Submit -->
        <x-primary-button class="btn-block">
            {{ __('Masuk') }}
        </x-primary-button>

        <p class="text-center text-sm text-base-content/60">
            Belum punya akun?
            <a class="link-hover font-semibold text-base-content hover:underline" href="{{ route('register') }}">Daftar gratis</a>
        </p>
    </form>

    @if (\App\Services\DemoMode::isEnabled())
    <div class="mt-6">
        <div class="divider my-0 text-[11px] font-semibold uppercase tracking-wider text-base-content/60">atau</div>

        <form method="POST" action="{{ route('demo.login') }}" class="mt-5">
            @csrf
            <button type="submit"
                    class="btn btn-block text-xs sm:text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
                Coba Demo Tanpa Daftar
            </button>
        </form>
        <p class="mt-3 text-center text-xs text-base-content/60">
            Masuk dengan data contoh. Tidak bisa di-ubah, aman untuk dicoba.
        </p>
    </div>
    @endif
</x-guest-layout>