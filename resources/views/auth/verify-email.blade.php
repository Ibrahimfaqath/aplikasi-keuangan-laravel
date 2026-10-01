<x-guest-layout
    :title="'Verifikasi Email'"
    :subtitle="'Terima kasih sudah mendaftar! Verifikasi email untuk mengaktifkan akunmu.'"
    >
    <x-slot:icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
    </x-slot:icon>

    <p class="text-sm text-base-content/60">
        Kami sudah mengirim tautan verifikasi ke emailmu. Belum menerima email? Kirim ulang di bawah.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert gap-2 border border-success/30 bg-success/10 px-3.5 py-3 text-sm font-medium text-base-content">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="min-w-0">{{ __('Tautan verifikasi baru telah dikirim ke email kamu.') }}</div>
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
            <button type="submit" class="btn btn-ghost btn-sm font-semibold text-base-content/60 hover:text-base-content focus:outline-none focus:ring-2 focus:ring-base-content">
                {{ __('Keluar') }}
            </button>
        </form>
    </div>
</x-guest-layout>