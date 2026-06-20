<form wire:submit="simpan" class="space-y-6"
    x-data="{
        kredit: @entangle('kredit'),
        nilaiSpby: @entangle('nilai_spby'),
        notaTotal: {{ $previewNotaTotal }},
        pengembalian: {{ $previewPengembalian }},
        tambahan: {{ $previewTambahan }},
        get target() { return (this.nilaiSpby * 1) > 0 ? (this.nilaiSpby * 1) : (this.kredit * 1); },
        get lunas() { return this.target > 0 && (this.notaTotal + this.pengembalian) >= this.target; },
        get selisih() { return (this.kredit * 1) + this.tambahan - this.notaTotal - this.pengembalian; },
        fmt(n) { return new Intl.NumberFormat('id-ID').format(Math.round(Math.abs(n) || 0)); }
    }">

    @if ($tersimpan)
        <div class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-2 text-sm text-teal-800">
            ✓ Transaksi tersimpan. Panel Rekonsiliasi &amp; dokumen sudah memakai nilai terbaru.
        </div>
    @endif

    @unless ($bolehUbah)
        <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>Hanya-baca.</strong> Anda tidak berhak mengubah transaksi ini — karena pemisahan tugas
            (PPK/Pimpinan tidak mengubah; Operator hanya transaksi berstatus <em>Belum SPJ</em>), status sudah final,
            atau periode terkunci. Masuk sebagai <strong>Bendahara</strong> untuk mengubah.
        </div>
    @endunless

    <fieldset @disabled(! $bolehUbah) class="m-0 space-y-6 border-0 p-0 disabled:opacity-60">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

        {{-- Tanggal --}}
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal <span class="text-red-500">*</span></label>
            <x-date-input model="tanggal" :value="$tanggal" />
            @error('tanggal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Penanggung Jawab --}}
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Penanggung Jawab</label>
            <input wire:model="penjab" type="text" placeholder="Nama penanggung jawab"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
        </div>

        {{-- Kegiatan --}}
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Uraian Kegiatan <span class="text-red-500">*</span></label>
            <input wire:model="kegiatan" type="text" placeholder="Deskripsi kegiatan / belanja"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('kegiatan') border-red-400 @enderror" />
            @error('kegiatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Keterangan --}}
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Keterangan</label>
            <textarea wire:model="keterangan" rows="2" placeholder="Catatan tambahan / jejak asal-usul (opsional)"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('keterangan') border-red-400 @enderror"></textarea>
            @error('keterangan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Sumber --}}
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Sumber Dana <span class="text-red-500">*</span></label>
            <select wire:model="sumber"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('sumber') border-red-400 @enderror">
                <option value="">— Pilih —</option>
                @foreach ($sumberPilihan as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
            @error('sumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Jenis --}}
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Jenis Transaksi <span class="text-red-500">*</span></label>
            <select wire:model.live="jenis"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('jenis') border-red-400 @enderror">
                <option value="">— Pilih —</option>
                @foreach ($jenisPilihan as $j)
                    <option value="{{ $j->value }}">{{ $j->label() }}</option>
                @endforeach
            </select>
            @error('jenis') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Nominal: HANYA satu kolom (debet/kredit) sesuai jenis — cegah salah input --}}
        @if ($arahDebet)
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Debet / Uang Masuk (Rp) <span class="text-red-500">*</span></label>
                <x-rupiah-input model="debet" />
                <p class="mt-1 text-xs text-slate-400">Jenis "{{ \App\Enums\JenisTransaksi::tryFrom($jenis)?->label() }}" = penerimaan (debet).</p>
                @error('debet') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('kredit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        @else
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kredit / Uang Keluar (Rp) <span class="text-red-500">*</span></label>
                <x-rupiah-input model="kredit" />
                <p class="mt-1 text-xs text-slate-400">Jenis "{{ \App\Enums\JenisTransaksi::tryFrom($jenis)?->label() ?: 'belanja/keluar' }}" = pengeluaran (kredit).</p>
                @error('kredit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('debet') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        {{-- Status SPJ (otomatis — dihitung dari nota, tidak dapat diubah manual) --}}
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Status SPJ <span class="text-xs font-normal text-slate-400">(otomatis)</span></label>
            <div class="flex min-h-[42px] items-center">
                @php $statusObj = \App\Enums\StatusSpj::tryFrom($status_spj) ?? \App\Enums\StatusSpj::Belum; @endphp
                <span class="rounded-full px-3 py-1 text-sm font-medium {{ $statusObj->badgeClass() }}"
                    :class="lunas ? 'bg-teal-100 text-teal-800' : 'bg-amber-100 text-amber-800'"
                    x-text="lunas ? 'Lunas SPJ' : 'Belum SPJ'">{{ $statusObj->label() }}</span>
            </div>
            <input type="hidden" wire:model="status_spj" />
            <p class="mt-1 text-xs text-slate-500">Dihitung otomatis (live) dari Kredit & rincian nota. Klik Simpan untuk menyimpan.</p>
        </div>

    </div>

    {{-- Detail SPBY (opsional) --}}
    <details class="rounded-lg border border-slate-200 p-4">
        <summary class="cursor-pointer text-sm font-medium text-slate-600">Data SPBY (opsional)</summary>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">No. SPBY</label>
                <input wire:model="no_spby" type="text" class="w-full rounded-lg border-slate-300 text-sm shadow-sm" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Tanggal SPBY</label>
                <x-date-input model="tgl_spby" :value="$tgl_spby" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Nilai SPBY (Rp)</label>
                <x-rupiah-input model="nilai_spby" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Uang Diserahkan (Rp)</label>
                <x-rupiah-input model="uang_diserahkan" />
            </div>
        </div>
    </details>

    </fieldset>

    @if ($previewBelanja)
        {{-- Pratinjau perhitungan LIVE — berubah seketika saat Kredit/Nilai SPBY diubah,
             baru tersimpan setelah klik Simpan. --}}
        <div class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pratinjau Perhitungan (live — belum disimpan)</p>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <div><span class="text-slate-500">Uang Muka (Kredit)</span><br><strong class="text-slate-800">Rp <span x-text="fmt(kredit)"></span></strong></div>
                <div><span class="text-slate-500">Total Nota</span><br><strong class="text-slate-800">Rp {{ number_format($previewNotaTotal, 0, ',', '.') }}</strong></div>
                <div><span class="text-slate-500">Pengembalian</span><br>Rp {{ number_format($previewPengembalian, 0, ',', '.') }}</div>
                <div><span class="text-slate-500">Tambahan</span><br>Rp {{ number_format($previewTambahan, 0, ',', '.') }}</div>
            </div>
            <div class="flex items-center justify-between border-t border-slate-200 pt-2">
                <span class="text-slate-600">Selisih (Uang Muka + Tambahan − Nota − Pengembalian)</span>
                <strong :class="selisih === 0 ? 'text-teal-700' : (selisih > 0 ? 'text-amber-700' : 'text-red-700')">
                    Rp <span x-text="fmt(selisih)"></span>
                    <span class="text-xs font-normal" x-text="selisih === 0 ? '(Nihil)' : (selisih > 0 ? '(Sisa — wajib dikembalikan)' : '(Kurang — bendahara menambah)')"></span>
                </strong>
            </div>
        </div>
    @endif

    {{-- Tombol --}}
    <div class="flex items-center gap-3">
        @if ($bolehUbah)
            <button type="submit"
                class="inline-flex min-h-[44px] items-center rounded-lg bg-teal-600 px-6 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                Simpan
            </button>
        @endif
        <a href="{{ route('transaksi-kas.index') }}"
            class="min-h-[44px] inline-flex items-center rounded-lg border border-slate-300 px-6 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            {{ $bolehUbah ? 'Batal' : 'Kembali' }}
        </a>
    </div>

</form>
