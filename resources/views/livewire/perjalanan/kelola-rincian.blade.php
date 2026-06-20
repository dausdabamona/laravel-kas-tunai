<div class="space-y-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

    <h3 class="text-sm font-semibold text-slate-700">Rincian Biaya Perjalanan Dinas (per pegawai)</h3>

    {{-- Form tambah/edit komponen --}}
    <form wire:submit="simpan" class="space-y-3 rounded-lg border border-slate-200 p-4"
        x-data="{
            qty: @entangle('qty'),
            harga: @entangle('harga_satuan'),
            fmt(n) { n = parseInt(n) || 0; return n ? new Intl.NumberFormat('id-ID').format(n) : ''; },
            parse(v) { return (String(v).replace(/[^0-9]/g, '') * 1) || 0; }
        }">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Pegawai</label>
                <select wire:model="pegawaiIndex" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @foreach ($daftarPegawai as $i => $p)
                        <option value="{{ $i }}">{{ $p['nama'] ?? ('Pegawai '.($i + 1)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Komponen</label>
                <select wire:model.live="komponen" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @foreach ($komponenPilihan as $k)
                        <option value="{{ $k->value }}">{{ $k->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Uraian</label>
                <input wire:model="uraian" type="text" placeholder="mis. Ambon - Sorong / 4 malam" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Qty (hari/malam/unit)</label>
                <input type="text" inputmode="numeric" :value="fmt(qty)" @input="qty = parse($event.target.value)"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                <p class="mt-1 text-xs text-slate-400">Default {{ $suratTugas->lama_hari }} hari (durasi surat tugas) — bisa diubah.</p>
                @error('qty') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Harga Satuan (Rp)</label>
                <input type="text" inputmode="numeric" :value="fmt(harga)" @input="harga = parse($event.target.value)"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                @error('harga_satuan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Jumlah (Rp)</label>
                <div class="flex h-[38px] items-center rounded-lg bg-slate-50 px-3 text-sm font-semibold text-teal-800">
                    Rp <span class="ml-1" x-text="fmt(qty * harga) || '0'"></span>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Pembayar</label>
                <select wire:model="pembayar" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @foreach ($pembayarPilihan as $pb)
                        <option value="{{ $pb->value }}">{{ $pb->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Metode (Kas)</label>
                <select wire:model="metode" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @foreach ($metodePilihan as $m)
                        <option value="{{ $m->value }}">{{ $m->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Keterangan</label>
                <input wire:model="keterangan" type="text" placeholder="mis. Lumpsum" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">
                {{ $editingId ? 'Perbarui' : 'Tambah' }} Komponen
            </button>
            @if ($editingId)
                <button type="button" wire:click="batal" class="text-sm text-slate-500 hover:underline">Batal</button>
            @endif
        </div>
    </form>

    {{-- Daftar per pegawai --}}
    @forelse ($daftarPegawai as $i => $p)
        @php $items = $rincianPerPegawai[$i] ?? collect(); @endphp
        <div class="rounded-lg border border-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-4 py-2">
                <span class="text-sm font-semibold text-slate-700">{{ $p['nama'] ?? ('Pegawai '.($i + 1)) }}</span>
                <div class="flex flex-wrap gap-3 text-xs">
                    <a href="{{ route('cetak.pd.rincian', ['suratTugas' => $suratTugas->id, 'pegawai' => $i]) }}" target="_blank" class="text-teal-600 hover:underline">Cetak Rincian Biaya</a>
                    <a href="{{ route('cetak.pd.kuitansi', ['suratTugas' => $suratTugas->id, 'pegawai' => $i]) }}" target="_blank" class="text-blue-600 hover:underline">Cetak Kuitansi</a>
                </div>
            </div>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Komponen</th>
                        <th class="px-3 py-2 text-right">Qty × Harga</th>
                        <th class="px-3 py-2 text-right">Jumlah</th>
                        <th class="px-3 py-2 text-left">Pembayar / Kas</th>
                        <th class="px-3 py-2 text-left">Bukti</th>
                        <th class="px-3 py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($items as $r)
                        <tr>
                            <td class="px-3 py-2">
                                <div class="font-medium text-slate-800">{{ $r->komponen->label() }}</div>
                                @if ($r->uraian) <div class="text-xs text-slate-400">{{ $r->uraian }}</div> @endif
                            </td>
                            <td class="px-3 py-2 text-right text-slate-500">{{ $r->qty }} × {{ number_format($r->harga_satuan, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right font-medium">Rp {{ number_format($r->nominal, 0, ',', '.') }}</td>
                            <td class="px-3 py-2">
                                <span class="text-xs">{{ $r->pembayar->label() }}</span>
                                <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $r->metode->label() }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap items-center gap-1">
                                    @foreach ($r->lampiran as $b)
                                        @if (str_starts_with($b->mime, 'image/'))
                                            <a href="{{ $urlBukti($b) }}" target="_blank" class="inline-block h-9 w-9 overflow-hidden rounded border border-slate-200">
                                                <img src="{{ $urlBukti($b) }}" class="h-full w-full object-cover" alt="bukti" />
                                            </a>
                                        @else
                                            <a href="{{ $urlBukti($b) }}" target="_blank" class="rounded border border-slate-200 px-1.5 py-0.5 text-xs text-slate-500">PDF</a>
                                        @endif
                                        <button wire:click="hapusBukti({{ $b->id }})" wire:confirm="Hapus bukti ini?" class="text-xs text-red-400 hover:text-red-600">×</button>
                                    @endforeach
                                    <label class="cursor-pointer text-teal-600">+ Galeri
                                        <input type="file" class="hidden" accept="image/*,application/pdf"
                                            wire:model="bukti" x-on:change.debounce.500ms="$wire.unggahBukti({{ $r->id }})" />
                                    </label>
                                    <label class="cursor-pointer text-teal-600">📷 Kamera
                                        <input type="file" class="hidden" accept="image/*" capture="environment"
                                            wire:model="bukti" x-on:change.debounce.500ms="$wire.unggahBukti({{ $r->id }})" />
                                    </label>
                                    <button type="button" tabindex="0" title="Klik lalu Ctrl+V untuk tempel screenshot"
                                        x-on:paste="for (const it of ($event.clipboardData?.items || [])) { if (it.type.startsWith('image/')) { $event.preventDefault(); $wire.upload('bukti', it.getAsFile(), () => $wire.unggahBukti({{ $r->id }})); break; } }"
                                        class="rounded border border-dashed border-slate-300 px-1.5 text-teal-600 focus:border-teal-500 focus:outline-none">📋 Tempel</button>
                                </div>
                                @error('bukti') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-3 py-2 text-center">
                                <button wire:click="edit({{ $r->id }})" class="mr-2 text-teal-600 hover:underline">Ubah</button>
                                <button wire:click="hapus({{ $r->id }})" wire:confirm="Hapus komponen ini?" class="text-red-500 hover:underline">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-3 text-center text-slate-400">Belum ada rincian untuk pegawai ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @empty
        <p class="text-sm text-slate-400">Surat tugas ini belum punya data pegawai.</p>
    @endforelse

    {{-- Uang muka (tunai/transfer) — mengurangi saldo kas & jadi "telah dibayar" --}}
    <div class="rounded-lg border border-slate-200 p-4">
        <h4 class="mb-2 text-sm font-semibold text-slate-700">Uang Muka (Tunai / Transfer)</h4>

        <form wire:submit="catatUangMuka" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs text-slate-600">Pegawai</label>
                <select wire:model="umPegawaiIndex" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @foreach ($daftarPegawai as $i => $p)
                        <option value="{{ $i }}">{{ $p['nama'] ?? ('Pegawai '.($i + 1)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-600">Tanggal</label>
                <x-date-input model="umTanggal" :value="$umTanggal" />
                @error('umTanggal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-600">Jumlah (Rp)</label>
                <x-rupiah-input model="umJumlah" />
                @error('umJumlah') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-600">Metode</label>
                <select wire:model="umMetode" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    <option value="tunai">Tunai</option>
                    <option value="bank">Transfer (Bank)</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    Catat Uang Muka
                </button>
            </div>
        </form>

        @if ($daftarUangMuka->isNotEmpty())
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @foreach ($daftarUangMuka as $um)
                    <li class="flex items-center justify-between py-2">
                        <span>
                            {{ $um->tanggal->translatedFormat('d M Y') }} —
                            <strong>{{ $daftarPegawai[$um->pegawai_index]['nama'] ?? ('Pegawai '.($um->pegawai_index + 1)) }}</strong> —
                            Rp {{ number_format($um->jumlah, 0, ',', '.') }}
                            <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $um->metode === \App\Enums\Sumber::Tunai ? 'Tunai' : 'Transfer' }}</span>
                        </span>
                        <button wire:click="hapusUangMuka({{ $um->id }})" wire:confirm="Hapus uang muka ini?" class="text-xs text-red-500 hover:underline">Hapus</button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Rekap keseluruhan --}}
    <div class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div class="flex items-center justify-between text-sm">
            <span class="font-semibold text-slate-700">Jumlah Seluruh Rincian</span>
            <span class="text-lg font-bold text-teal-800">Rp {{ number_format($ringkas['total'], 0, ',', '.') }}</span>
        </div>
        @foreach ($ringkas['bucket'] as $key => $jumlah)
            @php [$pb, $mt] = explode('|', $key); @endphp
            <div class="flex items-center justify-between text-sm text-slate-600">
                <span>{{ \App\Enums\PembayarPd::from($pb)->label() }} — {{ \App\Enums\Sumber::from($mt)->label() }}</span>
                <span>Rp {{ number_format($jumlah, 0, ',', '.') }}</span>
            </div>
        @endforeach
        <div class="mt-1 flex items-center justify-between border-t border-slate-200 pt-2 text-sm">
            <span class="text-slate-600">Telah dibayar (uang muka)</span>
            <span class="font-medium">Rp {{ number_format($ringkas['telah_dibayar'], 0, ',', '.') }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
            <span class="font-semibold {{ $ringkas['sisa_kurang'] > 0 ? 'text-amber-700' : 'text-teal-700' }}">Sisa kurang dibayar</span>
            <span class="font-bold {{ $ringkas['sisa_kurang'] > 0 ? 'text-amber-700' : 'text-teal-700' }}">Rp {{ number_format($ringkas['sisa_kurang'], 0, ',', '.') }}</span>
        </div>
    </div>
</div>
