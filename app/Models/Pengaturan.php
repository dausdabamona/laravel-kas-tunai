<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Penyimpanan setting key-value yang dapat diubah pengguna (mis. batas periode
 * terkunci). Disertai cache ringan per-kunci. Bukan record transaksional —
 * tanpa SoftDeletes/audit.
 */
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $fillable = ['kunci', 'nilai'];

    private static function cacheKey(string $kunci): string
    {
        return "pengaturan:{$kunci}";
    }

    public static function get(string $kunci, ?string $default = null): ?string
    {
        $nilai = Cache::rememberForever(
            self::cacheKey($kunci),
            fn () => static::query()->where('kunci', $kunci)->value('nilai'),
        );

        return $nilai ?? $default;
    }

    public static function set(string $kunci, ?string $nilai): void
    {
        static::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        Cache::forget(self::cacheKey($kunci));
    }
}
