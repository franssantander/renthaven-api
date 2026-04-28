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
    App\Modules\RoomManagement\Providers\RoomManagementServiceProvider::class,
    App\Modules\TenantModule\Providers\TenantModuleServiceProvider::class,
    App\Modules\Tenant\Providers\TenantServiceProvider::class,
    App\Modules\Plans\Providers\PlansServiceProvider::class,
    App\Modules\Plan\Providers\PlanServiceProvider::class,
];
