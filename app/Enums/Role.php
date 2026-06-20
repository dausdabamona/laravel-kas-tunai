<?php

namespace App\Enums;

/**
 * Peran pengguna aplikasi Kas Tunai.
 *
 * Menjadi satu-satunya sumber kebenaran untuk pemetaan peran -> kemampuan (ability).
 * Gate didaftarkan dari pemetaan ini di AppServiceProvider, sehingga penambahan
 * kemampuan pada fase berikutnya cukup diubah di sini.
 */
enum Role: string
{
    case Operator = 'operator';
    case Bendahara = 'bendahara';
    case Ppk = 'ppk';
    case Pimpinan = 'pimpinan';

    /**
     * Label tampilan dalam Bahasa Indonesia (untuk UI).
     */
    public function label(): string
    {
        return match ($this) {
            self::Operator => 'Operator',
            self::Bendahara => 'Bendahara',
            self::Ppk => 'PPK',
            self::Pimpinan => 'Pimpinan',
        };
    }

    /**
     * Daftar kemampuan (Gate ability) yang dimiliki peran ini.
     *
     * Pemetaan awal fondasi RBAC — disempurnakan per fase saat fitur terkait dibuat.
     *
     * @return list<string>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::Operator => [
                'input-transaksi',
                'lihat-laporan',
            ],
            self::Bendahara => [
                'input-transaksi',
                'verifikasi-transaksi',
                'pindah-dana',
                'impor-bank',
                'perjalanan-dinas',
                'kunci-periode',
                'lihat-laporan',
            ],
            self::Ppk => [
                'setujui-spj',
                'kelola-pengguna',
                'perjalanan-dinas',
                'kunci-periode',
                'lihat-laporan',
            ],
            self::Pimpinan => [
                'lihat-laporan',
            ],
        };
    }

    /**
     * Apakah peran ini memiliki kemampuan tertentu.
     */
    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities(), true);
    }

    /**
     * Seluruh kemampuan unik yang dikenal sistem (gabungan semua peran).
     *
     * @return list<string>
     */
    public static function allAbilities(): array
    {
        return collect(self::cases())
            ->flatMap(fn (self $role) => $role->abilities())
            ->unique()
            ->values()
            ->all();
    }
}
