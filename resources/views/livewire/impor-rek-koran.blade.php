<div class="mx-auto max-w-4xl space-y-6">

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-base font-semibold text-slate-800">Impor Rekening Koran (.xlsx)</h2>

        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium text-slate-700">Berkas Rekening Koran</label>
                <input wire:model="file" type="file" accept=".xlsx,.xls"
                    class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-600 file:px-4 file:py-2 file:text-white hover:file:bg-teal-700" />
                @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button wire:click="pratinjau" type="button"
                class="inline-flex min-h-[44px] items-center rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Pratinjau
            </button>
        </div>

        {{-- Pemetaan kolom (override manual) --}}
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach (['tanggal' => 'Tanggal', 'uraian' => 'Uraian', 'debet' => 'Debet', 'kredit' => 'Kredit'] as $key => $labelKolom)
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Kolom {{ $labelKolom }}</label>
                    <input wire:model="peta.{{ $key }}" type="number" min="0"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                </div>
            @endforeach
        </div>
    </div>

    {{-- Pratinjau --}}
    @if (! empty($pratinjau))
        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Uraian</th>
                        <th class="px-4 py-3 text-right">Debet (rek)</th>
                        <th class="px-4 py-3 text-right">Kredit (rek)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($pratinjau as $b)
                        <tr>
                            <td class="px-4 py-2">{{ $b['tanggal'] }}</td>
                            <td class="px-4 py-2">{{ $b['uraian'] }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($b['debet'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($b['kredit'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button wire:click="impor" type="button"
            class="inline-flex min-h-[44px] items-center rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">
            Konfirmasi Impor ({{ count($pratinjau) }} baris)
        </button>
    @endif

    {{-- Ringkasan --}}
    @if ($ringkasan)
        <div class="rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-800">
            Impor selesai: <strong>{{ $ringkasan['ditambah'] }}</strong> ditambahkan,
            <strong>{{ $ringkasan['dilewati'] }}</strong> dilewati (duplikat / periode terkunci).
        </div>
    @endif

</div>
