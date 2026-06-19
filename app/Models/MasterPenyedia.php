<?php

namespace App\Models;

use Database\Factories\MasterPenyediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Master data penyedia (vendor).
 *
 * Bukan record transaksional: TANPA SoftDeletes, tanpa audit (jaga log tetap
 * sunyi). Frekuensi & terakhir_digunakan dipelihara PenyediaService untuk
 * mengurutkan autocomplete — yang sering dipakai muncul dulu.
 */
class MasterPenyedia extends Model
{
    /** @use HasFactory<MasterPenyediaFactory> */
    use HasFactory;

    protected $table = 'master_penyedia';

    protected $fillable = [
        'nama',
        'npwp',
        'alamat',
        'terakhir_digunakan',
        'frekuensi',
    ];

    protected function casts(): array
    {
        return [
            'terakhir_digunakan' => 'datetime',
            'frekuensi' => 'integer',
        ];
    }
}
