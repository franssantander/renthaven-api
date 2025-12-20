<?php

namespace App\Modules\Authentication\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\Authentication\Database\Seeders\AuthenticationSeeder;

class AuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/Authentication/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/Authentication/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/Authentication/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/authentication)
        //if (is_dir(resource_path('views/authentication'))) {
        //  $this->loadViewsFrom(resource_path('views/authentication'), 'authentication');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/Authentication/Routes/api.php'));
    }
}