<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\CascadesSoftDeletes;
use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Services\NotaService;
use Database\Factories\MultiNotaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
    use Auditable, CascadesSoftDeletes, HasFactory, HasMicrosecondTimestamps;

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

    protected static function booted(): void
    {
        // Setiap nota berubah -> hitung ulang status_spj induk.
        // Null-guard: bila induk ter-soft-delete/cascade, $n->transaksi null -> lewati.
        // (Cascade nota lewat bulk update yang TIDAK memicu event ini — benar:
        //  status induk hanya relevan saat induk aktif.)
        $recalc = fn (self $n) => optional($n->transaksi, fn ($t) => app(NotaService::class)->recalc($t));

        static::saved($recalc);
        static::deleted($recalc);
        static::restored($recalc);
    }

    /**
     * Lampiran (foto_nota) ikut cascade saat nota dihapus/di-restore.
     *
     * @return list<string>
     */
    protected function cascadeRelations(): array
    {
        return ['lampiran'];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TransaksiKas::class, 'transaksi_id');
    }

    public function masterPenyedia(): BelongsTo
    {
        return $this->belongsTo(MasterPenyedia::class, 'penyedia_id');
    }

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'attachable');
    }
}
