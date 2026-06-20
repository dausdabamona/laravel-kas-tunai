<?php

namespace App\Models;

use App\Enums\JenisTransaksi;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\CascadesSoftDeletes;
use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Support\Cascade;
use Database\Factories\TransaksiKasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

        // Cascade pasangan ber-ref_group (pindah dana & porsi PD) agar buku tak
        // timpang. Beda GAS yang menghapus per-baris.
        static::deleted(function (self $t) {
            if ($t->isForceDeleting() || ! self::pasanganRefGroup($t)) {
                return;
            }

            Cascade::softDelete(
                static::where('ref_group', $t->ref_group)->whereKeyNot($t->id),
                $t->deleted_at,
            );
        });

        static::restoring(function (self $t) {
            if (! self::pasanganRefGroup($t)) {
                return;
            }

            Cascade::restore(
                static::where('ref_group', $t->ref_group)->whereKeyNot($t->id),
                $t->deleted_at,
            );
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
        return ['nota', 'lampiran', 'suratTugas'];
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

    public function pengembalian(): HasMany
    {
        return $this->hasMany(Pengembalian::class, 'transaksi_id');
    }

    public function tambahan(): HasMany
    {
        return $this->hasMany(Tambahan::class, 'transaksi_id');
    }

    public function suratTugas(): HasOne
    {
        return $this->hasOne(SuratTugas::class, 'transaksi_id');
    }

    /**
     * Apakah baris ini bagian dari pasangan ber-ref_group yang ikut cascade.
     */
    private static function pasanganRefGroup(self $t): bool
    {
        return $t->ref_group && in_array($t->jenis, [
            JenisTransaksi::PindahDana,
            JenisTransaksi::PdPokok,
            JenisTransaksi::PdBendahara,
        ], true);
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
