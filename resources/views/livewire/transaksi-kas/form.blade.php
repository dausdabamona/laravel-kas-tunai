<form wire:submit="simpan" class="space-y-6">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

        {{-- Tanggal --}}
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal <span class="text-red-500">*</span></label>
            <input wire:model="tanggal" type="date"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('tanggal') border-red-400 @enderror" />
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
            <select wire:model="jenis"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('jenis') border-red-400 @enderror">
                <option value="">— Pilih —</option>
                @foreach ($jenisPilihan as $j)
                    <option value="{{ $j->value }}">{{ $j->label() }}</option>
                @endforeach
            </select>
            @error('jenis') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Debet --}}
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Debet (Rp)</label>
            <input wire:model="debet" type="number" min="0" placeholder="0"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('debet') border-red-400 @enderror" />
            @error('debet') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Kredit --}}
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Kredit (Rp)</label>
            <input wire:model="kredit" type="number" min="0" placeholder="0"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('kredit') border-red-400 @enderror" />
            @error('kredit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Status SPJ --}}
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Status SPJ <span class="text-red-500">*</span></label>
            <select wire:model="status_spj"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('status_spj') border-red-400 @enderror">
                <option value="">— Pilih —</option>
                @foreach ($statusSpjPilihan as $st)
                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                @endforeach
            </select>
            @error('status_spj') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
                <input wire:model="tgl_spby" type="date" class="w-full rounded-lg border-slate-300 text-sm shadow-sm" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Nilai SPBY (Rp)</label>
                <input wire:model="nilai_spby" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm shadow-sm" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Uang Diserahkan (Rp)</label>
                <input wire:model="uang_diserahkan" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm shadow-sm" />
            </div>
        </div>
    </details>

    {{-- Tombol --}}
    <div class="flex items-center gap-3">
        <button type="submit"
            class="inline-flex min-h-[44px] items-center rounded-lg bg-teal-600 px-6 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
            Simpan
        </button>
        <a href="{{ route('transaksi-kas.index') }}"
            class="min-h-[44px] inline-flex items-center rounded-lg border border-slate-300 px-6 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Batal
        </a>
    </div>

</form>
