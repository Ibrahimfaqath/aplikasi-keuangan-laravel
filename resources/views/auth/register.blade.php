<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nama')" />
            <x-text-input id="name" class="block mt-2 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Nama lengkap kamu" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-5">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-5">
            <x-input-label for="password" :value="__('Kata Sandi')" />

            <x-text-input id="password" class="block mt-2 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" placeholder="Minimal 8 karakter" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-5">
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Kata Sandi')" />

            <x-text-input id="password_confirmation" class="block mt-2 w-full"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password" placeholder="Ulangi kata sandi" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Action Buttons: Link Login & Tombol Register -->
        <div class="flex items-center justify-between mt-6 pt-5 border-t border-neutral-200 dark:border-[#333333]">
            <a class="text-sm font-medium text-neutral-900 dark:text-neutral-100 hover:text-black dark:hover:text-white underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-[#171717] focus:ring-neutral-900" href="{{ route('login') }}">
                Sudah punya akun? Login
            </a>

            <x-primary-button class="ms-4">
                {{ __('Daftar') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
