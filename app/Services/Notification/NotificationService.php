<?php

namespace App\Services\Notification;

use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Enum\Role;
use App\Events\NotificationCreated;
use App\Models\AuditLog;
use App\Models\Lease;
use App\Models\LedgerEntry;
use App\Models\MaintenanceRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Modules whose audit entries never produce notifications (login noise etc.).
     *
     * @var AuditModule[]
     */
    protected array $mutedModules = [
        AuditModule::AUTH,
    ];

    /**
     * Only these event actions trigger a notification.
     *
     * @var AuditAction[]
     */
    protected array $notifiableActions = [
        AuditAction::CREATED,
        AuditAction::UPDATED,
        AuditAction::DELETED,
    ];

    /**
     * Fan an audit log entry out into per-recipient notification rows.
     *
     * Scope rules, enforced by recipient targeting:
     *  - super admin actions are visible only to other super admins;
     *  - admin/staff actions notify co-admins/staff of the same business, plus
     *    the affected renter when the record belongs to their lease;
     *  - tenant (renter) actions notify the admins/staff of their business.
     *
     * @return Collection<int, Notification>
     */
    public function dispatchFromAuditLog(AuditLog $log): Collection
    {
        if (!$this->shouldNotify($log)) {
            return collect();
        }

        // A just-created log gets its uuid from the DB default, so the in-memory
        // instance may not carry it yet — reload once before building payloads.
        if ($log->uuid === null) {
            $log->refresh();
        }

        $actor = $log->actor()->with('role')->withoutGlobalScope('tenant_business')->first();

        if (!$actor) {
            return collect();
        }

        return $this->resolveRecipients($log, $actor)
            ->map(fn (User $recipient) => $this->createFor($recipient, $log, $actor));
    }

    protected function shouldNotify(AuditLog $log): bool
    {
        return !in_array($log->module, array_column($this->mutedModules, 'value'), true)
            && in_array($log->action, array_column($this->notifiableActions, 'value'), true);
    }

    /**
     * @return Collection<int, User>
     */
    protected function resolveRecipients(AuditLog $log, User $actor): Collection
    {
        $actorRole = $actor->role?->slug;

        // Super admin activity stays between super admins — admins, staff and
        // tenants must never receive it.
        if ($actorRole === Role::SUPER_ADMIN->value) {
            return $this->usersWithRoles([Role::SUPER_ADMIN])
                ->where('id', '!=', $actor->id)
                ->values();
        }

        // A renter's action notifies the admins/staff of their business.
        if ($actorRole === Role::TENANT->value) {
            return $this->usersWithRoles([Role::ADMIN, Role::STAFF], $log->tenant_business_id);
        }

        // Admin/staff action: co-admins/staff of the same business (minus the
        // actor), plus the affected renter when the record belongs to them.
        $recipients = $this->usersWithRoles([Role::ADMIN, Role::STAFF], $log->tenant_business_id)
            ->where('id', '!=', $actor->id)
            ->values();

        if ($renterUser = $this->resolveAffectedRenterUser($log)) {
            if ($renterUser->id !== $actor->id) {
                $recipients->push($renterUser);
            }
        }

        return $recipients->unique('id')->values();
    }

    /**
     * @param Role[] $roles
     * @return Collection<int, User>
     */
    protected function usersWithRoles(array $roles, ?int $tenantBusinessId = null): Collection
    {
        return User::query()
            ->withoutGlobalScope('tenant_business')
            ->whereHas('role', fn ($q) => $q->whereIn('slug', array_column($roles, 'value')))
            ->when($tenantBusinessId !== null, fn ($q) => $q->where('tenant_business_id', $tenantBusinessId))
            ->get();
    }

    /**
     * When the audited record belongs to a renter (their ledger entry, lease,
     * or maintenance request), resolve that renter's portal user so they get
     * notified too (e.g. "your payment was approved").
     */
    protected function resolveAffectedRenterUser(AuditLog $log): ?User
    {
        $auditable = $log->auditable;

        $renter = match (true) {
            $auditable instanceof LedgerEntry,
            $auditable instanceof MaintenanceRequest,
            $auditable instanceof Lease => $auditable->renter,
            default => null,
        };

        return $renter?->user;
    }

    protected function createFor(User $recipient, AuditLog $log, User $actor): Notification
    {
        $notification = Notification::create([
            'user_id'            => $recipient->id,
            'tenant_business_id' => $log->tenant_business_id,
            'audit_log_id'       => $log->id,
            'module'             => $log->module,
            'action'             => $log->action,
            'title'              => Str::headline($log->module) . ' ' . Str::headline($log->action),
            'message'            => $log->description,
            'data'               => [
                'audit_log_uuid' => $log->uuid,
                'actor_uuid'     => $actor->uuid,
                'actor_name'     => $actor->full_name,
                'actor_role'     => $actor->role?->slug,
                'auditable_type' => $log->auditable_type ? class_basename($log->auditable_type) : null,
                'auditable_id'   => $log->auditable_id,
            ],
        ]);

        // Websocket hook: NotificationCreated will broadcast to the recipient's
        // private channel once ShouldBroadcast is implemented on the event.
        NotificationCreated::dispatch($notification);

        return $notification;
    }
}
