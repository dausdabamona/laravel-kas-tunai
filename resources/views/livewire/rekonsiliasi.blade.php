<div class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-sm font-semibold text-slate-700">Rekonsiliasi Uang Muka</h3>
        <a href="{{ route('cetak.pajak.tanda-terima', $transaksi) }}" target="_blank"
            class="inline-flex min-h-[44px] items-center rounded-lg border border-teal-600 px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50">
            Cetak Tanda Terima
        </a>
    </div>

    {{-- Ikhtisar angka --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-lg bg-slate-50 p-3">
            <p class="text-xs text-slate-500">Uang Muka</p>
            <p class="text-lg font-bold text-slate-800">Rp {{ number_format($rekon['uang_muka'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
            <p class="text-xs text-slate-500">Total Nota</p>
            <p class="text-lg font-bold text-slate-800">Rp {{ number_format($rekon['total_nota'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
            <p class="text-xs text-slate-500">Pengembalian</p>
            <p class="text-lg font-bold text-slate-800">Rp {{ number_format($rekon['total_pengembalian'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
            <p class="text-xs text-slate-500">Tambahan</p>
            <p class="text-lg font-bold text-slate-800">Rp {{ number_format($rekon['total_tambahan'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Selisih + status --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-4">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Selisih (Uang Muka + Tambahan − Nota − Pengembalian)</p>
            <p class="text-2xl font-bold {{ $rekon['selisih'] === 0 ? 'text-teal-700' : ($rekon['selisih'] > 0 ? 'text-amber-700' : 'text-red-700') }}">
                Rp {{ number_format(abs($rekon['selisih']), 0, ',', '.') }}
            </p>
        </div>
        <span class="rounded-full px-3 py-1 text-sm font-medium {{ $rekon['status']->badgeClass() }}">
            {{ $rekon['status']->label() }}
        </span>
    </div>

    @if ($rekon['status'] === \App\Enums\StatusRekonsiliasi::Lebih)
        <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
            Pelaksana wajib mengembalikan sisa <strong>Rp {{ number_format($rekon['selisih'], 0, ',', '.') }}</strong> ke kas. Catat di bawah pada <em>Pengembalian Sisa</em>.
        </p>
    @elseif ($rekon['status'] === \App\Enums\StatusRekonsiliasi::Kurang)
        <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">
            Bendahara wajib menambah kekurangan <strong>Rp {{ number_format(abs($rekon['selisih']), 0, ',', '.') }}</strong>. Catat di bawah pada <em>Tambahan Kekurangan</em>.
        </p>
    @else
        <p class="rounded-lg bg-teal-50 px-3 py-2 text-sm text-teal-800">
            Kas nihil — pertanggungjawaban uang muka tuntas.
        </p>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

        {{-- Pengembalian Sisa --}}
        <div class="rounded-lg border border-slate-200 p-4">
            <div class="mb-2 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-700">Pengembalian Sisa (masuk kas)</h4>
                @if ($rekon['selisih'] > 0)
                    <button type="button" wire:click="isiOtomatis('pengembalian')" class="text-xs text-teal-600 hover:underline">Isi sebesar selisih</button>
                @endif
            </div>

            <form wire:submit="catatPengembalian" class="space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-xs text-slate-600">Tanggal</label>
                        <x-date-input model="tglPengembalian" :value="$tglPengembalian" />
                        @error('tglPengembalian') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-slate-600">Jumlah (Rp)</label>
                        <x-rupiah-input model="jumlahPengembalian" />
                        @error('jumlahPengembalian') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-600">Keterangan</label>
                    <input wire:model="ketPengembalian" type="text" placeholder="opsional" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                </div>
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    Catat Pengembalian
                </button>
            </form>

            @if ($daftarPengembalian->isNotEmpty())
                <ul class="mt-3 divide-y divide-slate-100 text-sm">
                    @foreach ($daftarPengembalian as $p)
                        <li class="py-2">
                            <div class="flex items-center justify-between">
                                <span>
                                    {{ $p->tanggal->translatedFormat('d M Y') }} —
                                    <strong>Rp {{ number_format($p->jumlah, 0, ',', '.') }}</strong>
                                    @if ($p->keterangan) <span class="text-slate-400">({{ $p->keterangan }})</span> @endif
                                </span>
                                <button wire:click="hapusPengembalian({{ $p->id }})" wire:confirm="Hapus pengembalian ini?" class="text-xs text-red-500 hover:underline">Hapus</button>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                                <a href="{{ route('cetak.pajak.tt-pengembalian', $p) }}" target="_blank" class="text-teal-600 hover:underline">Cetak Tanda Terima</a>
                                @foreach ($p->lampiran as $b)
                                    <a href="{{ $urlBukti($b) }}" target="_blank" class="rounded border border-slate-200 px-1.5 py-0.5 text-slate-500">{{ str_starts_with($b->mime, 'image/') ? 'Foto' : 'PDF' }}</a>
                                    <button wire:click="hapusBuktiRekon({{ $b->id }})" wire:confirm="Hapus bukti?" class="text-red-400">×</button>
                                @endforeach
                                <label class="cursor-pointer text-teal-600">+ Galeri
                                    <input type="file" class="hidden" accept="image/*,application/pdf" wire:model="buktiRekon" x-on:change.debounce.500ms="$wire.unggahBuktiPengembalian({{ $p->id }})" />
                                </label>
                                <label class="cursor-pointer text-teal-600">📷 Kamera
                                    <input type="file" class="hidden" accept="image/*" capture="environment" wire:model="buktiRekon" x-on:change.debounce.500ms="$wire.unggahBuktiPengembalian({{ $p->id }})" />
                                </label>
                                <button type="button" tabindex="0" title="Klik lalu Ctrl+V untuk tempel screenshot"
                                    x-on:paste="for (const it of ($event.clipboardData?.items || [])) { if (it.type.startsWith('image/')) { $event.preventDefault(); $wire.upload('buktiRekon', it.getAsFile(), () => $wire.unggahBuktiPengembalian({{ $p->id }})); break; } }"
                                    class="rounded border border-dashed border-slate-300 px-1.5 text-teal-600 focus:border-teal-500 focus:outline-none">📋 Tempel</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @error('buktiRekon') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @endif
        </div>

        {{-- Tambahan Kekurangan --}}
        <div class="rounded-lg border border-slate-200 p-4">
            <div class="mb-2 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-700">Tambahan Kekurangan (keluar kas)</h4>
                @if ($rekon['selisih'] < 0)
                    <button type="button" wire:click="isiOtomatis('tambahan')" class="text-xs text-teal-600 hover:underline">Isi sebesar kekurangan</button>
                @endif
            </div>

            <form wire:submit="catatTambahan" class="space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-xs text-slate-600">Tanggal</label>
                        <x-date-input model="tglTambahan" :value="$tglTambahan" />
                        @error('tglTambahan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-slate-600">Jumlah (Rp)</label>
                        <x-rupiah-input model="jumlahTambahan" />
                        @error('jumlahTambahan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-600">Keterangan</label>
                    <input wire:model="ketTambahan" type="text" placeholder="opsional" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                </div>
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                    Catat Tambahan
                </button>
            </form>

            @if ($daftarTambahan->isNotEmpty())
                <ul class="mt-3 divide-y divide-slate-100 text-sm">
                    @foreach ($daftarTambahan as $t)
                        <li class="py-2">
                            <div class="flex items-center justify-between">
                                <span>
                                    {{ $t->tanggal->translatedFormat('d M Y') }} —
                                    <strong>Rp {{ number_format($t->jumlah, 0, ',', '.') }}</strong>
                                    @if ($t->keterangan) <span class="text-slate-400">({{ $t->keterangan }})</span> @endif
                                </span>
                                <button wire:click="hapusTambahan({{ $t->id }})" wire:confirm="Hapus tambahan ini?" class="text-xs text-red-500 hover:underline">Hapus</button>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                                <a href="{{ route('cetak.pajak.tt-tambahan', $t) }}" target="_blank" class="text-teal-600 hover:underline">Cetak Tanda Terima</a>
                                @foreach ($t->lampiran as $b)
                                    <a href="{{ $urlBukti($b) }}" target="_blank" class="rounded border border-slate-200 px-1.5 py-0.5 text-slate-500">{{ str_starts_with($b->mime, 'image/') ? 'Foto' : 'PDF' }}</a>
                                    <button wire:click="hapusBuktiRekon({{ $b->id }})" wire:confirm="Hapus bukti?" class="text-red-400">×</button>
                                @endforeach
                                <label class="cursor-pointer text-teal-600">+ Galeri
                                    <input type="file" class="hidden" accept="image/*,application/pdf" wire:model="buktiRekon" x-on:change.debounce.500ms="$wire.unggahBuktiTambahan({{ $t->id }})" />
                                </label>
                                <label class="cursor-pointer text-teal-600">📷 Kamera
                                    <input type="file" class="hidden" accept="image/*" capture="environment" wire:model="buktiRekon" x-on:change.debounce.500ms="$wire.unggahBuktiTambahan({{ $t->id }})" />
                                </label>
                                <button type="button" tabindex="0" title="Klik lalu Ctrl+V untuk tempel screenshot"
                                    x-on:paste="for (const it of ($event.clipboardData?.items || [])) { if (it.type.startsWith('image/')) { $event.preventDefault(); $wire.upload('buktiRekon', it.getAsFile(), () => $wire.unggahBuktiTambahan({{ $t->id }})); break; } }"
                                    class="rounded border border-dashed border-slate-300 px-1.5 text-teal-600 focus:border-teal-500 focus:outline-none">📋 Tempel</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>
</div>
