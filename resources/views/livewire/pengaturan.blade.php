<div class="mx-auto max-w-lg space-y-6">

    <form wire:submit="simpan" class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-slate-800">Kunci Periode</h2>
        <p class="text-sm text-slate-500">
            Transaksi dengan tanggal pada atau sebelum tanggal ini terkunci —
            tidak dapat diubah, dihapus, atau ditambah oleh peran mana pun.
        </p>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Terkunci Hingga Tanggal</label>
            <x-date-input model="periodeTerkunciHingga" :value="$periodeTerkunciHingga" />
            @error('periodeTerkunciHingga') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-slate-400">Kosongkan untuk membuka semua periode.</p>
        </div>

        <button type="submit"
            wire:confirm="Kunci periode sampai tanggal ini? Transaksi pada periode tersebut tak bisa diubah."
            class="inline-flex min-h-[44px] items-center rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500">
            Simpan Kunci Periode
        </button>

        <div x-data="{ show: false }" x-on:pengaturan-tersimpan.window="show = true; setTimeout(() => show = false, 3000)"
            x-show="show" x-cloak class="text-sm text-teal-700">
            Pengaturan tersimpan.
        </div>
    </form>

</div>
