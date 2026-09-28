<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(fn () => Password::min(10)->letters()->mixedCase()->numbers());

        // Una copia de .env con APP_DEBUG=true nunca debe mostrar trazas ni
        // variables de entorno en producción.
        if ($this->app->isProduction()) {
            config(['app.debug' => false]);
        }
    }
}
