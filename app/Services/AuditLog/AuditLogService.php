<?php

namespace App\Services\AuditLog;

use App\Data\AuditLog\AuditLogData;
use App\Enum\Role;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\UuidResolver;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelData\PaginatedDataCollection;

class AuditLogService
{
    /**
     * Paginated audit trail, visibility-scoped to the requesting user and
     * narrowed by the given filters (module, action, user_uuid, date range,
     * free-text search, and — for super admins — tenant_business_uuid).
     *
     * @param array<string, mixed> $filters
     */
    public function list(User $user, array $filters, int $perPage): PaginatedDataCollection
    {
        $query = AuditLog::query()->with(['actor.role', 'tenantBusiness']);

        $this->applyVisibilityScope($query, $user);

        $query
            ->when(!empty($filters['module']), fn ($q) => $q->where('module', $filters['module']))
            ->when(!empty($filters['action']), fn ($q) => $q->where('action', $filters['action']))
            ->when(!empty($filters['user_uuid']), function ($q) use ($filters) {
                $q->where('user_id', UuidResolver::id('users', $filters['user_uuid']));
            })
            ->when(!empty($filters['date_from']), fn ($q) => $q->where('created_at', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->where('created_at', '<=', $filters['date_to']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->where('description', 'like', "%{$filters['search']}%")
                        ->orWhere('actor_email', 'like', "%{$filters['search']}%");
                });
            });

        if ($this->isSuperAdmin($user) && !empty($filters['tenant_business_uuid'])) {
            $query->where('tenant_business_id', UuidResolver::id('tenant_businesses', $filters['tenant_business_uuid']));
        }

        $logs = $query
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);

        return AuditLogData::collect($logs, PaginatedDataCollection::class);
    }

    /**
     * The requesting user's own audit trail (self-serve, e.g. renters).
     */
    public function listOwn(User $user, int $perPage): PaginatedDataCollection
    {
        $logs = AuditLog::query()
            ->with(['actor.role', 'tenantBusiness'])
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);

        return AuditLogData::collect($logs, PaginatedDataCollection::class);
    }

    /**
     * Whether the given user is allowed to view the given log entry.
     */
    public function canView(User $user, AuditLog $log): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($user->role?->slug === Role::TENANT->value) {
            return $log->user_id === $user->id;
        }

        return $log->tenant_business_id === $user->tenant_business_id;
    }

    protected function applyVisibilityScope(Builder $query, User $user): void
    {
        if ($this->isSuperAdmin($user)) {
            return;
        }

        if ($user->role?->slug === Role::TENANT->value) {
            $query->where('user_id', $user->id);

            return;
        }

        $query->where('tenant_business_id', $user->tenant_business_id);
    }

    protected function isSuperAdmin(User $user): bool
    {
        return $user->role?->slug === Role::SUPER_ADMIN->value;
    }
}
