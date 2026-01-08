<?php

namespace App\Modules\Property\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\Property\Database\Seeders\PropertySeeder;

class PropertyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/Property/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/Property/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/Property/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/property)
        //if (is_dir(resource_path('views/property'))) {
        //  $this->loadViewsFrom(resource_path('views/property'), 'property');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/Property/Routes/api.php'));
    }
}