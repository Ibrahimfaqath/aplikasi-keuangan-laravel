<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-5">
            <x-input-label for="password" :value="__('Kata Sandi')" />

            <x-text-input id="password" class="block mt-2 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between mt-5">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-neutral-300 dark:border-[#333333] dark:bg-[#262626] text-neutral-900 shadow-sm focus:ring-neutral-900 dark:focus:ring-neutral-100" name="remember">
                <span class="ms-2 text-sm text-neutral-600 dark:text-neutral-300">{{ __('Ingat saya') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="underline text-sm text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-[#171717] focus:ring-neutral-900" href="{{ route('password.request') }}">
                    {{ __('Lupa kata sandi?') }}
                </a>
            @endif
        </div>

        <!-- Action Buttons: Link Register & Tombol Login -->
        <div class="flex items-center justify-between mt-6 pt-5 border-t border-neutral-200 dark:border-[#333333]">
            <a class="text-sm font-medium text-neutral-900 dark:text-neutral-100 hover:text-black dark:hover:text-white underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-[#171717] focus:ring-neutral-900" href="{{ route('register') }}">
                Belum punya akun? Daftar
            </a>

            <x-primary-button class="ms-3">
                {{ __('Masuk') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
