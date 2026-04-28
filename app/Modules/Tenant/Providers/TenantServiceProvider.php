<?php

namespace App\Modules\Tenant\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/Tenant/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/Tenant/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/Tenant/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/tenant)
        //if (is_dir(resource_path('views/tenant'))) {
        //  $this->loadViewsFrom(resource_path('views/tenant'), 'tenant');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/Tenant/Routes/api.php'));
    }
}