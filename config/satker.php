<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Satuan Kerja
    |--------------------------------------------------------------------------
    |
    | Dipakai pada kop & blok pengesahan dokumen cetak. JANGAN hardcode di Blade
    | — ubah di sini (atau via .env) agar dokumen menyesuaikan tanpa sentuh view.
    |
    */
    'kementerian' => env('SATKER_KEMENTERIAN', 'Kementerian Kelautan dan Perikanan'),
    'unit' => env('SATKER_UNIT', 'Badan Riset dan Sumber Daya Manusia Kelautan dan Perikanan'),
    'nama' => env('SATKER_NAMA', 'Politeknik Kelautan dan Perikanan Sorong'),
    'alamat' => env('SATKER_ALAMAT', 'Jl. Kapitan Pattimura, Tanjung Kasuari, Sorong, Papua Barat Daya'),
    'kota' => env('SATKER_KOTA', 'Sorong'),
    'telepon' => env('SATKER_TELEPON', ''),
    'website' => env('SATKER_WEBSITE', ''),

    // Pejabat penanda tangan.
    'ppk' => [
        'nama' => env('SATKER_PPK_NAMA', 'Firdaus Dabamona, S.T.'),
        'nip' => env('SATKER_PPK_NIP', ''),
        'jabatan' => 'Pejabat Pembuat Komitmen',
    ],
    'kpa' => [
        'nama' => env('SATKER_KPA_NAMA', ''),
        'nip' => env('SATKER_KPA_NIP', ''),
        'jabatan' => 'Kuasa Pengguna Anggaran',
    ],
    'bendahara' => [
        'nama' => env('SATKER_BENDAHARA_NAMA', ''),
        'nip' => env('SATKER_BENDAHARA_NIP', ''),
        'jabatan' => 'Bendahara Pengeluaran',
    ],

];
