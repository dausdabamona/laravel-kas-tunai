<div class="mx-auto max-w-lg space-y-6">

    {{-- Saldo --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-xl border border-teal-200 bg-teal-50 p-3">
            <p class="text-xs uppercase tracking-wide text-teal-600">Saldo Tunai</p>
            <p class="text-lg font-bold text-teal-800">Rp {{ number_format($rekap['tunai'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-3">
            <p class="text-xs uppercase tracking-wide text-blue-600">Saldo Bank</p>
            <p class="text-lg font-bold text-blue-800">Rp {{ number_format($rekap['bank'], 0, ',', '.') }}</p>
        </div>
    </div>

    <form wire:submit="simpan" class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-slate-800">Pindah Dana Antar Kas</h2>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Arah <span class="text-red-500">*</span></label>
            <select wire:model="arah" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="BANK_TUNAI">Tarik Tunai (Bank → Tunai)</option>
                <option value="TUNAI_BANK">Setor (Tunai → Bank)</option>
            </select>
            @error('arah') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Nominal (Rp) <span class="text-red-500">*</span></label>
            <input wire:model="nominal" type="number" min="1"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
            @error('nominal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal <span class="text-red-500">*</span></label>
            <input wire:model="tanggal" type="date"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
            @error('tanggal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Keterangan</label>
            <input wire:model="keterangan" type="text" placeholder="Opsional"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
        </div>

        <button type="submit"
            class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
            Pindahkan Dana
        </button>
    </form>

</div>
