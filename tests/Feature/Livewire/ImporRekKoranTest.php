<?php

use App\Enums\JenisTransaksi;
use App\Enums\Role;
use App\Livewire\ImporRekKoran;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function buatXlsxUi(array $baris): UploadedFile
{
    $ss = new Spreadsheet;
    $sheet = $ss->getActiveSheet();
    $sheet->fromArray(['Tanggal', 'Uraian', 'Debet', 'Kredit'], null, 'A1');
    $r = 2;
    foreach ($baris as $row) {
        $sheet->fromArray($row, null, 'A'.$r);
        $r++;
    }
    $path = tempnam(sys_get_temp_dir(), 'rkui').'.xlsx';
    (new Xlsx($ss))->save($path);

    // UploadedFile::fake() -> Testing\File (punya properti ->name) yang dibutuhkan
    // harness file-upload Livewire; UploadedFile mentah tidak punya.
    return UploadedFile::fake()->createWithContent('rk.xlsx', file_get_contents($path));
}

// 7. Alur upload -> pratinjau -> impor -> ringkasan
it('livewire: upload, pratinjau, impor, dan tampil ringkasan', function () {
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());

    $xlsx = buatXlsxUi([
        ['2026-06-10', 'Transfer masuk', 0, 5_000_000],
        ['2026-06-11', 'Biaya admin', 15_000, 0],
    ]);

    Livewire::test(ImporRekKoran::class)
        ->set('file', $xlsx)
        ->call('pratinjau')
        ->assertSet('pratinjau.0.uraian', 'Transfer masuk')
        ->call('impor')
        ->assertSet('ringkasan.ditambah', 2)
        ->assertSet('ringkasan.dilewati', 0);

    expect(TransaksiKas::where('jenis', JenisTransaksi::ImporBank)->count())->toBe(2);
});

// 8. Policy
it('livewire: non-bendahara ditolak impor', function () {
    $this->actingAs(User::factory()->role(Role::Operator)->create());

    Livewire::test(ImporRekKoran::class)
        ->call('pratinjau')
        ->assertForbidden();
});
