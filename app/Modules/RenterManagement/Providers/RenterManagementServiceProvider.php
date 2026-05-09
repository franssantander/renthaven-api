<?php

namespace App\Modules\RenterManagement\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RenterManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/RenterManagement/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/RenterManagement/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/RenterManagement/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/renter-management)
        //if (is_dir(resource_path('views/renter-management'))) {
        //  $this->loadViewsFrom(resource_path('views/renter-management'), 'renter-management');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/RenterManagement/Routes/api.php'));
    }
}