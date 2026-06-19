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

    /**
     * Simpan timestamp dengan presisi mikrodetik.
     *
     * Penting untuk cascade restore selektif: deleted_at hapus-manual harus
     * berbeda dari deleted_at cascade (yang menyalin timestamp induk presisi
     * detik). Tanpa ini Eloquent menulis 'Y-m-d H:i:s' (detik) dan dua
     * penghapusan dalam detik sama akan bertabrakan.
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';

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
