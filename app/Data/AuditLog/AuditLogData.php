<?php

namespace App\Data\AuditLog;

use App\Models\AuditLog;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

class AuditLogData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $module,
        public string $module_label,
        public string $action,
        public string $action_label,
        public ?string $description,
        public ?AuditActorData $actor,
        public ?string $tenant_business,
        public ?string $auditable_type,
        public ?int $auditable_id,
        public ?array $old_values,
        public ?array $new_values,
        public ?array $context,
        public ?string $ip_address,
        public ?string $user_agent,
        public string $timestamp,
        public string $timezone,
        public string $time_ago,
    ) {}

    public static function fromModel(AuditLog $log): self
    {
        $actor = $log->actor;

        return new self(
            id: $log->id,
            uuid: $log->uuid,
            module: $log->module,
            module_label: Str::headline($log->module),
            action: $log->action,
            action_label: Str::headline($log->action),
            description: $log->description,
            actor: ($actor || $log->actor_email) ? new AuditActorData(
                uuid: $actor?->uuid,
                name: $actor?->full_name,
                email: $actor?->email ?? $log->actor_email,
                role: $actor?->role?->slug,
            ) : null,
            tenant_business: $log->tenantBusiness?->name,
            auditable_type: $log->auditable_type ? class_basename($log->auditable_type) : null,
            auditable_id: $log->auditable_id,
            old_values: $log->old_values,
            new_values: $log->new_values,
            context: $log->context,
            ip_address: $log->ip_address,
            user_agent: $log->user_agent,
            timestamp: $log->created_at->toIso8601String(),
            timezone: config('app.timezone'),
            time_ago: $log->created_at->diffForHumans(),
        );
    }
}
