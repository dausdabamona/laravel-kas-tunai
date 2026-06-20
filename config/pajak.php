<?php

/*
|--------------------------------------------------------------------------
| Referensi Pajak Bendahara — port apa adanya dari PAJAK_REF (GAS Kas Tunai)
|--------------------------------------------------------------------------
| CATATAN STRUKTUR (jangan disederhanakan):
| - 'ppn'      : TARIF PPN (0 atau 0.11), BUKAN boolean.
| - 'min_pph'  : ambang nilai agar PPh dipungut (mis. PPh 22 = 2.000.000). 0 = selalu.
| - 'min_ppn'  : ambang nilai agar PPN dipungut (umumnya 1.000.000). 0 = selalu.
| - jenis ''   : tidak dipungut pajak otomatis.
| - PPh 21     : tarif 0 → HITUNG MANUAL (tabel PPh 21), service hanya menandai.
| - Non-NPWP   : penggandaan tarif HANYA untuk PPh Pasal 22 & PPh Pasal 23.
*/

return [

    'kategori' => [

        ['label' => 'BBM / Bahan Bakar',
            'kata_kunci' => ['bbm', 'bahan bakar', 'pertamina', 'solar', 'bensin', 'pertalite', 'dexlite'],
            'jenis_pph' => '', 'tarif_pph' => 0, 'ppn' => 0, 'map_pph' => '', 'kjs_pph' => '',
            'min_pph' => 0, 'min_ppn' => 0, 'catatan' => 'Tidak dipungut pajak'],

        ['label' => 'Air Minum / PDAM',
            'kata_kunci' => ['air minum', 'pdam', 'galon', 'aqua', 'air mineral'],
            'jenis_pph' => '', 'tarif_pph' => 0, 'ppn' => 0, 'map_pph' => '', 'kjs_pph' => '',
            'min_pph' => 0, 'min_ppn' => 0, 'catatan' => 'Tidak dipungut pajak'],

        ['label' => 'Makan Minum / Jamuan / Katering',
            'kata_kunci' => ['makan', 'minum', 'jamuan', 'katering', 'catering', 'konsumsi', 'snack', 'nasi', 'rumah makan', 'jasa boga'],
            'jenis_pph' => 'PPh Pasal 23', 'tarif_pph' => 0.02, 'ppn' => 0, 'map_pph' => '411124', 'kjs_pph' => '104',
            'min_pph' => 0, 'min_ppn' => 0, 'catatan' => 'Makan minum bukan BKP, tidak kena PPN'],

        ['label' => 'Sewa Kapal',
            'kata_kunci' => ['sewa kapal', 'sewa perahu', 'charter kapal', 'sewa speedboat'],
            'jenis_pph' => 'PPh Pasal 23', 'tarif_pph' => 0.02, 'ppn' => 0.11, 'map_pph' => '411124', 'kjs_pph' => '104',
            'min_pph' => 0, 'min_ppn' => 1000000, 'catatan' => ''],

        ['label' => 'Sewa Kendaraan Darat',
            'kata_kunci' => ['sewa kendaraan', 'sewa mobil', 'sewa bus', 'rental', 'sewa motor'],
            'jenis_pph' => 'PPh Pasal 23', 'tarif_pph' => 0.02, 'ppn' => 0.11, 'map_pph' => '411124', 'kjs_pph' => '104',
            'min_pph' => 0, 'min_ppn' => 1000000, 'catatan' => ''],

        ['label' => 'Sewa Gedung / Ruangan',
            'kata_kunci' => ['sewa gedung', 'sewa ruangan', 'sewa aula', 'sewa tempat', 'sewa hotel', 'sewa kamar'],
            'jenis_pph' => 'PPh Final 4(2)', 'tarif_pph' => 0.10, 'ppn' => 0.11, 'map_pph' => '411128', 'kjs_pph' => '403',
            'min_pph' => 0, 'min_ppn' => 1000000, 'catatan' => 'Sewa tanah/bangunan'],

        ['label' => 'Perencanaan / Pengawasan Konstruksi',
            'kata_kunci' => ['perencanaan konstruksi', 'pengawasan konstruksi', 'konsultan perencana', 'konsultan pengawas', 'manajemen konstruksi', 'supervisi konstruksi', 'konsultan konstruksi'],
            'jenis_pph' => 'PPh Final 4(2)', 'tarif_pph' => 0.035, 'ppn' => 0.11, 'map_pph' => '411128', 'kjs_pph' => '409',
            'min_pph' => 0, 'min_ppn' => 1000000, 'catatan' => 'Perencana/pengawas konstruksi bersertifikat (PP 9/2022) - tanpa sertifikat 6%'],

        ['label' => 'Konstruksi (Pelaksana Menengah/Besar)',
            'kata_kunci' => ['konstruksi pt', 'kontraktor besar', 'konstruksi menengah', 'kualifikasi menengah', 'kualifikasi besar'],
            'jenis_pph' => 'PPh Final 4(2)', 'tarif_pph' => 0.0265, 'ppn' => 0.11, 'map_pph' => '411128', 'kjs_pph' => '409',
            'min_pph' => 0, 'min_ppn' => 1000000, 'catatan' => 'Pelaksana konstruksi kualifikasi menengah/besar (PP 9/2022)'],

        ['label' => 'Konstruksi / Renovasi (Pelaksana Kecil)',
            'kata_kunci' => ['konstruksi', 'renovasi', 'pembangunan', 'bangun', 'rehab', 'pemasangan'],
            'jenis_pph' => 'PPh Final 4(2)', 'tarif_pph' => 0.0175, 'ppn' => 0.11, 'map_pph' => '411128', 'kjs_pph' => '409',
            'min_pph' => 0, 'min_ppn' => 1000000, 'catatan' => 'Pelaksana konstruksi kualifikasi kecil (PP 9/2022) - tanpa sertifikat 4%'],

        ['label' => 'Jasa Servis / Perbaikan / Instalasi',
            'kata_kunci' => ['servis', 'service', 'perbaikan', 'instalasi', 'reparasi', 'perawatan ac'],
            'jenis_pph' => 'PPh Pasal 23', 'tarif_pph' => 0.02, 'ppn' => 0.11, 'map_pph' => '411124', 'kjs_pph' => '104',
            'min_pph' => 0, 'min_ppn' => 1000000, 'catatan' => ''],

        ['label' => 'Jasa Pemeliharaan / Kebersihan',
            'kata_kunci' => ['pemeliharaan', 'kebersihan', 'cleaning', 'perawatan', 'jasa kebersihan'],
            'jenis_pph' => 'PPh Pasal 23', 'tarif_pph' => 0.02, 'ppn' => 0.11, 'map_pph' => '411124', 'kjs_pph' => '104',
            'min_pph' => 0, 'min_ppn' => 1000000, 'catatan' => ''],

        ['label' => 'ATK / Alat Tulis',
            'kata_kunci' => ['atk', 'alat tulis', 'kertas', 'tinta', 'pulpen', 'map', 'spidol', 'amplop'],
            'jenis_pph' => 'PPh Pasal 22', 'tarif_pph' => 0.015, 'ppn' => 0.11, 'map_pph' => '411122', 'kjs_pph' => '910',
            'min_pph' => 2000000, 'min_ppn' => 1000000, 'catatan' => 'PPh 22 hanya bila >= Rp 2 juta'],

        ['label' => 'Peralatan / Barang Modal / Elektronik',
            'kata_kunci' => ['peralatan', 'elektronik', 'komputer', 'laptop', 'printer', 'mesin', 'barang modal', 'proyektor', 'kamera', 'ac '],
            'jenis_pph' => 'PPh Pasal 22', 'tarif_pph' => 0.015, 'ppn' => 0.11, 'map_pph' => '411122', 'kjs_pph' => '910',
            'min_pph' => 2000000, 'min_ppn' => 1000000, 'catatan' => 'PPh 22 hanya bila >= Rp 2 juta'],

        ['label' => 'Keperluan Kantor / Rumah Tangga',
            'kata_kunci' => ['keperluan kantor', 'rumah tangga', 'perlengkapan', 'alat kebersihan', 'barang habis pakai'],
            'jenis_pph' => 'PPh Pasal 22', 'tarif_pph' => 0.015, 'ppn' => 0.11, 'map_pph' => '411122', 'kjs_pph' => '910',
            'min_pph' => 2000000, 'min_ppn' => 1000000, 'catatan' => 'PPh 22 hanya bila >= Rp 2 juta'],

        ['label' => 'Obat / Kesehatan',
            'kata_kunci' => ['obat', 'kesehatan', 'medis', 'apotek', 'apotik', 'p3k', 'vitamin', 'masker'],
            'jenis_pph' => '', 'tarif_pph' => 0, 'ppn' => 0, 'map_pph' => '', 'kjs_pph' => '',
            'min_pph' => 0, 'min_ppn' => 0, 'catatan' => 'Tidak dipungut pajak'],

        ['label' => 'Transport / Perjalanan Dinas',
            'kata_kunci' => ['transport', 'perjalanan dinas', 'tiket', 'perjadin', 'taksi', 'ojek', 'grab', 'gojek', 'penginapan'],
            'jenis_pph' => '', 'tarif_pph' => 0, 'ppn' => 0, 'map_pph' => '', 'kjs_pph' => '',
            'min_pph' => 0, 'min_ppn' => 0, 'catatan' => 'Tidak dipungut pajak'],

        ['label' => 'Listrik / PLN / Komunikasi',
            'kata_kunci' => ['listrik', 'pln', 'telepon', 'pulsa', 'internet', 'komunikasi', 'token', 'wifi', 'indihome'],
            'jenis_pph' => '', 'tarif_pph' => 0, 'ppn' => 0, 'map_pph' => '', 'kjs_pph' => '',
            'min_pph' => 0, 'min_ppn' => 0, 'catatan' => 'Tidak dipungut pajak'],

        ['label' => 'Pakan / Bibit Perikanan',
            'kata_kunci' => ['pakan', 'bibit', 'benih', 'ikan', 'perikanan', 'pelet', 'udang', 'pupuk'],
            'jenis_pph' => '', 'tarif_pph' => 0, 'ppn' => 0, 'map_pph' => '', 'kjs_pph' => '',
            'min_pph' => 0, 'min_ppn' => 0, 'catatan' => 'Tidak dipungut pajak'],

        ['label' => 'Pendidikan / Pelatihan',
            'kata_kunci' => ['pendidikan', 'pelatihan', 'diklat', 'seminar', 'workshop', 'bimtek', 'sosialisasi'],
            'jenis_pph' => '', 'tarif_pph' => 0, 'ppn' => 0, 'map_pph' => '', 'kjs_pph' => '',
            'min_pph' => 0, 'min_ppn' => 0, 'catatan' => 'Tidak dipungut pajak'],

        ['label' => 'Honor Narasumber',
            'kata_kunci' => ['honor', 'narasumber', 'honorarium', 'fee', 'moderator'],
            'jenis_pph' => 'PPh Pasal 21', 'tarif_pph' => 0, 'ppn' => 0, 'map_pph' => '411121', 'kjs_pph' => '100',
            'min_pph' => 0, 'min_ppn' => 0, 'catatan' => 'Tarif sesuai tabel PPh 21 - hitung manual'],

    ],

    'default' => [
        'label' => 'Lain-lain (tanpa pajak otomatis)',
        'kata_kunci' => [],
        'jenis_pph' => '', 'tarif_pph' => 0, 'ppn' => 0, 'map_pph' => '', 'kjs_pph' => '',
        'min_pph' => 0, 'min_ppn' => 0, 'catatan' => 'Tidak terklasifikasi - periksa manual',
    ],

    // Kode billing setoran PPN (PPh sudah per-kategori di tiap entri di atas).
    // CATATAN: verifikasi KJS PPN terhadap regulasi terkini sebelum produksi.
    'ppn_ssp' => ['map' => '411211', 'kjs' => '920'],

];
