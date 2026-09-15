<x-guest-layout
    :title="'Masuk ke DompetKu'"
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
</x-guest-layout>