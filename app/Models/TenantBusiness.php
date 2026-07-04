<?php

namespace App\Models;

use App\Enum\StatusEnum;
use App\HasHasPublicUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('tenant_businesses')]
#[Fillable(['plan_id', 'name', 'email', 'phone', 'contact_person', 'tin', 'business_address', 'logo', 'status'])]
class TenantBusiness extends Model
{
    use HasHasPublicUuidTrait;

    protected function casts(): array
    {
        return [
            'status' => StatusEnum::class
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
