<div class="py-6">
    <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-800">{{ $suratTugasId ? 'Ubah' : 'Buat' }} Perjalanan Dinas</h2>
            <a href="{{ route('perjalanan-dinas.index') }}" wire:navigate class="text-sm text-slate-500 hover:underline">← Kembali</a>
        </div>

        <form wire:submit="simpan" class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nomor Surat Tugas <span class="text-red-500">*</span></label>
                    <input wire:model="nomor_surat" type="text" placeholder="mis. 094/ST/VI/2026"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                    @error('nomor_surat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal Surat</label>
                    <x-date-input model="tanggal_surat" :value="$tanggal_surat" />
                    @error('tanggal_surat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tujuan Kegiatan (Maksud) <span class="text-red-500">*</span></label>
                    <input wire:model="maksud" type="text" placeholder="mis. Koordinasi program kelautan"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                    @error('maksud') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Uraian Kegiatan (Buku Kas) <span class="text-red-500">*</span></label>
                    <input wire:model="kegiatan" type="text" placeholder="mis. Perjalanan Dinas Makassar"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                    @error('kegiatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tempat Berangkat <span class="text-red-500">*</span></label>
                    <input wire:model="tempat_berangkat" type="text"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                    @error('tempat_berangkat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Kota Tujuan <span class="text-red-500">*</span></label>
                    <input wire:model="tempat_tujuan" type="text" placeholder="mis. Makassar"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                    @error('tempat_tujuan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal Berangkat <span class="text-red-500">*</span></label>
                    <x-date-input model="tgl_berangkat" :value="$tgl_berangkat" live />
                    @error('tgl_berangkat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal Kembali <span class="text-red-500">*</span></label>
                    <x-date-input model="tgl_kembali" :value="$tgl_kembali" live />
                    @error('tgl_kembali') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Jenis</label>
                    <select wire:model="jenis" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="luar_kota">Luar Kota</option>
                        <option value="dalam_kota">Dalam Kota</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Lama Hari (otomatis)</label>
                    <div class="flex h-[42px] items-center rounded-lg bg-slate-50 px-3 text-sm font-semibold text-teal-800">
                        {{ $this->lamaHari }} hari
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Dihitung dari berangkat–kembali (inklusif), dipakai untuk uang harian/fullboard.</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Akun</label>
                    <input wire:model="akun" type="text" placeholder="mis. 524111"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Dasar</label>
                    <input wire:model="dasar" type="text" placeholder="mis. DIPA 2026"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                </div>
            </div>

            {{-- Pegawai --}}
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">Pelaksana / Pegawai</h3>
                    <button type="button" wire:click="tambahPegawai" class="text-sm text-teal-600 hover:underline">+ Tambah Pegawai</button>
                </div>
                @error('pegawai') <p class="mb-2 text-xs text-red-600">{{ $message }}</p> @enderror

                <div class="space-y-2">
                    @foreach ($pegawai as $i => $p)
                        <div class="grid grid-cols-1 gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-6">
                            <div class="sm:col-span-2">
                                <input wire:model="pegawai.{{ $i }}.nama" type="text" placeholder="Nama *"
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                                @error('pegawai.'.$i.'.nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <input wire:model="pegawai.{{ $i }}.nip" type="text" placeholder="NIP"
                                class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                            <input wire:model="pegawai.{{ $i }}.pangkat" type="text" placeholder="Pangkat"
                                class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                            <input wire:model="pegawai.{{ $i }}.jabatan" type="text" placeholder="Jabatan"
                                class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                            <div class="flex gap-2">
                                <input wire:model="pegawai.{{ $i }}.golongan" type="text" placeholder="Gol."
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                                <button type="button" wire:click="hapusPegawai({{ $i }})" class="shrink-0 rounded-lg border border-red-200 px-2 text-sm text-red-500 hover:bg-red-50">×</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="inline-flex min-h-[44px] items-center rounded-lg bg-teal-600 px-6 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    Simpan Surat Tugas
                </button>
                <a href="{{ route('perjalanan-dinas.index') }}" wire:navigate
                    class="inline-flex min-h-[44px] items-center rounded-lg border border-slate-300 px-6 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
