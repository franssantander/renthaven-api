<?php

namespace App\Modules\TenantManagement\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\TenantManagement\Database\Seeders\TenantManagementSeeder;

class TenantManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/TenantManagement/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/TenantManagement/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/TenantManagement/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/tenant-management)
        //if (is_dir(resource_path('views/tenant-management'))) {
        //  $this->loadViewsFrom(resource_path('views/tenant-management'), 'tenant-management');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/TenantManagement/Routes/api.php'));
    }
}