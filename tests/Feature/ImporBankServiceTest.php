<?php

use App\Enums\JenisTransaksi;
use App\Enums\Role;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\ImporBankService;
use App\Services\SaldoService;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Bangun file .xlsx sementara dari baris [tanggal, uraian, debet, kredit]. */
function buatXlsx(array $baris): UploadedFile
{
    $ss = new Spreadsheet;
    $sheet = $ss->getActiveSheet();
    $sheet->fromArray(['Tanggal', 'Uraian', 'Debet', 'Kredit'], null, 'A1');
    $r = 2;
    foreach ($baris as $row) {
        $sheet->fromArray($row, null, 'A'.$r);
        $r++;
    }
    $path = tempnam(sys_get_temp_dir(), 'rk').'.xlsx';
    (new Xlsx($ss))->save($path);

    return new UploadedFile($path, 'rk.xlsx', null, null, true);
}

const PETA = ['tanggal' => 0, 'uraian' => 1, 'debet' => 2, 'kredit' => 3];

beforeEach(function () {
    config()->set('kas.saldo_awal_bank', 0);
    config()->set('kas.periode_terkunci_hingga', null);
    $this->actingAs($this->importir = User::factory()->role(Role::Bendahara)->create());
    $this->service = app(ImporBankService::class);
    $this->saldo = app(SaldoService::class);
});

// 1. parse sesuai pemetaan
it('parse: membaca baris sesuai pemetaan kolom (header terlewati)', function () {
    $xlsx = buatXlsx([
        ['2026-06-10', 'Transfer masuk', 0, 5_000_000],
        ['2026-06-11', 'Biaya admin', 15_000, 0],
    ]);

    $baris = $this->service->parse($xlsx, PETA);

    expect($baris)->toHaveCount(2)
        ->and($baris[0]['tanggal'])->toBe('2026-06-10')
        ->and($baris[0]['uraian'])->toBe('Transfer masuk')
        ->and($baris[0]['kredit'])->toBe(5_000_000)
        ->and($baris[1]['debet'])->toBe(15_000);
});

// 2. Perspektif rekening
it('perspektif: kredit rekening -> debet app (masuk); debet rekening -> kredit app (keluar)', function () {
    $this->service->impor([
        ['tanggal' => '2026-06-10', 'uraian' => 'Masuk', 'debet' => 0, 'kredit' => 5_000_000],
        ['tanggal' => '2026-06-11', 'uraian' => 'Keluar', 'debet' => 15_000, 'kredit' => 0],
    ]);

    $masuk = TransaksiKas::where('kegiatan', 'Masuk')->first();
    $keluar = TransaksiKas::where('kegiatan', 'Keluar')->first();

    expect($masuk->debet)->toBe(5_000_000)
        ->and($masuk->kredit)->toBe(0)
        ->and($keluar->kredit)->toBe(15_000)
        ->and($keluar->debet)->toBe(0);
});

// 3. Dedup
it('dedup: impor baris sama dua kali -> kali kedua tidak menambah', function () {
    $baris = [['tanggal' => '2026-06-10', 'uraian' => 'Transfer masuk', 'debet' => 0, 'kredit' => 5_000_000]];

    $pertama = $this->service->impor($baris);
    $kedua = $this->service->impor($baris);

    expect($pertama['ditambah'])->toBe(1)
        ->and($kedua['ditambah'])->toBe(0)
        ->and($kedua['dilewati'])->toBe(1)
        ->and(TransaksiKas::where('jenis', JenisTransaksi::ImporBank)->count())->toBe(1);
});

// 4. Atribut baris impor
it('baris impor: jenis impor_bank, sumber bank, dibuat_oleh importir', function () {
    $this->service->impor([
        ['tanggal' => '2026-06-10', 'uraian' => 'Masuk', 'debet' => 0, 'kredit' => 1_000_000],
    ]);

    $row = TransaksiKas::where('jenis', JenisTransaksi::ImporBank)->first();
    expect($row->sumber)->toBe(Sumber::Bank)
        ->and($row->dibuat_oleh)->toBe($this->importir->id)
        ->and($row->keterangan)->toBe('Impor rekening koran');
});

// 5. Saldo bank
it('saldo bank bergerak sesuai mutasi (masuk - keluar)', function () {
    $this->service->impor([
        ['tanggal' => '2026-06-10', 'uraian' => 'Masuk', 'debet' => 0, 'kredit' => 5_000_000],
        ['tanggal' => '2026-06-11', 'uraian' => 'Keluar', 'debet' => 15_000, 'kredit' => 0],
    ]);

    expect($this->saldo->deltaPerSumber(Sumber::Bank))->toBe(4_985_000);
});

// 6. Periode terkunci
it('baris bertanggal di periode terkunci dilewati, bukan dibuat', function () {
    config()->set('kas.periode_terkunci_hingga', '2026-05-31');

    $hasil = $this->service->impor([
        ['tanggal' => '2026-05-20', 'uraian' => 'Lama (terkunci)', 'debet' => 0, 'kredit' => 1_000_000],
        ['tanggal' => '2026-06-10', 'uraian' => 'Baru', 'debet' => 0, 'kredit' => 2_000_000],
    ]);

    expect($hasil['ditambah'])->toBe(1)
        ->and($hasil['dilewati'])->toBe(1)
        ->and(TransaksiKas::where('kegiatan', 'Lama (terkunci)')->exists())->toBeFalse()
        ->and(TransaksiKas::where('kegiatan', 'Baru')->exists())->toBeTrue();
});
