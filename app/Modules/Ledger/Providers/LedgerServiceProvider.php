<?php

namespace App\Modules\Ledger\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Database\Seeders\DatabaseSeeder;
use App\Modules\Ledger\Database\Seeders\LedgerSeeder;

class LedgerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations here
    }

    public function boot(): void
    {
        // Load module routes
        parent::boot();

        if(file_exists(app_path("Modules/Ledger/Routes/api.php"))) {
        //  Map api routes
            $this->mapApiRoutes();
        }

        if(file_exists(app_path("Modules/Ledger/Database/Migrations"))){
               $this->loadMigrationsFrom(app_path("Modules/Ledger/Database/Migrations"));
        }

        // Map a view namespace for this module (resources/views/ledger)
        //if (is_dir(resource_path('views/ledger'))) {
        //  $this->loadViewsFrom(resource_path('views/ledger'), 'ledger');
        //}
    }

    protected function mapApiRoutes():void{
        Route::prefix('api')
            ->middleware('api')
            ->group(app_path('Modules/Ledger/Routes/api.php'));
    }
}