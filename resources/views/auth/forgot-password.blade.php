<x-guest-layout
    :title="'Lupa Kata Sandi'"
    :subtitle="'Masukkan emailmu, kami akan kirim tautan untuk mengatur ulang kata sandi.'"
    >
    <x-slot:icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2.25c-2.9 0-5.25 2.35-5.25 5.25v3.75M12 2.25c2.9 0 5.25 2.35 5.25 5.25v3.75m-10.5 0h10.5M18.75 11.25A.75.75 0 0119.5 12v6.75A2.25 2.25 0 0117.25 21H6.75A2.25 2.25 0 014.5 18.75V12a.75.75 0 01.75-.75h13.5z"/></svg>
    </x-slot:icon>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-2 w-full" type="email" name="email" :value="old('email')" required autofocus placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 space-y-4">
            <x-primary-button class="w-full">
                {{ __('Kirim Tautan Reset') }}
            </x-primary-button>

            <p class="text-center text-sm text-neutral-500 dark:text-neutral-400">
                Sudah ingat? <a class="font-semibold text-neutral-900 hover:underline dark:text-neutral-100" href="{{ route('login') }}">Kembali ke Masuk</a>
            </p>
        </div>
    </form>
</x-guest-layout>