<?php

namespace App\Services;

use App\Enums\JenisTransaksi;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use App\Support\Periode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImporBankService
{
    /**
     * Baca sheet pertama .xlsx → koleksi baris {tanggal, uraian, debet, kredit}.
     *
     * Baris yang tanggalnya tak terbaca (mis. header) otomatis dilewati.
     *
     * @param  array{tanggal:int, uraian:int, debet:int, kredit:int}  $peta  indeks kolom
     * @return Collection<int, array{tanggal:string, uraian:string, debet:int, kredit:int}>
     */
    public function parse(UploadedFile $xlsx, array $peta): Collection
    {
        $sheet = IOFactory::load($xlsx->getRealPath())->getActiveSheet();
        $hasil = collect();

        foreach ($sheet->toArray() as $row) {
            $tanggal = $this->parseTanggal($row[$peta['tanggal']] ?? null);

            if ($tanggal === null) {
                continue; // header / baris tanpa tanggal valid
            }

            $hasil->push([
                'tanggal' => $tanggal,
                'uraian' => trim((string) ($row[$peta['uraian']] ?? '')),
                'debet' => (int) round((float) ($row[$peta['debet']] ?? 0)),
                'kredit' => (int) round((float) ($row[$peta['kredit']] ?? 0)),
            ]);
        }

        return $hasil;
    }

    /**
     * Impor baris mutasi ke transaksi_kas (sumber BANK) dengan dedup ref_group.
     *
     * PERSPEKTIF REKENING: kredit rekening = uang MASUK (debet app);
     * debet rekening = uang KELUAR (kredit app). Baris di periode terkunci
     * atau yang sudah pernah diimpor (ref_group cocok) dilewati.
     *
     * @param  array<int, array{tanggal:string, uraian:string, debet:int, kredit:int}>  $baris
     * @return array{ditambah:int, dilewati:int}
     */
    public function impor(array $baris): array
    {
        $ditambah = 0;
        $dilewati = 0;

        foreach ($baris as $b) {
            $masuk = (int) $b['kredit'];   // kredit rekening → uang masuk
            $keluar = (int) $b['debet'];   // debet rekening  → uang keluar

            if ($masuk <= 0 && $keluar <= 0) {
                continue; // baris tanpa nilai diabaikan (tidak dihitung)
            }

            if (Periode::terkunci($b['tanggal'])) {
                $dilewati++;

                continue; // jangan impor ke periode terkunci
            }

            $ref = 'RK-'.$this->hashStabil($b['tanggal'].'|'.($masuk - $keluar).'|'.$this->normalisasi($b['uraian']));

            if (TransaksiKas::where('ref_group', $ref)->exists()) {
                $dilewati++;

                continue; // sudah pernah diimpor (aktif)
            }

            TransaksiKas::create([
                'tanggal' => $b['tanggal'],
                'kegiatan' => $b['uraian'],
                'keterangan' => 'Impor rekening koran',
                'debet' => $masuk,
                'kredit' => $keluar,
                'sumber' => Sumber::Bank,
                'jenis' => JenisTransaksi::ImporBank,
                'ref_group' => $ref,
                'dibuat_oleh' => auth()->id(),
            ]);

            $ditambah++;
        }

        return ['ditambah' => $ditambah, 'dilewati' => $dilewati];
    }

    private function parseTanggal(mixed $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        try {
            if (is_numeric($nilai)) {
                // Serial number Excel.
                return ExcelDate::excelToDateTimeObject((float) $nilai)->format('Y-m-d');
            }

            return Carbon::parse((string) $nilai)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalisasi(string $uraian): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $uraian)));
    }

    private function hashStabil(string $kunci): string
    {
        return substr(md5($kunci), 0, 16);
    }
}
