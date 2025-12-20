<?php

namespace App\Modules\Portfolio\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\Portfolio\Database\Seeders\PortfolioSeeder;

class PortfolioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/Portfolio/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/Portfolio/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/Portfolio/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/portfolio)
        //if (is_dir(resource_path('views/portfolio'))) {
        //  $this->loadViewsFrom(resource_path('views/portfolio'), 'portfolio');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/Portfolio/Routes/api.php'));
    }
}