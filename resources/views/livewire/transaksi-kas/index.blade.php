<div class="space-y-6">

    {{-- Rekap Saldo --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-teal-200 bg-teal-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-teal-600">Saldo Tunai</p>
            <p class="mt-1 text-2xl font-bold text-teal-800">
                Rp {{ number_format($rekap['tunai'], 0, ',', '.') }}
            </p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-blue-600">Saldo Bank</p>
            <p class="mt-1 text-2xl font-bold text-blue-800">
                Rp {{ number_format($rekap['bank'], 0, ',', '.') }}
            </p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-600">Total</p>
            <p class="mt-1 text-2xl font-bold text-slate-800">
                Rp {{ number_format($rekap['total'], 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <input
                wire:model.live.debounce.300ms="cari"
                type="search"
                placeholder="Cari kegiatan…"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
            />
        </div>

        <select wire:model.live="filterSumber" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">— Semua Sumber —</option>
            @foreach ($sumberPilihan as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterStatusSpj" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">— Semua Status —</option>
            @foreach ($statusSpjPilihan as $st)
                <option value="{{ $st->value }}">{{ $st->label() }}</option>
            @endforeach
        </select>

        <div class="w-40"><x-date-input model="filterDariTanggal" :value="$filterDariTanggal" live /></div>
        <div class="w-40"><x-date-input model="filterSampaiTanggal" :value="$filterSampaiTanggal" live /></div>

        <a href="{{ route('transaksi-kas.create') }}" class="inline-flex min-h-[44px] items-center gap-1 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
            + Tambah
        </a>
    </div>

    {{-- Tabel --}}
    <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">No</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Kegiatan</th>
                    <th class="px-4 py-3 text-left">Sumber</th>
                    <th class="px-4 py-3 text-right">Debet</th>
                    <th class="px-4 py-3 text-right">Kredit</th>
                    <th class="px-4 py-3 text-center">SPJ</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($this->transaksi as $trx)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-500">{{ $trx->no }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $trx->tanggal->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $trx->kegiatan }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $trx->sumber->label() }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-teal-700">
                            @if ($trx->debet > 0) Rp {{ number_format($trx->debet, 0, ',', '.') }} @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-red-600">
                            @if ($trx->kredit > 0) Rp {{ number_format($trx->kredit, 0, ',', '.') }} @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $trx->status_spj->badgeClass() }}">
                                {{ $trx->status_spj->label() }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <a href="{{ route('transaksi-kas.edit', $trx) }}"
                               class="mr-2 text-teal-600 hover:underline">Ubah</a>
                            <button
                                wire:click="hapus({{ $trx->id }})"
                                wire:confirm="Yakin hapus transaksi ini?"
                                class="text-red-500 hover:underline"
                                min-height="44px">
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">Tidak ada transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->transaksi->links() }}</div>

</div>
