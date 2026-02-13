<?php

namespace App\Modules\RoomManagement\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\RoomManagement\Database\Seeders\RoomManagementSeeder;

class RoomManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/RoomManagement/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/RoomManagement/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/RoomManagement/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/room-management)
        //if (is_dir(resource_path('views/room-management'))) {
        //  $this->loadViewsFrom(resource_path('views/room-management'), 'room-management');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/RoomManagement/Routes/api.php'));
    }
}