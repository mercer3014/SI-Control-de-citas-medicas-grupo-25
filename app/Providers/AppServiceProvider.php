<?php

namespace App\Providers;

use App\Services\InterfazPasarela;
use App\Services\PasarelaPagoService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(InterfazPasarela::class, PasarelaPagoService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El proyecto resuelve las vistas en resources/js/Pages (mayúscula).
        // El default de Inertia es js/pages y en Linux no coincide.
        config(['inertia.pages.paths' => [resource_path('js/Pages')]]);

        if (app()->environment('production') || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}
