<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Authentication\Providers\AuthenticationServiceProvider::class,
    App\Modules\Portfolio\Providers\PortfolioServiceProvider::class,
    App\Modules\TenantManagement\Providers\TenantManagementServiceProvider::class,
    App\Modules\Property\Providers\PropertyServiceProvider::class,
];
