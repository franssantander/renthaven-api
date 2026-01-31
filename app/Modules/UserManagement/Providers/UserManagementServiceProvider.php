<?php

namespace App\Modules\UserManagement\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\UserManagement\Database\Seeders\UserManagementSeeder;

class UserManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/UserManagement/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/UserManagement/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/UserManagement/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/user-management)
        //if (is_dir(resource_path('views/user-management'))) {
        //  $this->loadViewsFrom(resource_path('views/user-management'), 'user-management');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/UserManagement/Routes/api.php'));
    }
}