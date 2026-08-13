<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\URL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

/**
 * Configuracion global de la aplicacion SISARST.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
{
    if (app()->environment('production')) {
        URL::forceScheme('https');
    }
}
}
