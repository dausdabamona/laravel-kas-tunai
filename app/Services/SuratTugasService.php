<?php

namespace App\Services;

use App\Enums\JenisTransaksi;
use App\Models\SuratTugas;
use App\Models\TransaksiKas;
use App\Support\Periode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuratTugasService
{
    /**
     * Buat surat tugas + baris transaksi porsi pelaksana (& bendahara bila ada).
     *
     * Porsi pelaksana = baris PD_POKOK; porsi bendahara (jika > 0) = baris
     * PD_BENDAHARA dengan ref_group SAMA (pasangan PD-...). biaya_total dihitung
     * dari Σ biaya seluruh pegawai.
     */
    public function simpan(array $d): SuratTugas
    {
        $this->pastikanPeriodeTerbuka($d['tgl_berangkat']);

        return DB::transaction(function () use ($d) {
            $ref = 'PD-'.Str::ulid();
            $biayaTotal = (int) collect($d['pegawai'] ?? [])->sum('biaya');
            $punyaBendahara = (int) ($d['porsi_bendahara'] ?? 0) > 0;
            $lamaHari = (int) ($d['lama_hari'] ?? $this->hitungLamaHari($d['tgl_berangkat'], $d['tgl_kembali']));

            $pokok = TransaksiKas::create([
                'tanggal' => $d['tgl_berangkat'],
                'kegiatan' => $d['kegiatan'],
                'keterangan' => 'Perjalanan dinas: '.$d['maksud'],
                'debet' => 0,
                'kredit' => (int) $d['porsi_pelaksana'],
                'sumber' => $d['sumber_pelaksana'],
                'jenis' => JenisTransaksi::PdPokok,
                'ref_group' => $ref,
            ]);

            if ($punyaBendahara) {
                TransaksiKas::create([
                    'tanggal' => $d['tgl_berangkat'],
                    'kegiatan' => $d['kegiatan'],
                    'keterangan' => 'Perjalanan dinas (porsi bendahara): '.$d['maksud'],
                    'debet' => 0,
                    'kredit' => (int) $d['porsi_bendahara'],
                    'sumber' => $d['sumber_bendahara'],
                    'jenis' => JenisTransaksi::PdBendahara,
                    'ref_group' => $ref,
                ]);
            }

            return SuratTugas::create([
                'transaksi_id' => $pokok->id,
                'nomor_surat' => $d['nomor_surat'],
                'tanggal_surat' => $d['tanggal_surat'] ?? null,
                'dasar' => $d['dasar'] ?? null,
                'maksud' => $d['maksud'],
                'angkutan' => $d['angkutan'] ?? null,
                'tempat_berangkat' => $d['tempat_berangkat'],
                'tempat_tujuan' => $d['tempat_tujuan'],
                'tgl_berangkat' => $d['tgl_berangkat'],
                'tgl_kembali' => $d['tgl_kembali'],
                'lama_hari' => $lamaHari,
                'akun' => $d['akun'] ?? null,
                'jenis' => $d['jenis'],
                'pegawai' => $d['pegawai'] ?? [],
                'biaya_total' => $biayaTotal,
                'sumber_pelaksana' => $d['sumber_pelaksana'],
                'sumber_bendahara' => $punyaBendahara ? $d['sumber_bendahara'] : null,
                'ttd_ppk' => $d['ttd_ppk'] ?? null,
                'nip_ppk' => $d['nip_ppk'] ?? null,
                'dibuat_oleh' => auth()->id(),
            ]);
        });
    }

    public function get(int $id): SuratTugas
    {
        return SuratTugas::findOrFail($id);
    }

    /**
     * Perbarui header surat tugas. lama_hari dihitung ulang dari tanggal
     * berangkat–kembali; transaksi PD_POKOK tertaut ikut disinkron (kegiatan &
     * tanggal) agar buku kas konsisten.
     */
    public function update(SuratTugas $st, array $d): SuratTugas
    {
        if (array_key_exists('pegawai', $d)) {
            $d['biaya_total'] = (int) collect($d['pegawai'])->sum('biaya');
        }

        if (isset($d['tgl_berangkat'], $d['tgl_kembali'])) {
            $d['lama_hari'] = $this->hitungLamaHari($d['tgl_berangkat'], $d['tgl_kembali']);
        }

        return DB::transaction(function () use ($st, $d) {
            $st->update($d);

            if ($st->transaksi) {
                $st->transaksi->update(array_filter([
                    'kegiatan' => $d['kegiatan'] ?? null,
                    'tanggal' => $d['tgl_berangkat'] ?? null,
                ], fn ($v) => $v !== null));
            }

            return $st;
        });
    }

    /**
     * Lama hari inklusif (berangkat & kembali dihitung). Min 1.
     */
    public function hitungLamaHari(string $tglBerangkat, string $tglKembali): int
    {
        $berangkat = \Illuminate\Support\Carbon::parse($tglBerangkat)->startOfDay();
        $kembali = \Illuminate\Support\Carbon::parse($tglKembali)->startOfDay();

        return max(1, (int) $berangkat->diffInDays($kembali) + 1);
    }

    private function pastikanPeriodeTerbuka(string $tanggal): void
    {
        if (Periode::terkunci($tanggal)) {
            throw new AuthorizationException('Periode terkunci: tidak dapat membuat perjalanan dinas pada periode ini.');
        }
    }
}
