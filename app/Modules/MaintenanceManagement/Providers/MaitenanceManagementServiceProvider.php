<?php

namespace App\Modules\MaitenanceManagement\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\MaitenanceManagement\Database\Seeders\MaitenanceManagementSeeder;

class MaitenanceManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/MaitenanceManagement/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/MaitenanceManagement/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/MaitenanceManagement/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/maitenance-management)
        //if (is_dir(resource_path('views/maitenance-management'))) {
        //  $this->loadViewsFrom(resource_path('views/maitenance-management'), 'maitenance-management');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/MaitenanceManagement/Routes/api.php'));
    }
}