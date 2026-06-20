<?php

use App\Services\PajakService;

beforeEach(function () {
    $this->pajak = app(PajakService::class);
});

// 1. klasifikasi
it('klasifikasi mengenali kategori dari kata kunci; fallback ke default', function () {
    expect($this->pajak->klasifikasi('beli ATK kantor')['jenis_pph'])->toBe('PPh Pasal 22')
        ->and($this->pajak->klasifikasi('jasa cleaning')['jenis_pph'])->toBe('PPh Pasal 23')
        ->and($this->pajak->klasifikasi('beli BBM solar')['jenis_pph'])->toBe('')
        ->and($this->pajak->klasifikasi('barang tak terklasifikasi zzz')['label'])
        ->toBe(config('pajak.default')['label']);
});

// 2. Katering (ppn 0)
it('katering (ppn 0): dpp=nominal, pph=nominal*tarif, ppn=0', function () {
    $k = $this->pajak->klasifikasi('katering rapat');
    $r = $this->pajak->hitung(5_000_000, $k, true);

    expect($r['dpp'])->toBe(5_000_000)
        ->and($r['ppn'])->toBe(0)
        ->and($r['pph'])->toBe((int) round(5_000_000 * $k['tarif_pph']));
});

// 3. Ber-PPN >= min_ppn
it('ber-PPN >= min_ppn (sewa kapal): dpp=nominal/(1+ppn), ppn & pph atas dpp', function () {
    $k = $this->pajak->klasifikasi('sewa kapal nelayan');
    $n = 11_100_000;
    $r = $this->pajak->hitung($n, $k, true);

    $dpp = (int) round($n / (1 + $k['ppn']));
    expect($r['dpp'])->toBe($dpp)
        ->and($r['ppn'])->toBe((int) round($dpp * $k['ppn']))
        ->and($r['pph'])->toBe((int) round($dpp * $k['tarif_pph']));
});

// 4. Ambang min_ppn
it('di bawah min_ppn (sewa kapal < 1jt): ppn=0, dpp=nominal', function () {
    $k = $this->pajak->klasifikasi('sewa kapal kecil');
    $n = 500_000;
    $r = $this->pajak->hitung($n, $k, true);

    expect($r['ppn'])->toBe(0)
        ->and($r['dpp'])->toBe($n);
});

// 5. Ambang min_pph
it('ATK di bawah min_pph: pph=0 tapi ppn tetap; di atas: pph terhitung', function () {
    $k = $this->pajak->klasifikasi('beli ATK');

    $r1 = $this->pajak->hitung(1_500_000, $k, true); // < min_pph 2jt, >= min_ppn 1jt
    expect($r1['pph'])->toBe(0)
        ->and($r1['ppn'])->toBeGreaterThan(0);

    $n = 3_000_000;
    $r2 = $this->pajak->hitung($n, $k, true);
    $dpp = (int) round($n / (1 + $k['ppn']));
    expect($r2['pph'])->toBe((int) round($dpp * $k['tarif_pph']));
});

// 6. Non-NPWP
it('non-NPWP: gandakan PPh 22/23, JANGAN Final 4(2) / PPh 21', function () {
    $n = 3_000_000;

    $atk = $this->pajak->klasifikasi('beli ATK'); // PPh 22
    $dppAtk = (int) round($n / (1 + $atk['ppn']));
    expect($this->pajak->hitung($n, $atk, false)['pph'])
        ->toBe((int) round($dppAtk * $atk['tarif_pph'] * 2));

    $gedung = $this->pajak->klasifikasi('sewa gedung aula'); // Final 4(2)
    $nb = 10_000_000;
    $dppGedung = (int) round($nb / (1 + $gedung['ppn']));
    expect($this->pajak->hitung($nb, $gedung, false)['pph'])
        ->toBe((int) round($dppGedung * $gedung['tarif_pph'])); // tidak digandakan
});

// 7. PPh 21 manual
it('PPh 21 honor: manual=true, pph=0', function () {
    $k = $this->pajak->klasifikasi('honor narasumber');
    $r = $this->pajak->hitung(2_000_000, $k, true);

    expect($r['manual'])->toBeTrue()
        ->and($r['pph'])->toBe(0)
        ->and($r['jenis_pph'])->toBe('PPh Pasal 21');
});

// 8. Bebas + MAP/KJS
it('kategori bebas: pph=0, ppn=0; MAP/KJS ikut kategori', function () {
    $k = $this->pajak->klasifikasi('beli BBM solar');
    $r = $this->pajak->hitung(5_000_000, $k, true);

    expect($r['pph'])->toBe(0)
        ->and($r['ppn'])->toBe(0)
        ->and($r['map_pph'])->toBe($k['map_pph'])
        ->and($r['kjs_pph'])->toBe($k['kjs_pph'])
        ->and($r['kategori'])->toBe($k['label']);
});
