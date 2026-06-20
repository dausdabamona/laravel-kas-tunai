@props(['model', 'value' => '', 'live' => false])

{{--
    Input tanggal berbahasa Indonesia via flatpickr (tampil "12 Juni 2026",
    nilai disimpan Y-m-d). Bila flatpickr gagal dimuat (offline tanpa aset),
    jatuh ke input type=date native — tetap fungsional.

    Pemakaian: <x-date-input model="tanggal" :value="$tanggal" />
              <x-date-input model="tgl_berangkat" :value="$tgl_berangkat" live />
--}}
<div wire:ignore>
    <input
        type="text"
        x-data
        x-init="
            const live = {{ $live ? 'true' : 'false' }};
            const nama = '{{ $model }}';
            const awal = @js($value);
            if (window.flatpickr) {
                flatpickr($el, {
                    locale: 'id',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'j F Y',
                    allowInput: true,
                    defaultDate: awal || null,
                    onChange: (dates, str) => $wire.set(nama, str, live),
                });
            } else {
                $el.type = 'date';
                if (awal) $el.value = awal;
                $el.addEventListener('change', (e) => $wire.set(nama, e.target.value, live));
            }
        "
        {{ $attributes->merge(['class' => 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500']) }}
    />
</div>
