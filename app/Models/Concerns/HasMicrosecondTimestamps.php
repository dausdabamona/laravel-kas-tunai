<?php

namespace App\Models\Concerns;

/**
 * Simpan timestamp dengan presisi mikrodetik (default Eloquent: detik).
 *
 * Wajib di model yang ikut cascade soft-delete timestamp-matching: deleted_at
 * hapus-manual harus berbeda dari deleted_at cascade (yang menyalin timestamp
 * induk) agar restore selektif tidak bertabrakan dalam detik yang sama.
 *
 * Memakai hook initialize{Trait}() Eloquent (set $dateFormat per-instance),
 * bukan properti — karena Model sudah mendeklarasikan $dateFormat dan properti
 * trait dengan default berbeda menimbulkan konflik komposisi.
 */
trait HasMicrosecondTimestamps
{
    public function initializeHasMicrosecondTimestamps(): void
    {
        $this->dateFormat = 'Y-m-d H:i:s.u';
    }
}
