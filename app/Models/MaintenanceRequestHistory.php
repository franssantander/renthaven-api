<?php

namespace App\Models;

use App\Enum\MaintenanceRequestHistoryAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'maintenance_request_id',
    'action',
    'from_status',
    'to_status',
    'performed_by',
    'notes',
])]
#[Table('maintenance_request_histories')]
class MaintenanceRequestHistory extends Model
{
    protected function casts(): array
    {
        return [
            'maintenance_request_id' => 'integer',
            'performed_by'           => 'integer',
            'action'                 => MaintenanceRequestHistoryAction::class,
        ];
    }

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
