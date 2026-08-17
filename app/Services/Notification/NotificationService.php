<?php

namespace App\Services\Notification;

use App\Enum\NotificationType;
use App\Enum\Role;
use App\Events\NotificationCreated;
use App\Models\AuditLog;
use App\Models\Lease;
use App\Models\LedgerEntry;
use App\Models\MaintenanceRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Fan an audit log entry out into per-recipient notification rows.
     *
     * Only audit entries explicitly tagged with a NotificationType fan out.
     * Operational alerts stay within the tenant business, while payment
     * decisions are delivered only to the affected renter.
     *
     * @return Collection<int, Notification>
     */
    public function dispatchFromAuditLog(AuditLog $log): Collection
    {
        $type = NotificationType::tryFrom($log->context['notification_type'] ?? '');

        if (! $type) {
            return collect();
        }

        // A just-created log gets its uuid from the DB default, so the in-memory
        // instance may not carry it yet — reload once before building payloads.
        if ($log->uuid === null) {
            $log->refresh();
        }

        $actor = $log->actor()->with('role')->withoutGlobalScope('tenant_business')->first();

        if (! $actor || ! $this->actorCanDispatch($actor, $type)) {
            return collect();
        }

        return $this->resolveRecipients($log, $actor, $type)
            ->map(fn (User $recipient) => $this->createFor($recipient, $log, $actor, $type));
    }

    protected function actorCanDispatch(User $actor, NotificationType $type): bool
    {
        $role = $actor->role?->slug;

        if (in_array($type, [NotificationType::MAINTENANCE_SUBMITTED, NotificationType::PAYMENT_SUBMITTED], true)) {
            return $role === Role::TENANT->value;
        }

        return in_array($role, [Role::ADMIN->value, Role::STAFF->value], true);
    }

    /**
     * @return Collection<int, User>
     */
    protected function resolveRecipients(AuditLog $log, User $actor, NotificationType $type): Collection
    {
        if ($type->targetsRenter()) {
            $renter = $this->resolveAffectedRenterUser($log);

            return $renter ? collect([$renter]) : collect();
        }

        return $this->usersWithRoles([Role::ADMIN, Role::STAFF], $log->tenant_business_id)
            ->where('id', '!=', $actor->id)
            ->values();
    }

    /**
     * @param  Role[]  $roles
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

    protected function createFor(User $recipient, AuditLog $log, User $actor, NotificationType $type): Notification
    {
        [$title, $message] = $this->contentFor($log, $actor, $type);

        $notification = Notification::create([
            'user_id' => $recipient->id,
            'tenant_business_id' => $log->tenant_business_id,
            'audit_log_id' => $log->id,
            'module' => $log->module,
            'action' => $log->action,
            'title' => $title,
            'message' => $message,
            'data' => [
                'notification_type' => $type->value,
                'audit_log_uuid' => $log->uuid,
                'actor_uuid' => $actor->uuid,
                'actor_name' => $actor->full_name,
                'actor_role' => $actor->role?->slug,
                'auditable_type' => $log->auditable_type ? class_basename($log->auditable_type) : null,
                'auditable_id' => $log->auditable_id,
            ],
        ]);

        // Broadcast the persisted representation to the recipient's channel.
        NotificationCreated::dispatch($notification);

        return $notification;
    }

    /** @return array{string, string} */
    protected function contentFor(AuditLog $log, User $actor, NotificationType $type): array
    {
        $auditable = $log->auditable;

        return match ($type) {
            NotificationType::MAINTENANCE_SUBMITTED => [
                'New maintenance request',
                sprintf('%s submitted “%s”.', $auditable?->renter?->full_name ?? $actor->full_name, $auditable?->title ?? 'a maintenance request'),
            ],
            NotificationType::PAYMENT_SUBMITTED => [
                'Payment submitted',
                sprintf('%s submitted a payment of ₱%s for review.', $auditable?->renter?->full_name ?? $actor->full_name, $this->money($auditable?->submitted_amount)),
            ],
            NotificationType::PAYMENT_ACCEPTED => [
                'Payment accepted',
                sprintf('Your payment of ₱%s was accepted.', $this->money($this->paymentAmount($log))),
            ],
            NotificationType::PAYMENT_REJECTED => [
                'Payment rejected',
                $this->rejectionMessage($log),
            ],
            NotificationType::USER_CREATED => [
                'New user added',
                sprintf('%s added %s.', $actor->full_name, $auditable?->email ?? 'a new user'),
            ],
            NotificationType::TENANT_ASSIGNED => [
                'New tenant assigned',
                sprintf('%s was assigned to %s.', $auditable?->renter?->full_name ?? 'A tenant', $auditable?->propertyUnit?->name ?? 'a property unit'),
            ],
        };
    }

    protected function paymentAmount(AuditLog $log): float
    {
        $newAmount = (float) ($log->new_values['amount_paid'] ?? 0);
        $oldAmount = (float) ($log->old_values['amount_paid'] ?? 0);

        return max(0, $newAmount - $oldAmount);
    }

    protected function rejectionMessage(AuditLog $log): string
    {
        $amount = $this->money($log->old_values['submitted_amount'] ?? null);
        $reason = trim((string) ($log->context['reason'] ?? ''));

        return $reason === ''
            ? "Your payment of ₱{$amount} was rejected."
            : "Your payment of ₱{$amount} was rejected: {$reason}";
    }

    protected function money(mixed $amount): string
    {
        return number_format((float) $amount, 2);
    }
}
