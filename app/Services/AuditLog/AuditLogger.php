<?php

namespace App\Services\AuditLog;

use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogger
{
    /**
     * Attribute keys that should never be persisted in old/new value diffs.
     */
    protected array $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected array $ignored = [
        'updated_at',
        'created_at',
        'deleted_at',
    ];

    protected function filterAttributes(array $values): array
    {
        return array_diff_key(
            $values,
            array_flip([
                ...$this->hidden,
                ...$this->ignored,
            ])
        );
    }

    public function __construct(protected Request $request)
    {
        //
    }


    public function record(
        AuditModule $module,
        AuditAction $action,
        ?string $description = null,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $context = [],
    ): AuditLog {

        if ($auditable && $newValues === null) {
            $newValues = $this->removeIgnored($auditable->getChanges());
        }

        if ($auditable && $oldValues !== null) {
            $oldValues = array_intersect_key($oldValues, $newValues);
        }
        $user = Auth::user();

        $tenantBusinessId = $context['tenant_business_id']
            ?? $user?->tenant_business_id
            ?? null;

        unset($context['tenant_business_id']);

        return AuditLog::create([
            'user_id'            => $user?->id,
            'tenant_business_id' => $tenantBusinessId,
            'actor_email'        => $user?->email,
            'module'             => $module->value,
            'action'             => $action->value,
            'description'        => $description,
            'old_values' => $oldValues ? $this->filterAttributes($oldValues) : null,
            'new_values' => $newValues ? $this->filterAttributes($newValues) : null,
            'auditable_type'     => $auditable?->getMorphClass(),
            'auditable_id'       => $auditable?->getKey(),
            'context'            => empty($context) ? null : $context,
            'ip_address'         => $this->getIpAddress(),
            'user_agent'         => $this->getUserAgent(),
        ]);
    }

    protected function getIpAddress(): ?string
    {
        return app()->runningInConsole() ? null : $this->request->ip();
    }

    protected function getUserAgent(): ?string
    {
        return app()->runningInConsole() ? null : $this->request->userAgent();
    }

    protected function filterHidden(array $values): array
    {
        return array_diff_key($values, array_flip($this->hidden));
    }

    protected function extractChanges(Model $model): array
    {
        $newValues = $model->getChanges();

        unset($newValues['updated_at']);

        $oldValues = $model->getPrevious();

        unset($oldValues['updated_at']);

        return [$oldValues, $newValues];
    }

    protected function removeIgnored(array $values): array
    {
        return array_diff_key($values, array_flip($this->ignored));
    }
}