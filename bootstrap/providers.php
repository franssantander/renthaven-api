<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Authentication\Providers\AuthenticationServiceProvider::class,
    App\Modules\Portfolio\Providers\PortfolioServiceProvider::class,
    App\Modules\TenantManagement\Providers\TenantManagementServiceProvider::class,
    App\Modules\Property\Providers\PropertyServiceProvider::class,
    App\Modules\MaintenanceManagement\Providers\MaintenanceManagementServiceProvider::class,
    App\Modules\UserManagement\Providers\UserManagementServiceProvider::class,
    App\Modules\BillManagement\Providers\BillManagementServiceProvider::class,
];
