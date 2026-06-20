{{-- Strip saldo kas — KHUSUS Bendahara, tampil di semua halaman. --}}
@auth
    @if (auth()->user()->role === \App\Enums\Role::Bendahara)
        @php $rekap = app(\App\Services\SaldoService::class)->rekap(); @endphp
        <div class="border-b border-teal-800 bg-teal-700 text-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-6 gap-y-1 px-4 py-2 text-sm sm:px-6 lg:px-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-teal-100">Saldo Kas</span>
                <span>Total: <strong>Rp {{ number_format($rekap['total'], 0, ',', '.') }}</strong></span>
                <span class="text-teal-100">|</span>
                <span>Tunai: <strong>Rp {{ number_format($rekap['tunai'], 0, ',', '.') }}</strong></span>
                <span class="text-teal-100">|</span>
                <span>Bank: <strong>Rp {{ number_format($rekap['bank'], 0, ',', '.') }}</strong></span>
            </div>
        </div>
    @endif
@endauth
