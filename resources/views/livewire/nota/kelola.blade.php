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
        <div class="flex items-center gap-3">
            <span class="rounded-full px-3 py-1 text-sm font-medium {{ $statusSpj->badgeClass() }}">
                {{ $statusSpj->label() }}
            </span>
            <a href="{{ route('cetak.pajak.spj', $transaksi) }}" target="_blank"
                class="inline-flex min-h-[44px] items-center rounded-lg border border-teal-600 px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50">
                Cetak SPJ (PDF)
            </a>
        </div>
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
                <x-rupiah-input model="nominal" />
                @error('nominal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">NPWP Penyedia</label>
                <input wire:model="npwp_penyedia" type="text"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Tanggal Nota</label>
                <x-date-input model="tgl_nota" :value="$tgl_nota" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Kategori Pajak</label>
                <select wire:model="kategori_pajak"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    <option value="">Otomatis (dari uraian kegiatan)</option>
                    @foreach ($daftarKategori as $label)
                        <option value="{{ $label }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Kosongkan untuk deteksi otomatis; pilih untuk memaksa perlakuan PPN/PPh nota ini.</p>
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
                    <th class="px-4 py-3 text-left">Foto Barang</th>
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
                        <td class="px-4 py-3 text-right">
                            <div>Rp {{ number_format($nota->nominal, 0, ',', '.') }}</div>
                            @php $hp = $pajakNota[$nota->id] ?? null; @endphp
                            @if ($hp)
                                <div class="mt-1 text-xs {{ $nota->kategori_pajak ? 'text-teal-600' : 'text-slate-400' }}">
                                    {{ $nota->kategori_pajak ? '🔒 ' : '' }}{{ $hp['kategori'] }}
                                </div>
                                @if ($hp['ppn'] > 0 || $hp['pph'] > 0 || $hp['manual'])
                                    <div class="text-xs text-slate-500">
                                        @if ($hp['ppn'] > 0) PPN Rp {{ number_format($hp['ppn'], 0, ',', '.') }}@endif
                                        @if ($hp['ppn'] > 0 && ($hp['pph'] > 0 || $hp['manual'])) · @endif
                                        @if ($hp['manual'])
                                            {{ $hp['jenis_pph'] }} (manual)
                                        @elseif ($hp['pph'] > 0)
                                            {{ $hp['jenis_pph'] }} Rp {{ number_format($hp['pph'], 0, ',', '.') }}
                                        @endif
                                    </div>
                                @else
                                    <div class="text-xs text-slate-400">tanpa pajak</div>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($nota->lampiran()->where('kategori', \App\Enums\KategoriLampiran::FotoNota->value)->get() as $foto)
                                    <div class="relative">
                                        <a href="{{ $urlFoto($foto) }}" target="_blank"
                                            class="inline-block h-12 w-12 overflow-hidden rounded border border-slate-200">
                                            <img src="{{ $urlFoto($foto) }}" alt="{{ $foto->nama_file }}" class="h-full w-full object-cover" />
                                        </a>
                                        <button type="button" wire:click="hapusFoto({{ $foto->id }})" wire:confirm="Hapus foto ini?"
                                            class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs leading-none text-white hover:bg-red-600">×</button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                <label class="inline-flex min-h-[44px] cursor-pointer items-center text-teal-600">
                                    <input type="file" accept="image/*" class="hidden" wire:model="fotoNota"
                                        x-on:change="tangkapGps()"
                                        x-on:change.debounce.500ms="$wire.simpanFotoNota({{ $nota->id }})" />
                                    + Galeri
                                </label>
                                <label class="inline-flex min-h-[44px] cursor-pointer items-center text-teal-600">
                                    <input type="file" accept="image/*" capture="environment" class="hidden" wire:model="fotoNota"
                                        x-on:change="tangkapGps()"
                                        x-on:change.debounce.500ms="$wire.simpanFotoNota({{ $nota->id }})" />
                                    📷 Kamera
                                </label>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($nota->lampiran()->where('kategori', \App\Enums\KategoriLampiran::FotoBarang->value)->get() as $foto)
                                    <div class="relative">
                                        <a href="{{ $urlFoto($foto) }}" target="_blank"
                                            class="inline-block h-12 w-12 overflow-hidden rounded border border-slate-200">
                                            <img src="{{ $urlFoto($foto) }}" alt="{{ $foto->nama_file }}" class="h-full w-full object-cover" />
                                        </a>
                                        <button type="button" wire:click="hapusFoto({{ $foto->id }})" wire:confirm="Hapus foto ini?"
                                            class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs leading-none text-white hover:bg-red-600">×</button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                <label class="inline-flex min-h-[44px] cursor-pointer items-center text-teal-600">
                                    <input type="file" accept="image/*" class="hidden" wire:model="fotoBarang"
                                        x-on:change="tangkapGps()"
                                        x-on:change.debounce.500ms="$wire.simpanFotoBarang({{ $nota->id }})" />
                                    + Galeri
                                </label>
                                <label class="inline-flex min-h-[44px] cursor-pointer items-center text-teal-600">
                                    <input type="file" accept="image/*" capture="environment" class="hidden" wire:model="fotoBarang"
                                        x-on:change="tangkapGps()"
                                        x-on:change.debounce.500ms="$wire.simpanFotoBarang({{ $nota->id }})" />
                                    📷 Kamera
                                </label>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div>
                                <button wire:click="editNota({{ $nota->id }})" class="mr-2 text-teal-600 hover:underline">Ubah</button>
                                <button wire:click="hapusNota({{ $nota->id }})" wire:confirm="Hapus nota ini?"
                                    class="text-red-500 hover:underline">Hapus</button>
                            </div>
                            <div class="mt-1 flex flex-wrap justify-center gap-2 text-xs">
                                <a href="{{ route('cetak.pajak.ssp-pph', $nota) }}" target="_blank" class="text-blue-600 hover:underline">SSP PPh</a>
                                <a href="{{ route('cetak.pajak.ssp-ppn', $nota) }}" target="_blank" class="text-blue-600 hover:underline">SSP PPN</a>
                                <a href="{{ route('cetak.pajak.kuitansi', $nota) }}" target="_blank" class="text-blue-600 hover:underline">Kuitansi</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Belum ada nota.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-slate-400">Foto Nota &amp; Foto Barang dilampirkan berpasangan pada masing-masing baris nota di atas.</p>

</div>
