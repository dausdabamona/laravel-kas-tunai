<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Trait fondasi untuk seluruh model transaksional Kas Tunai.
 *
 * Menggabungkan dua aturan keras domain keuangan:
 *  - Tidak ada hard delete  -> SoftDeletes
 *  - Audit otomatis         -> LogsActivity (spatie/laravel-activitylog)
 *
 * Cukup `use Auditable;` pada model agar kedua perilaku aktif.
 */
trait Auditable
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * Konfigurasi log aktivitas standar: catat hanya kolom yang berubah,
     * dari daftar $fillable, dan jangan simpan log kosong.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
