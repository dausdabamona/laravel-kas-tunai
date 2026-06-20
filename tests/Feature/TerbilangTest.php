<?php

use App\Support\Terbilang;

it('mengubah angka menjadi kata Bahasa Indonesia', function () {
    expect(Terbilang::rupiah(0))->toBe('nol rupiah')
        ->and(Terbilang::rupiah(1_000))->toBe('seribu rupiah')
        ->and(Terbilang::rupiah(1_500))->toBe('seribu lima ratus rupiah')
        ->and(Terbilang::rupiah(11_100_000))->toBe('sebelas juta seratus ribu rupiah')
        ->and(Terbilang::rupiah(2_000_000))->toBe('dua juta rupiah');
});

it('menangani belasan dan ratusan dengan benar', function () {
    expect(Terbilang::angka(11))->toBe('sebelas')
        ->and(Terbilang::angka(19))->toBe('sembilan belas')
        ->and(Terbilang::angka(100))->toBe('seratus')
        ->and(Terbilang::angka(125))->toBe('seratus dua puluh lima');
});
