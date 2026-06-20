@props(['model', 'placeholder' => '0'])

{{--
    Input nominal rupiah dengan separator ribuan (sisi klien, Alpine).

    Mengikat properti Livewire integer via @entangle (deferred) — nilai mentah
    tetap integer; tampilan diformat "1.234.567". Menghindari bug ketik
    wire:model.live yang memotong angka karena round-trip per ketukan.

    Pemakaian: <x-rupiah-input model="harga_satuan" />
--}}
<div x-data="{
        raw: @entangle($model),
        fmt(n) { n = parseInt(n) || 0; return n ? new Intl.NumberFormat('id-ID').format(n) : ''; },
        parse(v) { return (String(v).replace(/[^0-9]/g, '') * 1) || 0; }
     }">
    <input
        type="text"
        inputmode="numeric"
        autocomplete="off"
        :value="fmt(raw)"
        @input="raw = parse($event.target.value)"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500']) }}
    />
</div>
