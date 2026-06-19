<?php

namespace App\Services;

use App\Enums\JenisTransaksi;
use App\Models\SuratTugas;
use App\Models\TransaksiKas;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
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
            $biayaTotal = (int) collect($d['pegawai'])->sum('biaya');
            $punyaBendahara = (int) ($d['porsi_bendahara'] ?? 0) > 0;

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
                'dasar' => $d['dasar'] ?? null,
                'maksud' => $d['maksud'],
                'angkutan' => $d['angkutan'] ?? null,
                'tempat_berangkat' => $d['tempat_berangkat'],
                'tempat_tujuan' => $d['tempat_tujuan'],
                'tgl_berangkat' => $d['tgl_berangkat'],
                'tgl_kembali' => $d['tgl_kembali'],
                'lama_hari' => (int) $d['lama_hari'],
                'akun' => $d['akun'] ?? null,
                'jenis' => $d['jenis'],
                'pegawai' => $d['pegawai'],
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

    public function update(SuratTugas $st, array $d): SuratTugas
    {
        if (array_key_exists('pegawai', $d)) {
            $d['biaya_total'] = (int) collect($d['pegawai'])->sum('biaya');
        }

        $st->update($d);

        return $st;
    }

    private function pastikanPeriodeTerbuka(string $tanggal): void
    {
        $batas = config('kas.periode_terkunci_hingga');

        if (! empty($batas) && Carbon::parse($tanggal)->lte(Carbon::parse($batas)->endOfDay())) {
            throw new AuthorizationException('Periode terkunci: tidak dapat membuat perjalanan dinas pada periode ini.');
        }
    }
}
