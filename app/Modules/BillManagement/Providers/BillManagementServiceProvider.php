<?php

namespace App\Modules\BillManagement\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\BillManagement\Database\Seeders\BillManagementSeeder;

class BillManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/BillManagement/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/BillManagement/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/BillManagement/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/bill-management)
        //if (is_dir(resource_path('views/bill-management'))) {
        //  $this->loadViewsFrom(resource_path('views/bill-management'), 'bill-management');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/BillManagement/Routes/api.php'));
    }
}