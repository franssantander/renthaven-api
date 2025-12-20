<?php

namespace App\Modules\PropertyManagement\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\PropertyManagement\Database\Seeders\PropertyManagementSeeder;

class PropertyManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/PropertyManagement/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/PropertyManagement/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/PropertyManagement/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/property-management)
        //if (is_dir(resource_path('views/property-management'))) {
        //  $this->loadViewsFrom(resource_path('views/property-management'), 'property-management');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/PropertyManagement/Routes/api.php'));
    }
}