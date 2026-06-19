<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\Lampiran;
use App\Models\MultiNota;
use App\Models\Pengembalian;
use App\Models\SuratTugas;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerGates();

        // Peta morph: simpan ALIAS pendek di DB, bukan FQCN. enforceMorphMap
        // melempar bila ada model morphable tak terpetakan — termasuk subjek
        // activitylog (transaksi/nota/lampiran) DAN causer (user) — sehingga
        // tak ada FQCN bocor ke kolom *_type.
        Relation::enforceMorphMap([
            'transaksi' => TransaksiKas::class,
            'nota' => MultiNota::class,
            'lampiran' => Lampiran::class,
            'pengembalian' => Pengembalian::class,
            'suratTugas' => SuratTugas::class,
            'user' => User::class,
        ]);

        // Deteksi N+1 saat pengembangan: lempar error bila relasi di-lazy-load.
        Model::preventLazyLoading(! $this->app->isProduction());
    }

    /**
     * Daftarkan Gate RBAC dari pemetaan peran -> kemampuan pada enum Role.
     *
     * Sumber kebenaran tunggal ada di App\Enums\Role::abilities().
     */
    protected function registerGates(): void
    {
        foreach (Role::allAbilities() as $ability) {
            Gate::define($ability, fn (User $user) => $user->role?->can($ability) ?? false);
        }
    }
}
