<x-guest-layout
    :title="'Atur Ulang Kata Sandi'"
    :subtitle="'Buat kata sandi baru untuk akunmu.'"
    >
    <x-slot:icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5.25a1.875 1.875 0 00-1.875 1.875v.375h3.75v-.375A1.875 1.875 0 0012 5.25zm-3.375 2.25v-.375a3.375 3.375 0 016.75 0v.375h.375A1.875 1.875 0 0117.625 9.375v6A1.875 1.875 0 0115.75 17.25h-7.5a1.875 1.875 0 01-1.875-1.875v-6A1.875 1.875 0 018.25 7.5h.375z"/></svg>
    </x-slot:icon>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-2 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Kata Sandi Baru')" />
            <x-text-input id="password" class="mt-2 w-full" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Kata Sandi')" />
            <x-text-input id="password_confirmation" class="mt-2 w-full" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Simpan Kata Sandi Baru') }}
        </x-primary-button>
    </form>
</x-guest-layout>