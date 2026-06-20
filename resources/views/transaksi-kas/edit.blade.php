<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Ubah Transaksi
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-teal-200 bg-teal-50 p-3 text-sm text-teal-800">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="p-6">
                    <livewire:transaksi-kas.form :transaksi-id="$transaksi->id" />
                </div>
            </div>

            {{-- Panel aksi hanya untuk yang berhak mengubah (hindari tombol yang 403). --}}
            @can('update', $transaksi)
                @if ($transaksi->jenis === \App\Enums\JenisTransaksi::Belanja)
                    <livewire:nota.kelola :transaksi="$transaksi" :key="'nota-'.$transaksi->id.'-'.$transaksi->updated_at?->timestamp" />

                    <livewire:rekonsiliasi :transaksi="$transaksi" :key="'rekon-'.$transaksi->id.'-'.$transaksi->updated_at?->timestamp" />
                @endif

                @if (in_array($transaksi->jenis, [\App\Enums\JenisTransaksi::PdPokok, \App\Enums\JenisTransaksi::PdBendahara], true))
                    @php
                        $st = \App\Models\SuratTugas::where('transaksi_id', $transaksi->id)->first()
                            ?? ($transaksi->ref_group
                                ? \App\Models\SuratTugas::whereHas('transaksi', fn ($q) => $q->where('ref_group', $transaksi->ref_group))->first()
                                : null);
                    @endphp

                    @if ($st)
                        <livewire:perjalanan.kelola-rincian :surat-tugas="$st" :key="'rincian-pd-'.$st->id" />
                    @endif
                @endif

                {{-- Bukti / dokumen pendukung — ditautkan langsung ke SETIAP transaksi --}}
                <livewire:bukti-perjalanan :transaksi="$transaksi" :key="'bukti-'.$transaksi->id.'-'.$transaksi->updated_at?->timestamp" />
            @endcan
        </div>
    </div>
</x-app-layout>
