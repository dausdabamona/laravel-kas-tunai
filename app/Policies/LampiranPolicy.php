<?php

namespace App\Policies;

use App\Models\Lampiran;
use App\Models\User;

/**
 * Akses baca lampiran (bukti). Read terbuka untuk semua peran — gerbang
 * sesungguhnya adalah middleware auth + signed pada route stream.
 */
class LampiranPolicy
{
    public function view(User $user, Lampiran $lampiran): bool
    {
        return $user->role !== null;
    }
}
