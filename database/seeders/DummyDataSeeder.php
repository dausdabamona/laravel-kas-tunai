<?php

namespace Database\Seeders;

use App\Enums\JenisTransaksi;
use App\Enums\KategoriLampiran;
use App\Enums\KomponenBiaya;
use App\Enums\PembayarPd;
use App\Enums\Role;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Models\MasterPenyedia;
use App\Models\MultiNota;
use App\Models\RincianPd;
use App\Models\SuratTugas;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\PengembalianService;
use App\Services\SuratTugasService;
use App\Services\TambahanService;
use App\Services\TransaksiService;
use App\Services\UangMukaPdService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data contoh RAMPING & DETERMINISTIK — sengaja sedikit tapi menyentuh SEMUA menu
 * dan setiap skenario penting, agar mudah ditelusuri manual:
 *
 *  - Buku Kas  : penerimaan UP, belanja barang, kepanitiaan, belanja lapangan.
 *  - Pindah Dana: 1 pasangan Bank → Tunai.
 *  - Rekonsiliasi: skenario PAS, LEBIH (→pengembalian), KURANG (→tambahan),
 *    dan BELUM selesai (sisa belum dikembalikan) — penuh menguji fitur uang muka.
 *  - Nota & Foto: foto nota + foto barang (placeholder asli di disk privat).
 *  - Perjalanan Dinas: 1 surat tugas (porsi pelaksana + bendahara).
 *  - Laporan   : seluruh tanggal di Juni 2026 agar masuk rentang default laporan.
 */
class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $bendahara = User::where('role', Role::Bendahara)->first();

        if (! $bendahara) {
            $this->command?->warn('Tidak ada user Bendahara. Jalankan DatabaseSeeder dulu.');

            return;
        }

        // Konteks pembuat (dibuat_oleh) & audit untuk seluruh baris.
        Auth::login($bendahara);

        $penyedia = $this->penyedia();

        // 1. Penerimaan Uang Persediaan (UP) ke kas Bank — modal kerja.
        TransaksiKas::create([
            'tanggal' => '2026-06-01',
            'kegiatan' => 'Penerimaan Uang Persediaan (UP) — DIPA 2026',
            'keterangan' => 'SP2D UP masuk rekening bendahara',
            'debet' => 50_000_000,
            'kredit' => 0,
            'sumber' => Sumber::Bank,
            'jenis' => JenisTransaksi::Masuk,
        ]);

        // 2. Pindah dana Bank → Tunai (tarik tunai untuk kas operasional).
        app(TransaksiService::class)->pindahDana(
            'BANK_TUNAI', 15_000_000, '2026-06-02', 'Tarik tunai untuk kas operasional'
        );

        // 3. Belanja ATK — SPJ PAS (nota = uang muka), lengkap foto nota & barang.
        $atk = $this->belanja('2026-06-03', Sumber::Tunai, 1_000_000, 'Andi Saputra', 'Belanja ATK Kantor');
        $notaAtk = $this->nota($atk, $penyedia['toko'], 600_000, 1);
        $this->nota($atk, $penyedia['cetak'], 400_000, 2);
        // Foto nota & foto barang BERPASANGAN pada nota yang sama.
        $this->attachFoto($notaAtk, KategoriLampiran::FotoNota, 'Nota ATK');
        $this->attachFoto($notaAtk, KategoriLampiran::FotoBarang, 'Foto Barang ATK');

        // 4. Belanja Konsumsi — SISA LEBIH (uang muka 2jt, nota 1,7jt) → pengembalian 300rb (SELESAI).
        $konsumsi = $this->belanja('2026-06-05', Sumber::Tunai, 2_000_000, 'Budi Santoso', 'Konsumsi Rapat Koordinasi Bulanan');
        $this->nota($konsumsi, $penyedia['rm'], 1_700_000, 1);
        app(PengembalianService::class)->catat($konsumsi, [
            'tanggal' => '2026-06-06',
            'jumlah' => 300_000,
            'keterangan' => 'Sisa konsumsi dikembalikan ke kas',
        ]);

        // 5. KEGIATAN KEPANITIAAN — KURANG (uang muka 5jt, honor 5,3jt) → tambahan 300rb (SELESAI).
        $panitia = $this->belanja('2026-06-10', Sumber::Bank, 5_000_000, 'Citra Lestari (Ketua Panitia)',
            'Workshop Peningkatan Kapasitas SDM — Honorarium Kepanitiaan');
        $hKetua = $this->notaHonor($panitia, 'Citra Lestari — Ketua Panitia', 1_500_000, 1);
        $this->notaHonor($panitia, 'Andi Saputra — Sekretaris', 1_300_000, 2);
        $this->notaHonor($panitia, 'Eka Pratiwi — Anggota', 1_250_000, 3);
        $this->notaHonor($panitia, 'Deni Kurniawan — Anggota', 1_250_000, 4);
        $this->attachFoto($hKetua, KategoriLampiran::FotoNota, 'Daftar Hadir & Honor Panitia');
        app(TambahanService::class)->catat($panitia, [
            'tanggal' => '2026-06-11',
            'jumlah' => 300_000,
            'keterangan' => 'Kekurangan honor ditambah bendahara',
        ]);

        // 6. Belanja Lapangan — BELUM SELESAI (uang muka 1,5jt, nota baru 1,2jt; sisa 300rb belum dikembalikan).
        $lapangan = $this->belanja('2026-06-15', Sumber::Tunai, 1_500_000, 'Deni Kurniawan', 'Belanja Bahan Praktik Lapangan');
        $this->nota($lapangan, $penyedia['toko'], 1_200_000, 1);

        // 7. Perjalanan Dinas — 1 surat tugas (pelaksana + bendahara).
        $this->seedPerjalananDinas();

        Auth::logout();

        $this->command?->info('Data contoh (ramping) dibuat:');
        $this->command?->line('users           : '.User::count());
        $this->command?->line('master_penyedia : '.MasterPenyedia::count());
        $this->command?->line('transaksi_kas   : '.TransaksiKas::count());
        $this->command?->line('multi_nota      : '.MultiNota::count());
        $this->command?->line('surat_tugas     : '.SuratTugas::count());
    }

    /**
     * 4 penyedia deterministik (kunci pendek untuk dipakai di nota).
     *
     * @return array<string, MasterPenyedia>
     */
    private function penyedia(): array
    {
        $data = [
            'toko' => ['Toko Sumber Rejeki', '01.234.567.8-901.000', 'Jl. Yos Sudarso No. 12, Sorong'],
            'cetak' => ['CV Mitra Cetak Mandiri', '02.345.678.9-012.000', 'Jl. Basuki Rahmat No. 5, Sorong'],
            'rm' => ['RM Sederhana', null, 'Jl. A. Yani No. 8, Sorong'],
            'atk' => ['UD Cahaya Kasuari', '03.456.789.0-123.000', 'Jl. Kapitan Pattimura, Sorong'],
        ];

        $hasil = [];
        foreach ($data as $kunci => [$nama, $npwp, $alamat]) {
            $hasil[$kunci] = MasterPenyedia::firstOrCreate(
                ['nama' => $nama],
                ['npwp' => $npwp, 'alamat' => $alamat, 'frekuensi' => 1, 'terakhir_digunakan' => now()],
            );
        }

        return $hasil;
    }

    private function belanja(string $tanggal, Sumber $sumber, int $uangMuka, string $penjab, string $kegiatan): TransaksiKas
    {
        return TransaksiKas::create([
            'tanggal' => $tanggal,
            'kegiatan' => $kegiatan,
            'penjab' => $penjab,
            'debet' => 0,
            'kredit' => $uangMuka,
            'uang_diserahkan' => $uangMuka,
            'sumber' => $sumber,
            'jenis' => JenisTransaksi::Belanja,
            'status_spj' => StatusSpj::Belum,
        ]);
    }

    private function nota(TransaksiKas $trx, MasterPenyedia $p, int $nominal, int $urutan): MultiNota
    {
        return MultiNota::create([
            'transaksi_id' => $trx->id,
            'urutan' => $urutan,
            'nama_penyedia' => $p->nama,
            'nominal' => $nominal,
            'npwp_penyedia' => $p->npwp,
            'alamat_penyedia' => $p->alamat,
            'tgl_nota' => $trx->tanggal->toDateString(),
            'penyedia_id' => $p->id,
        ]);
    }

    /** Nota honorarium (penerima perorangan, tanpa NPWP penyedia). */
    private function notaHonor(TransaksiKas $trx, string $nama, int $nominal, int $urutan): MultiNota
    {
        return MultiNota::create([
            'transaksi_id' => $trx->id,
            'urutan' => $urutan,
            'nama_penyedia' => $nama,
            'nominal' => $nominal,
            'tgl_nota' => $trx->tanggal->toDateString(),
        ]);
    }

    /**
     * Lampirkan foto placeholder NYATA ke disk privat agar thumbnail & stream
     * berfungsi. Dilewati bila ekstensi GD tak tersedia.
     */
    private function attachFoto(Model $induk, KategoriLampiran $kategori, string $label): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }

        $path = sprintf('lampiran/2026/%s/%s.jpg', $kategori->value, Str::ulid());
        Storage::disk('privat')->put($path, $this->gambarPlaceholder($label));

        $induk->lampiran()->create([
            'kategori' => $kategori,
            'urutan' => (int) $induk->lampiran()->max('urutan') + 1,
            'disk' => 'privat',
            'path' => $path,
            'nama_file' => Str::slug($label).'.jpg',
            'mime' => 'image/jpeg',
            'meta' => [
                'lat' => -0.876200,
                'lng' => 131.255800,
                'maps_url' => 'https://www.google.com/maps?q=-0.876200,131.255800',
            ],
        ]);
    }

    private function gambarPlaceholder(string $label): string
    {
        $img = imagecreatetruecolor(480, 320);
        imagefill($img, 0, 0, imagecolorallocate($img, 13, 148, 136));
        imagestring($img, 5, 20, 150, $label, imagecolorallocate($img, 255, 255, 255));

        ob_start();
        imagejpeg($img, null, 70);
        $bytes = (string) ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    private function seedPerjalananDinas(): void
    {
        if (SuratTugas::exists()) {
            return;
        }

        // Porsi kas diselaraskan dengan bucket rincian: uang harian (pelaksana,
        // tunai) = 4.120.000; transport+penginapan (bendahara, bank) = 5.500.000.
        $st = app(SuratTugasService::class)->simpan([
            'nomor_surat' => '094/ST/VI/2026',
            'tanggal_surat' => '2026-06-10',
            'dasar' => 'DIPA Politeknik KP Sorong Tahun 2026',
            'maksud' => 'Koordinasi dan monitoring program kelautan',
            'angkutan' => 'Pesawat',
            'tempat_berangkat' => 'Sorong',
            'tempat_tujuan' => 'Makassar',
            'tgl_berangkat' => '2026-06-12',
            'tgl_kembali' => '2026-06-15',
            'lama_hari' => 4,
            'akun' => '524111',
            'jenis' => 'luar_kota',
            'kegiatan' => 'Perjalanan Dinas Koordinasi — Makassar',
            'pegawai' => [
                ['nama' => 'Andi Saputra', 'nip' => '198801010001', 'pangkat' => 'III/a', 'jabatan' => 'Staf', 'golongan' => 'III', 'uang_harian' => 530_000, 'transport' => 2_000_000, 'penginapan' => 1_500_000, 'biaya' => 5_620_000],
                ['nama' => 'Budi Santoso', 'nip' => '198902020002', 'pangkat' => 'III/b', 'jabatan' => 'Staf', 'golongan' => 'III', 'uang_harian' => 500_000, 'transport' => 1_500_000, 'penginapan' => 500_000, 'biaya' => 4_000_000],
            ],
            // Porsi 0 — pergerakan kas kini lewat Uang Muka (tunai/transfer).
            'porsi_pelaksana' => 0,
            'sumber_pelaksana' => 'tunai',
            'porsi_bendahara' => 0,
            'sumber_bendahara' => null,
            'ttd_ppk' => 'Firdaus Dabamona, S.T.',
            'nip_ppk' => '19800101',
        ]);

        $this->seedRincianPd($st);
        $this->seedUangMukaPd($st);
    }

    /** Uang muka per pegawai: uang harian (tunai) + transport/hotel (transfer). */
    private function seedUangMukaPd(SuratTugas $st): void
    {
        $svc = app(UangMukaPdService::class);

        $perPegawai = [
            0 => [['tunai', 2_120_000], ['bank', 3_500_000]], // Andi total 5.620.000
            1 => [['tunai', 2_000_000], ['bank', 2_000_000]], // Budi total 4.000.000
        ];

        foreach ($perPegawai as $index => $items) {
            foreach ($items as [$metode, $jumlah]) {
                $svc->catat($st, [
                    'pegawai_index' => $index,
                    'tanggal' => '2026-06-12',
                    'jumlah' => $jumlah,
                    'metode' => $metode,
                    'keterangan' => $metode === 'tunai' ? 'Uang harian (lumpsum)' : 'Transport & penginapan',
                ]);
            }
        }
    }

    /** Rincian biaya per pegawai (uang harian -> pelaksana/tunai; transport & penginapan -> bendahara/bank). */
    private function seedRincianPd(SuratTugas $st): void
    {
        $perPegawai = [
            0 => [ // Andi — total 5.620.000
                [KomponenBiaya::UangHarian, 'Uang harian', 4, 530_000, PembayarPd::Pelaksana, Sumber::Tunai, 'Lumpsum'],
                [KomponenBiaya::TransportUdara, 'Sorong - Makassar (PP)', 1, 2_000_000, PembayarPd::Bendahara, Sumber::Bank, null],
                [KomponenBiaya::Penginapan, 'Hotel', 3, 500_000, PembayarPd::Bendahara, Sumber::Bank, null],
            ],
            1 => [ // Budi — total 4.000.000
                [KomponenBiaya::UangHarian, 'Uang harian', 4, 500_000, PembayarPd::Pelaksana, Sumber::Tunai, 'Lumpsum'],
                [KomponenBiaya::TransportUdara, 'Sorong - Makassar (PP)', 1, 1_500_000, PembayarPd::Bendahara, Sumber::Bank, null],
                [KomponenBiaya::Penginapan, 'Hotel', 1, 500_000, PembayarPd::Bendahara, Sumber::Bank, null],
            ],
        ];

        foreach ($perPegawai as $index => $items) {
            $nama = $st->pegawai[$index]['nama'];
            foreach ($items as $urutan => [$komponen, $uraian, $qty, $harga, $pembayar, $metode, $ket]) {
                RincianPd::create([
                    'surat_tugas_id' => $st->id,
                    'pegawai_index' => $index,
                    'pegawai_nama' => $nama,
                    'komponen' => $komponen,
                    'uraian' => $uraian,
                    'qty' => $qty,
                    'harga_satuan' => $harga,
                    'nominal' => $qty * $harga,
                    'pembayar' => $pembayar,
                    'metode' => $metode,
                    'keterangan' => $ket,
                    'urutan' => $urutan + 1,
                ]);
            }
        }
    }
}
