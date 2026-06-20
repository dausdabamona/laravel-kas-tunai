<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Perjalanan Dinas
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 shadow-sm">
                Dokumen yang sudah tersedia dan terpasang saat ini: Surat Tugas, SPD (dalam kota/luar kota memakai template sesuai jenis), Rincian Biaya, Pengesahan, Pengeluaran Riil, dan unggah Bukti Perjalanan pada halaman ubah transaksi. Template SPPR dan Tanda Terima khusus belum ada di kode saat ini.
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Nomor Surat</th>
                            <th class="px-4 py-3 text-left">Kegiatan</th>
                            <th class="px-4 py-3 text-left">Tujuan</th>
                            <th class="px-4 py-3 text-left">Jenis</th>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-right">Biaya</th>
                            <th class="px-4 py-3 text-left">Dokumen</th>
                            <th class="px-4 py-3 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($suratTugas as $st)
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $st->nomor_surat }}</td>
                                <td class="px-4 py-3">{{ $st->transaksi?->kegiatan ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $st->tempat_tujuan }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{ $st->jenis === 'dalam_kota' ? 'Dalam Kota' : 'Luar Kota' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $st->tgl_berangkat->format('d-m-Y') }} s.d. {{ $st->tgl_kembali->format('d-m-Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format($st->biaya_total, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('cetak.pd.surat-tugas', $st) }}" target="_blank" class="text-teal-600 hover:underline">Surat Tugas</a>
                                        <a href="{{ route('cetak.pd.spd', $st) }}" target="_blank" class="text-blue-600 hover:underline">{{ $st->jenis === 'dalam_kota' ? 'SPD Dalam Kota' : 'SPD Luar Kota' }}</a>
                                        <a href="{{ route('cetak.pd.rincian', $st) }}" target="_blank" class="text-slate-700 hover:underline">Rincian</a>
                                        <a href="{{ route('cetak.pd.pengesahan', $st) }}" target="_blank" class="text-slate-700 hover:underline">Pengesahan</a>
                                        <a href="{{ route('cetak.pd.pengeluaran-riil', $st) }}" target="_blank" class="text-slate-700 hover:underline">Pengeluaran Riil</a>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($st->transaksi)
                                        <a href="{{ route('transaksi-kas.edit', $st->transaksi) }}" class="text-teal-600 hover:underline">Kelola Transaksi</a>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-400">Belum ada data perjalanan dinas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $suratTugas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
