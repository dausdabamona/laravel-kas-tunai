<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
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
