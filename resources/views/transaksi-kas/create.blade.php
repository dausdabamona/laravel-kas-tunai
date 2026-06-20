<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Tambah Transaksi
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 shadow-sm">
                Simpan transaksi terlebih dahulu. Setelah itu buka menu Ubah untuk melengkapi nota, foto tagging barang, dan bukti perjalanan dinas sesuai jenis transaksinya.
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="p-6">
                    <livewire:transaksi-kas.form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
