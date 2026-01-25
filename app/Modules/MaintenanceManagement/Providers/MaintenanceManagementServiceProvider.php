<?php

namespace App\Modules\MaintenanceManagement\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\MaintenanceManagement\Database\Seeders\MaintenanceManagementSeeder;

class MaintenanceManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/MaintenanceManagement/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/MaintenanceManagement/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/MaintenanceManagement/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/maintenance-management)
        //if (is_dir(resource_path('views/maintenance-management'))) {
        //  $this->loadViewsFrom(resource_path('views/maintenance-management'), 'maintenance-management');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/MaintenanceManagement/Routes/api.php'));
    }
}