<div class="space-y-6">

    {{-- Filter rentang --}}
    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Dari Tanggal</label>
            <x-date-input model="dari" :value="$dari" live />
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Sampai Tanggal</label>
            <x-date-input model="sampai" :value="$sampai" live />
        </div>
        <a href="{{ route('laporan.lpj', ['dari' => $dari, 'sampai' => $sampai]) }}" target="_blank"
            class="inline-flex min-h-[44px] items-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
            Cetak LPJ
        </a>
    </div>

    {{-- Rekap per sumber --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($sumberPilihan as $s)
            @php $r = $rekap[$s->value] ?? ['saldo_awal' => 0, 'debet' => 0, 'kredit' => 0, 'saldo_akhir' => 0]; @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">{{ $s->label() }}</h3>
                    <a href="{{ route('laporan.bku', ['sumber' => $s->value, 'dari' => $dari, 'sampai' => $sampai]) }}" target="_blank"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-teal-600 px-3 py-1.5 text-xs font-medium text-teal-700 hover:bg-teal-50">
                        Cetak BKU
                    </a>
                </div>
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Saldo Awal</dt><dd>Rp {{ number_format($r['saldo_awal'], 0, ',', '.') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Penerimaan</dt><dd class="text-teal-700">Rp {{ number_format($r['debet'], 0, ',', '.') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Pengeluaran</dt><dd class="text-red-600">Rp {{ number_format($r['kredit'], 0, ',', '.') }}</dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-1 font-semibold"><dt>Saldo Akhir</dt><dd>Rp {{ number_format($r['saldo_akhir'], 0, ',', '.') }}</dd></div>
                </dl>
            </div>
        @endforeach
    </div>

</div>
