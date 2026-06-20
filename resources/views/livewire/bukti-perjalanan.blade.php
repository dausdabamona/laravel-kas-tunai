<div class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-700">Bukti / Dokumen Pendukung</h3>
        @if ($urlZip)
            <a href="{{ $urlZip }}"
                class="inline-flex min-h-[44px] items-center rounded-lg border border-teal-600 px-3 py-1.5 text-sm font-medium text-teal-700 hover:bg-teal-50">
                Unduh ZIP
            </a>
        @endif
    </div>

    <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1">
            <input wire:model="berkas" type="file" accept="image/*,application/pdf"
                class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-600 file:px-4 file:py-2 file:text-white hover:file:bg-teal-700" />
            @error('berkas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <button wire:click="unggah" type="button"
            class="inline-flex min-h-[44px] items-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
            Unggah
        </button>
        <label class="inline-flex min-h-[44px] cursor-pointer items-center rounded-lg border border-teal-600 px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50">
            <input type="file" accept="image/*" capture="environment" class="hidden"
                wire:model="berkas" x-on:change.debounce.500ms="$wire.unggah()" />
            📷 Kamera
        </label>
    </div>

    {{-- Tempel screenshot (paste) --}}
    <div tabindex="0"
        x-on:paste="
            for (const it of ($event.clipboardData?.items || [])) {
                if (it.type.startsWith('image/')) {
                    $event.preventDefault();
                    $wire.upload('berkas', it.getAsFile(), () => $wire.unggah());
                    break;
                }
            }
        "
        class="cursor-text rounded-lg border border-dashed border-slate-300 px-3 py-2 text-xs text-slate-400 focus:border-teal-500 focus:text-teal-600 focus:outline-none">
        📋 Tempel screenshot di sini — klik lalu tekan Ctrl+V
    </div>

    <div class="flex flex-wrap gap-2">
        @forelse ($daftarBukti as $bukti)
            <div class="relative">
                <a href="{{ $urlBukti($bukti) }}" target="_blank"
                    class="inline-block h-16 w-16 overflow-hidden rounded border border-slate-200">
                    @if (str_starts_with($bukti->mime, 'image/'))
                        <img src="{{ $urlBukti($bukti) }}" alt="{{ $bukti->nama_file }}" class="h-full w-full object-cover" />
                    @else
                        <span class="flex h-full w-full items-center justify-center text-xs text-slate-500">PDF</span>
                    @endif
                </a>
                <button wire:click="hapus({{ $bukti->id }})" wire:confirm="Hapus bukti ini?"
                    class="absolute -right-2 -top-2 rounded-full bg-red-500 px-1.5 text-xs text-white">×</button>
            </div>
        @empty
            <p class="text-xs text-slate-400">Belum ada bukti.</p>
        @endforelse
    </div>

</div>
