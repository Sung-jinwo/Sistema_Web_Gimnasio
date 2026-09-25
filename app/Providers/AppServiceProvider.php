<?php

namespace App\Providers;

use App\Models\Membresia;
use App\Models\Producto;
use App\Models\User;
use App\Policies\MembresiaPolicy;
use App\Policies\ProductoPolicy;
use App\Policies\UsuarioPolicy;
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
        Gate::policy(Membresia::class, MembresiaPolicy::class);
        Gate::policy(Producto::class, ProductoPolicy::class);
        Gate::policy(User::class, UsuarioPolicy::class);
    }
}
