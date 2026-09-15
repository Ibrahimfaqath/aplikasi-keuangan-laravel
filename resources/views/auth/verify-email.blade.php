<x-guest-layout
    :title="'Verifikasi Email'"
    :subtitle="'Terima kasih sudah mendaftar! Verifikasi email untuk mengaktifkan akunmu.'"
    >
    <x-slot:icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
    </x-slot:icon>

    <p class="text-sm text-neutral-600 dark:text-neutral-300">
        Kami sudah mengirim tautan verifikasi ke emailmu. Belum menerima email? Kirim ulang di bawah.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 px-3.5 py-3 text-sm font-medium text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
            {{ __('Tautan verifikasi baru telah dikirim ke email kamu.') }}
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                {{ __('Kirim Ulang Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-semibold text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-100 rounded-md focus:outline-none focus:ring-2 focus:ring-neutral-900">
                {{ __('Keluar') }}
            </button>
        </form>
    </div>
</x-guest-layout>