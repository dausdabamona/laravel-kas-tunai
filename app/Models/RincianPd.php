<?php

namespace App\Models;

use App\Enums\KomponenBiaya;
use App\Enums\PembayarPd;
use App\Enums\Sumber;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Rincian biaya perjalanan dinas per pegawai (satu komponen per baris).
 *
 * nominal disimpan (qty × harga_satuan) agar laporan tak menghitung ulang.
 * pembayar + metode menentukan pengelompokan kuitansi rampung & bucket kas.
 */
class RincianPd extends Model
{
    use Auditable, HasMicrosecondTimestamps;

    protected $table = 'rincian_pd';

    protected $fillable = [
        'surat_tugas_id',
        'pegawai_index',
        'pegawai_nama',
        'komponen',
        'uraian',
        'qty',
        'harga_satuan',
        'nominal',
        'pembayar',
        'metode',
        'keterangan',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'pegawai_index' => 'integer',
            'qty' => 'integer',
            'harga_satuan' => 'integer',
            'nominal' => 'integer',
            'komponen' => KomponenBiaya::class,
            'pembayar' => PembayarPd::class,
            'metode' => Sumber::class,
        ];
    }

    public function suratTugas(): BelongsTo
    {
        return $this->belongsTo(SuratTugas::class, 'surat_tugas_id');
    }

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'attachable');
    }
}
