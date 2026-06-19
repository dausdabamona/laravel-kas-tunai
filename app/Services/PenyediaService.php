<?php

namespace App\Services;

use App\Models\MasterPenyedia;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PenyediaService
{
    /**
     * Simpan penyedia baru atau pakai ulang yang sudah ada.
     *
     * Pencocokan by nama secara CASE-INSENSITIVE ("CV Maju" = "cv maju") agar
     * tidak ada duplikat lintas-kapitalisasi — tidak mengandalkan unique
     * constraint yang exact-case. Reuse menaikkan frekuensi & memperbarui
     * terakhir_digunakan; npwp/alamat hanya ditimpa bila nilai baru diisi.
     *
     * @param  array{nama: string, npwp?: ?string, alamat?: ?string}  $data
     */
    public function simpanAtauUpdate(array $data): MasterPenyedia
    {
        $nama = trim($data['nama']);
        $npwp = isset($data['npwp']) ? trim((string) $data['npwp']) : null;
        $alamat = isset($data['alamat']) ? trim((string) $data['alamat']) : null;

        return DB::transaction(function () use ($nama, $npwp, $alamat) {
            $penyedia = MasterPenyedia::query()
                ->where('nama_normal', mb_strtolower($nama))
                ->lockForUpdate()
                ->first();

            if ($penyedia) {
                $penyedia->frekuensi++;
                $penyedia->terakhir_digunakan = now();

                // Hanya timpa bila nilai baru benar-benar diisi (jangan hapus data lama).
                if (! empty($npwp)) {
                    $penyedia->npwp = $npwp;
                }
                if (! empty($alamat)) {
                    $penyedia->alamat = $alamat;
                }

                $penyedia->save();

                return $penyedia;
            }

            return MasterPenyedia::create([
                'nama' => $nama,
                'npwp' => $npwp ?: null,
                'alamat' => $alamat ?: null,
                'terakhir_digunakan' => now(),
                'frekuensi' => 1,
            ]);
        });
    }

    /**
     * Cari penyedia by potongan nama atau NPWP (case-insensitive),
     * diurut frekuensi desc untuk autocomplete.
     *
     * @return Collection<int, MasterPenyedia>
     */
    public function cari(string $q): Collection
    {
        $q = trim($q);

        if ($q === '') {
            return $this->getAll();
        }

        $like = '%'.mb_strtolower($q).'%';

        return MasterPenyedia::query()
            ->whereRaw('LOWER(nama) LIKE ?', [$like])
            ->orWhereRaw('LOWER(npwp) LIKE ?', [$like])
            ->orderByDesc('frekuensi')
            ->orderBy('nama')
            ->get();
    }

    /**
     * Seluruh penyedia, sering-dipakai dulu (untuk autocomplete default).
     *
     * @return Collection<int, MasterPenyedia>
     */
    public function getAll(): Collection
    {
        return MasterPenyedia::query()
            ->orderByDesc('frekuensi')
            ->orderBy('nama')
            ->get();
    }
}
