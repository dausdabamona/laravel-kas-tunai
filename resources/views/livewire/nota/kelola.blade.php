<div class="space-y-6"
     x-data="{
        tangkapGps() {
            if (! navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition((pos) => {
                @this.set('lat', pos.coords.latitude);
                @this.set('lng', pos.coords.longitude);
                @this.set('maps_url', `https://www.google.com/maps?q=${pos.coords.latitude},${pos.coords.longitude}`);
            });
        }
     }">

    {{-- Ringkasan --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Nota</p>
            <p class="text-2xl font-bold text-teal-800">Rp {{ number_format($totalNota, 0, ',', '.') }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-sm font-medium {{ $statusSpj->badgeClass() }}">
            {{ $statusSpj->label() }}
        </span>
    </div>

    {{-- Form nota --}}
    <form wire:submit="simpanNota" class="space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-700">{{ $editingId ? 'Ubah Nota' : 'Tambah Nota' }}</h3>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="relative">
                <label class="mb-1 block text-xs font-medium text-slate-600">Nama Penyedia <span class="text-red-500">*</span></label>
                <input wire:model="nama_penyedia" wire:keyup.debounce.300ms="cariPenyedia" type="text"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                @error('nama_penyedia') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                @if (! empty($kandidatPenyedia))
                    <ul class="absolute z-10 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                        @foreach ($kandidatPenyedia as $kandidat)
                            <li>
                                <button type="button" wire:click="pilihPenyedia({{ $kandidat['id'] }})"
                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-teal-50">
                                    {{ $kandidat['nama'] }}
                                    @if ($kandidat['npwp']) <span class="text-xs text-slate-400">— {{ $kandidat['npwp'] }}</span> @endif
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Nominal (Rp) <span class="text-red-500">*</span></label>
                <input wire:model="nominal" type="number" min="1"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                @error('nominal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">NPWP Penyedia</label>
                <input wire:model="npwp_penyedia" type="text"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Tanggal Nota</label>
                <input wire:model="tgl_nota" type="date"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">Alamat Penyedia</label>
                <input wire:model="alamat_penyedia" type="text"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
            </div>
        </div>

        <button type="submit"
            class="inline-flex min-h-[44px] items-center rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
            {{ $editingId ? 'Perbarui' : 'Simpan' }} Nota
        </button>
    </form>

    {{-- Daftar nota --}}
    <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">Penyedia</th>
                    <th class="px-4 py-3 text-right">Nominal</th>
                    <th class="px-4 py-3 text-left">Foto Nota</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($daftarNota as $nota)
                    <tr class="align-top hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-500">{{ $nota->urutan }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ $nota->nama_penyedia }}</div>
                            @if ($nota->npwp_penyedia) <div class="text-xs text-slate-400">{{ $nota->npwp_penyedia }}</div> @endif
                        </td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($nota->nominal, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($nota->lampiran()->where('kategori', \App\Enums\KategoriLampiran::FotoNota->value)->get() as $foto)
                                    <a href="{{ $urlFoto($foto) }}" target="_blank"
                                        class="inline-block h-12 w-12 overflow-hidden rounded border border-slate-200">
                                        <img src="{{ $urlFoto($foto) }}" alt="{{ $foto->nama_file }}" class="h-full w-full object-cover" />
                                    </a>
                                @endforeach
                            </div>
                            <label class="mt-1 inline-flex min-h-[44px] cursor-pointer items-center text-xs text-teal-600">
                                <input type="file" class="hidden" wire:model="fotoNota"
                                    x-on:change="tangkapGps()"
                                    x-on:change.debounce.500ms="$wire.simpanFotoNota({{ $nota->id }})" />
                                + Foto nota
                            </label>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="editNota({{ $nota->id }})" class="mr-2 text-teal-600 hover:underline">Ubah</button>
                            <button wire:click="hapusNota({{ $nota->id }})" wire:confirm="Hapus nota ini?"
                                class="text-red-500 hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Belum ada nota.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Foto barang (level transaksi) --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">Foto Barang</h3>
            <label class="inline-flex min-h-[44px] cursor-pointer items-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                <input type="file" class="hidden" wire:model="fotoBarang"
                    x-on:change="tangkapGps()"
                    x-on:change.debounce.500ms="$wire.simpanFotoBarang()" />
                + Unggah
            </label>
        </div>
        <div class="flex flex-wrap gap-2">
            @forelse ($daftarFotoBarang as $foto)
                <a href="{{ $urlFoto($foto) }}" target="_blank"
                    class="inline-block h-16 w-16 overflow-hidden rounded border border-slate-200">
                    <img src="{{ $urlFoto($foto) }}" alt="{{ $foto->nama_file }}" class="h-full w-full object-cover" />
                </a>
            @empty
                <p class="text-xs text-slate-400">Belum ada foto barang.</p>
            @endforelse
        </div>
    </div>

</div>
