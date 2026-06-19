<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Primitif cascade soft-delete dengan timestamp-matching.
 *
 * Satu sumber kebenaran untuk dua operasi yang dulu tersebar inline:
 *  - softDelete: hapus-lunak baris aktif, set deleted_at = timestamp induk PERSIS.
 *  - restore   : bangkitkan HANYA baris yang deleted_at-nya == timestamp induk
 *                (withTrashed tertanam permanen agar tak terjegal global scope).
 *
 * Memakai BULK update -> melewati event model anak (tak ada audit/recalc untuk
 * perubahan cascade), konsisten dengan keputusan jaga-log-sunyi.
 *
 * $query boleh berupa Eloquent Builder atau Relation (keduanya proxy ke query).
 */
class Cascade
{
    /**
     * @param  Builder|Relation  $query
     */
    public static function softDelete($query, mixed $deletedAt): void
    {
        $query->whereNull('deleted_at')->update(['deleted_at' => $deletedAt]);
    }

    /**
     * @param  Builder|Relation  $query
     */
    public static function restore($query, mixed $deletedAt): void
    {
        $query->withTrashed()->where('deleted_at', $deletedAt)->update(['deleted_at' => null]);
    }
}
