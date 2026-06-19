<?php

namespace App\Models;

use App\Enums\KategoriLampiran;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\LampiranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Lampiran bukti polymorphic — menempel ke transaksi ATAU nota.
 *
 * Bukti keuangan: layak diaudit (Auditable). Presisi mikrodetik agar ikut
 * cascade restore selektif yang akurat. URL tidak dipersist — signed URL
 * sementara dibangkitkan saat baca di slice 2.4.
 */
class Lampiran extends Model
{
    /** @use HasFactory<LampiranFactory> */
    use Auditable, HasFactory, HasMicrosecondTimestamps;

    protected $table = 'lampiran';

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'kategori',
        'urutan',
        'disk',
        'path',
        'nama_file',
        'mime',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'kategori' => KategoriLampiran::class,
            'meta' => 'array',
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
