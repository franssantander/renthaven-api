<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Authentication\Providers\AuthenticationServiceProvider::class,
    App\Modules\PropertyManagement\Providers\PropertyManagementServiceProvider::class,
    App\Modules\Portfolio\Providers\PortfolioServiceProvider::class,
    App\Modules\TenantManagement\Providers\TenantManagementServiceProvider::class,
];
