<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Saldo Awal Kas
    |--------------------------------------------------------------------------
    |
    | Saldo pembuka per sumber dana (Rp integer) saat migrasi dari sistem lama
    | (GAS). Saldo riil = saldo awal + Σ(debet − kredit) baris aktif sumber itu.
    | Disimpan di config, bukan kolom DB, agar tidak mengotori akumulasi delta.
    |
    */
    'saldo_awal_tunai' => (int) env('KAS_SALDO_AWAL_TUNAI', 0),
    'saldo_awal_bank' => (int) env('KAS_SALDO_AWAL_BANK', 0),

    /*
    |--------------------------------------------------------------------------
    | Period-Locking (penegakan minimal, UI penuh di Fase 6)
    |--------------------------------------------------------------------------
    |
    | Tanggal (Y-m-d) batas periode terkunci. Transaksi dengan tanggal <= nilai
    | ini TIDAK boleh diubah/dihapus oleh peran apa pun (period-locking
    | mengalahkan RBAC). null = tidak ada periode terkunci.
    |
    */
    'periode_terkunci_hingga' => env('KAS_PERIODE_TERKUNCI_HINGGA'),

];
