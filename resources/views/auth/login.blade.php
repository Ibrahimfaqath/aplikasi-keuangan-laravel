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
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 dark:border-navy-700 dark:bg-navy-800 text-blue-600 dark:text-navy-300 shadow-sm focus:ring-blue-500" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-white/80">{{ __('Ingat saya') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-white/70 hover:text-gray-900 dark:hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-navy-950 focus:ring-blue-500" href="{{ route('password.request') }}">
                    {{ __('Lupa kata sandi?') }}
                </a>
            @endif
        </div>

        <!-- Action Buttons: Link Register & Tombol Login -->
        <div class="flex items-center justify-between mt-6 pt-5 border-t border-gray-100 dark:border-navy-800">
            <a class="text-sm font-medium text-blue-600 dark:text-navy-300 hover:text-blue-800 dark:hover:text-navy-200 underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-navy-950 focus:ring-blue-500" href="{{ route('register') }}">
                Belum punya akun? Daftar
            </a>

            <x-primary-button class="ms-3">
                {{ __('Masuk') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
