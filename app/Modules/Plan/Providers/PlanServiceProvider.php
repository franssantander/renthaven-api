<?php

namespace App\Modules\Plan\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\Plan\Database\Seeders\PlanSeeder;

class PlanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/Plan/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/Plan/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/Plan/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/plan)
        //if (is_dir(resource_path('views/plan'))) {
        //  $this->loadViewsFrom(resource_path('views/plan'), 'plan');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/Plan/Routes/api.php'));
    }
}