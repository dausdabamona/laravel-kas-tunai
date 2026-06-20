<?php

namespace App\Policies;

use App\Enums\Role;
use App\Enums\StatusSpj;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Support\Periode;

/**
 * Kebijakan akses CRUD transaksi_kas.
 *
 * Prinsip:
 *  - Pemisahan tugas: operator TIDAK boleh menghapus record keuangan.
 *  - Period-locking MENGALAHKAN peran: transaksi pada periode terkunci tak
 *    bisa diubah/dihapus oleh peran apa pun (lihat config kas.periode_terkunci_hingga).
 *
 * Matriks (create | update | delete | read):
 *   operator  : ✓ | ✓ (hanya draft) | ✗ | ✓
 *   bendahara : ✓ | ✓               | ✓ | ✓
 *   ppk       : ✗ | ✗ (approval via ability terpisah) | ✗ | ✓
 *   pimpinan  : ✗ | ✗               | ✗ | ✓
 */
class TransaksiKasPolicy
{
    /**
     * Membaca daftar — semua peran boleh.
     */
    public function viewAny(User $user): bool
    {
        return $user->role !== null;
    }

    /**
     * Membaca satu record — semua peran boleh.
     */
    public function view(User $user, TransaksiKas $transaksi): bool
    {
        return $user->role !== null;
    }

    /**
     * Membuat transaksi — operator & bendahara.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [Role::Operator, Role::Bendahara], true);
    }

    /**
     * Mengubah transaksi.
     *
     * - bendahara: boleh selama periode tidak terkunci.
     * - operator : hanya draft (status SPJ belum) dan periode tidak terkunci.
     * - ppk/pimpinan: tidak (persetujuan SPBY/SPJ lewat ability terpisah).
     */
    public function update(User $user, TransaksiKas $transaksi): bool
    {
        if ($this->terkunci($transaksi)) {
            return false;
        }

        return match ($user->role) {
            Role::Bendahara => true,
            Role::Operator => $transaksi->status_spj === StatusSpj::Belum,
            default => false,
        };
    }

    /**
     * Menghapus (lunak) transaksi — hanya bendahara, dan periode tidak terkunci.
     */
    public function delete(User $user, TransaksiKas $transaksi): bool
    {
        if ($this->terkunci($transaksi)) {
            return false;
        }

        return $user->role === Role::Bendahara;
    }

    /**
     * Apakah transaksi berada di periode yang terkunci.
     * Sumber batas: App\Support\Periode (Pengaturan editable user).
     */
    protected function terkunci(TransaksiKas $transaksi): bool
    {
        return Periode::terkunci($transaksi->tanggal->format('Y-m-d'));
    }
}
