<?php

namespace App\Models\Concerns;

use App\Support\Cascade;

/**
 * Cascade soft-delete dengan timestamp-matching untuk restore selektif.
 *
 * Saat induk di-soft-delete, anak yang masih aktif ikut di-soft-delete dengan
 * deleted_at PERSIS sama dengan induk. Saat induk di-restore, HANYA anak yang
 * deleted_at-nya == induk yang dibangkitkan — anak yang dihapus manual lebih
 * dulu (timestamp berbeda) tetap terhapus.
 *
 * Mensyaratkan presisi mikrodetik pada anak (lihat HasMicrosecondTimestamps)
 * agar hapus-manual & cascade tak berbagi timestamp detik yang sama.
 *
 * Mekanisme bulk timestamp-match dipusatkan di App\Support\Cascade.
 */
trait CascadesSoftDeletes
{
    /**
     * Daftar nama relasi (hasMany/morphMany) yang ikut cascade.
     *
     * @return list<string>
     */
    abstract protected function cascadeRelations(): array;

    protected static function bootCascadesSoftDeletes(): void
    {
        // deleted: deleted_at induk SUDAH terisi di sini.
        static::deleted(function ($model) {
            if ($model->isForceDeleting()) {
                return;
            }

            foreach ($model->cascadeRelations() as $rel) {
                Cascade::softDelete($model->{$rel}(), $model->deleted_at);
            }
        });

        // restoring: deleted_at induk masih terisi (restore() mennull-kannya setelah event ini).
        static::restoring(function ($model) {
            foreach ($model->cascadeRelations() as $rel) {
                Cascade::restore($model->{$rel}(), $model->deleted_at);
            }
        });
    }
}
