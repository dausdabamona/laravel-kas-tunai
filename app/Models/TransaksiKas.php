<?php

namespace App\Models;

use App\Enums\JenisTransaksi;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Models\Concerns\Auditable;
use Database\Factories\TransaksiKasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class TransaksiKas extends Model
{
    /** @use HasFactory<TransaksiKasFactory> */
    use Auditable, HasFactory;

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

        // Cascade soft-delete ke nota anak. Pakai event "deleted" (deleted_at induk
        // SUDAH terisi), dan set anak ke timestamp induk PERSIS agar restore selektif.
        static::deleted(function (self $t) {
            if ($t->isForceDeleting()) {
                return;
            }

            $t->nota()->whereNull('deleted_at')
                ->update(['deleted_at' => $t->deleted_at]);
        });

        // Restore HANYA anak yang ikut terhapus oleh cascade (deleted_at == induk).
        // Nota yang dihapus manual lebih dulu punya timestamp beda -> tetap terhapus.
        static::restoring(function (self $t) {
            $ts = $t->deleted_at;

            $t->nota()->onlyTrashed()->where('deleted_at', $ts)
                ->update(['deleted_at' => null]);
        });
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
