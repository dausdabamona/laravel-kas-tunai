<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Ubah Transaksi
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="p-6">
                    <livewire:transaksi-kas.form :transaksi-id="$transaksi->id" />
                </div>
            </div>

            @if ($transaksi->jenis === \App\Enums\JenisTransaksi::Belanja)
                <livewire:nota.kelola :transaksi="$transaksi" :key="'nota-'.$transaksi->id.'-'.$transaksi->updated_at?->timestamp" />
            @endif

            @if (in_array($transaksi->jenis, [\App\Enums\JenisTransaksi::PdPokok, \App\Enums\JenisTransaksi::PdBendahara], true))
                <livewire:bukti-perjalanan :transaksi="$transaksi" :key="'bukti-pd-'.$transaksi->id.'-'.$transaksi->updated_at?->timestamp" />
            @endif
        </div>
    </div>
</x-app-layout>
