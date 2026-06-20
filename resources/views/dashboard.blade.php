<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Ringkasan Kas
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-xl border border-teal-200 bg-teal-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-teal-700">Saldo Berjalan</p>
                    <p class="mt-1 text-2xl font-bold text-teal-900">Rp {{ number_format($summary['saldo'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-blue-700">Total Debet</p>
                    <p class="mt-1 text-2xl font-bold text-blue-900">Rp {{ number_format($summary['total_debet'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-rose-700">Total Kredit</p>
                    <p class="mt-1 text-2xl font-bold text-rose-900">Rp {{ number_format($summary['total_kredit'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Jumlah Transaksi</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($summary['total_transaksi'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Jumlah Nota</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($summary['total_nota'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Master Penyedia</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($summary['total_penyedia'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Perjalanan Dinas</p>
                    <p class="mt-1 text-2xl font-bold text-amber-900">{{ number_format($summary['total_perjalanan_dinas'], 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Perjalanan Dinas Terbaru</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-2 text-left">Nomor Surat</th>
                                <th class="px-3 py-2 text-left">Tujuan</th>
                                <th class="px-3 py-2 text-left">Tanggal</th>
                                <th class="px-3 py-2 text-right">Biaya</th>
                                <th class="px-3 py-2 text-center">Dokumen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($recentSuratTugas as $st)
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-2 font-medium text-slate-800">{{ $st->nomor_surat }}</td>
                                    <td class="px-3 py-2">{{ $st->tempat_tujuan }}</td>
                                    <td class="whitespace-nowrap px-3 py-2">{{ $st->tgl_berangkat->format('d-m-Y') }} s.d. {{ $st->tgl_kembali->format('d-m-Y') }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right text-slate-700">Rp {{ number_format($st->biaya_total, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-center">
                                        <a href="{{ route('cetak.pd.surat-tugas', $st) }}" class="text-teal-600 hover:underline">Surat Tugas</a>
                                        <span class="mx-1 text-slate-300">|</span>
                                        <a href="{{ route('cetak.pd.spd', $st) }}" class="text-blue-600 hover:underline">SPD</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-slate-400">Belum ada data perjalanan dinas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Transaksi Terbaru</h3>
                    <a href="{{ route('transaksi-kas.index') }}" class="inline-flex items-center rounded-lg bg-teal-600 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-700">
                        Buka Buku Kas
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-2 text-left">Tanggal</th>
                                <th class="px-3 py-2 text-left">Kegiatan</th>
                                <th class="px-3 py-2 text-left">Sumber</th>
                                <th class="px-3 py-2 text-right">Debet</th>
                                <th class="px-3 py-2 text-right">Kredit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($recentTransaksi as $trx)
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-2">{{ $trx->tanggal->format('d-m-Y') }}</td>
                                    <td class="px-3 py-2">{{ $trx->kegiatan }}</td>
                                    <td class="whitespace-nowrap px-3 py-2">{{ $trx->sumber->label() }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right text-teal-700">
                                        @if ($trx->debet > 0)
                                            Rp {{ number_format($trx->debet, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right text-rose-700">
                                        @if ($trx->kredit > 0)
                                            Rp {{ number_format($trx->kredit, 0, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-slate-400">Belum ada data transaksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
