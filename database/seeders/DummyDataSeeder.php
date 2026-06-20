<?php

namespace Database\Seeders;

use App\Models\MasterPenyedia;
use App\Models\MultiNota;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\SuratTugasService;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    /**
     * Seed dummy business data so UI pages have realistic content.
     */
    public function run(): void
    {
        $users = User::pluck('id');

        if ($users->isEmpty()) {
            $this->command?->warn('Tidak ada user. Jalankan DatabaseSeeder terlebih dahulu.');

            return;
        }

        $penyedia = MasterPenyedia::factory()->count(12)->create();
        $transaksi = TransaksiKas::factory()->count(30)->create();

        $targetNota = $transaksi
            ->filter(fn (TransaksiKas $t) => (int) $t->kredit > 0)
            ->take(18)
            ->values();

        foreach ($targetNota as $trx) {
            $jumlahNota = random_int(1, 3);
            $sisa = (int) $trx->kredit;

            for ($urutan = 1; $urutan <= $jumlahNota; $urutan++) {
                if ($sisa <= 0) {
                    break;
                }

                $refPenyedia = $penyedia->random();
                $nominal = $urutan === $jumlahNota
                    ? $sisa
                    : min(
                        $sisa,
                        max(10_000, (int) floor($trx->kredit / $jumlahNota) + random_int(-25_000, 25_000))
                    );

                MultiNota::create([
                    'transaksi_id' => $trx->id,
                    'urutan' => $urutan,
                    'nama_penyedia' => $refPenyedia->nama,
                    'nominal' => $nominal,
                    'npwp_penyedia' => $refPenyedia->npwp,
                    'alamat_penyedia' => $refPenyedia->alamat,
                    'tgl_nota' => now()->subDays(random_int(1, 120))->toDateString(),
                    'penyedia_id' => $refPenyedia->id,
                ]);

                $sisa -= $nominal;
            }
        }

        $this->seedPerjalananDinas();

        $this->command?->info('Dummy data berhasil dibuat:');
        $this->command?->line('users: '.User::count());
        $this->command?->line('master_penyedia: '.MasterPenyedia::count());
        $this->command?->line('transaksi_kas: '.TransaksiKas::count());
        $this->command?->line('multi_nota: '.MultiNota::count());
        $this->command?->line('surat_tugas: '.\App\Models\SuratTugas::count());
    }

    private function seedPerjalananDinas(): void
    {
        if (\App\Models\SuratTugas::exists()) {
            return;
        }

        $service = app(SuratTugasService::class);

        foreach ([
            [
                'nomor_surat' => '094/ST/2026',
                'maksud' => 'Koordinasi program kelautan',
                'tempat_tujuan' => 'Jakarta',
                'kegiatan' => 'Perjalanan Dinas Jakarta',
                'pegawai' => [
                    ['nama' => 'Andi Saputra', 'nip' => '198801010001', 'pangkat' => 'III/a', 'jabatan' => 'Staf', 'golongan' => 'III', 'uang_harian' => 530000, 'transport' => 2000000, 'penginapan' => 1500000, 'biaya' => 5620000],
                    ['nama' => 'Budi Santoso', 'nip' => '198902020002', 'pangkat' => 'III/b', 'jabatan' => 'Staf', 'golongan' => 'III', 'uang_harian' => 500000, 'transport' => 1500000, 'penginapan' => 500000, 'biaya' => 4000000],
                ],
                'porsi_pelaksana' => 6000000,
                'sumber_pelaksana' => 'tunai',
                'porsi_bendahara' => 3620000,
                'sumber_bendahara' => 'bank',
            ],
            [
                'nomor_surat' => '095/ST/2026',
                'maksud' => 'Monitoring kegiatan lapangan',
                'tempat_tujuan' => 'Makassar',
                'kegiatan' => 'Perjalanan Dinas Makassar',
                'pegawai' => [
                    ['nama' => 'Citra Lestari', 'nip' => '199001030003', 'pangkat' => 'III/c', 'jabatan' => 'Analis', 'golongan' => 'III', 'uang_harian' => 450000, 'transport' => 1200000, 'penginapan' => 900000, 'biaya' => 3000000],
                ],
                'porsi_pelaksana' => 3000000,
                'sumber_pelaksana' => 'bank',
                'porsi_bendahara' => 0,
                'sumber_bendahara' => null,
            ],
            [
                'nomor_surat' => '096/ST/2026',
                'maksud' => 'Pendampingan evaluasi anggaran',
                'tempat_tujuan' => 'Manokwari',
                'kegiatan' => 'Perjalanan Dinas Manokwari',
                'pegawai' => [
                    ['nama' => 'Deni Kurniawan', 'nip' => '199102040004', 'pangkat' => 'III/b', 'jabatan' => 'Operator', 'golongan' => 'III', 'uang_harian' => 350000, 'transport' => 500000, 'penginapan' => 650000, 'biaya' => 1850000],
                    ['nama' => 'Eka Pratiwi', 'nip' => '199203050005', 'pangkat' => 'III/c', 'jabatan' => 'Verifikator', 'golongan' => 'III', 'uang_harian' => 350000, 'transport' => 500000, 'penginapan' => 650000, 'biaya' => 1850000],
                ],
                'porsi_pelaksana' => 2500000,
                'sumber_pelaksana' => 'tunai',
                'porsi_bendahara' => 1200000,
                'sumber_bendahara' => 'bank',
            ],
        ] as $index => $data) {
            $tanggalBerangkat = now()->subDays(20 - ($index * 5));
            $tanggalKembali = (clone $tanggalBerangkat)->addDays(3);

            $service->simpan([
                'nomor_surat' => $data['nomor_surat'],
                'dasar' => 'DIPA 2026',
                'maksud' => $data['maksud'],
                'angkutan' => 'Pesawat',
                'tempat_berangkat' => 'Sorong',
                'tempat_tujuan' => $data['tempat_tujuan'],
                'tgl_berangkat' => $tanggalBerangkat->toDateString(),
                'tgl_kembali' => $tanggalKembali->toDateString(),
                'lama_hari' => 4,
                'akun' => '524111',
                'jenis' => 'luar_kota',
                'kegiatan' => $data['kegiatan'],
                'pegawai' => $data['pegawai'],
                'porsi_pelaksana' => $data['porsi_pelaksana'],
                'sumber_pelaksana' => $data['sumber_pelaksana'],
                'porsi_bendahara' => $data['porsi_bendahara'],
                'sumber_bendahara' => $data['sumber_bendahara'],
                'ttd_ppk' => 'Firdaus Dabamona',
                'nip_ppk' => '19800101',
            ]);
        }
    }
}
