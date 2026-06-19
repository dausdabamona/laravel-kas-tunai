<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\MultiNotaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rincian nota per penyedia untuk satu transaksi belanja.
 *
 * Record transaksional: SoftDeletes + Auditable (keduanya dari trait Auditable).
 * Cascade soft-delete dari induk dikelola di model event TransaksiKas, memakai
 * timestamp deleted_at yang identik agar restore selektif akurat.
 */
class MultiNota extends Model
{
    /** @use HasFactory<MultiNotaFactory> */
    use Auditable, HasFactory;

    protected $table = 'multi_nota';

    protected $fillable = [
        'transaksi_id',
        'urutan',
        'nama_penyedia',
        'nominal',
        'npwp_penyedia',
        'alamat_penyedia',
        'tgl_nota',
        'penyedia_id',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'tgl_nota' => 'date',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TransaksiKas::class, 'transaksi_id');
    }

    public function masterPenyedia(): BelongsTo
    {
        return $this->belongsTo(MasterPenyedia::class, 'penyedia_id');
    }
}
