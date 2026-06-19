<?php

namespace App\Models;

use App\Enums\JenisTransaksi;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\CascadesSoftDeletes;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Database\Factories\TransaksiKasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class TransaksiKas extends Model
{
    /** @use HasFactory<TransaksiKasFactory> */
    use Auditable, CascadesSoftDeletes, HasFactory, HasMicrosecondTimestamps;

    protected $table = 'transaksi_kas';

    protected $fillable = [
        'no',
        'tanggal',
        'kegiatan',
        'keterangan',
        'penjab',
        'debet',
        'kredit',
        'sumber',
        'jenis',
        'parent_id',
        'ref_group',
        'status_spj',
        'no_spby',
        'tgl_spby',
        'nilai_spby',
        'uang_diserahkan',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tgl_spby' => 'date',
            'debet' => 'integer',
            'kredit' => 'integer',
            'nilai_spby' => 'integer',
            'uang_diserahkan' => 'integer',
            'sumber' => Sumber::class,
            'jenis' => JenisTransaksi::class,
            'status_spj' => StatusSpj::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->no)) {
                $model->no = self::generateNomor($model->tanggal?->year ?? now()->year);
            }

            // Akuntabilitas: catat penginput bila belum diisi eksplisit.
            // Baris sistem (mis. impor bank) boleh mengeset null secara sengaja.
            if (! $model->isDirty('dibuat_oleh') && auth()->check()) {
                $model->dibuat_oleh = auth()->id();
            }
        });
    }

    /**
     * Relasi yang ikut cascade soft-delete.
     *
     * Keputusan (b): cascade hanya menjangkau nota anak + lampiran LANGSUNG
     * transaksi. Lampiran nota (foto_nota cucu) TIDAK ikut — cascade nota lewat
     * bulk update yang tak memicu event, jadi foto_nota tetap aktif.
     *
     * @return list<string>
     */
    protected function cascadeRelations(): array
    {
        return ['nota', 'lampiran'];
    }

    /**
     * Generate nomor urut KAS-{tahun}-{seq 4 digit}.
     * Memakai SELECT FOR UPDATE agar aman saat insert bersamaan.
     */
    private static function generateNomor(int $tahun): string
    {
        return DB::transaction(function () use ($tahun) {
            $terakhir = self::withTrashed()
                ->whereYear('tanggal', $tahun)
                ->lockForUpdate()
                ->count();

            $seq = str_pad($terakhir + 1, 4, '0', STR_PAD_LEFT);

            return "KAS-{$tahun}-{$seq}";
        });
    }

    // ── Relasi ──────────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function anak(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function nota(): HasMany
    {
        return $this->hasMany(MultiNota::class, 'transaksi_id');
    }

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'attachable');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    // ── Scope ───────────────────────────────────────────────────────────────

    public function scopePerSumber(Builder $query, Sumber $sumber): Builder
    {
        return $query->where('sumber', $sumber);
    }

    public function scopeRentangTanggal(Builder $query, ?string $dari, ?string $sampai): Builder
    {
        return $query
            ->when($dari, fn (Builder $q) => $q->whereDate('tanggal', '>=', $dari))
            ->when($sampai, fn (Builder $q) => $q->whereDate('tanggal', '<=', $sampai));
    }
}
