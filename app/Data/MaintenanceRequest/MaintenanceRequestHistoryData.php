<?php

namespace App\Data\MaintenanceRequest;

use App\Enum\MaintenanceRequestHistoryAction;
use App\Models\MaintenanceRequestHistory;
use Spatie\LaravelData\Data;

class MaintenanceRequestHistoryData extends Data
{
    public function __construct(
        public int $id,
        public MaintenanceRequestHistoryAction $action,
        public ?string $from_status,
        public ?string $to_status,
        public ?string $notes,
        public ?string $performed_by_name,
        public string $created_at,
        public string $time_ago,
    ) {}

    public static function fromModel(MaintenanceRequestHistory $history): self
    {
        return new self(
            id: $history->id,
            action: $history->action,
            from_status: $history->from_status,
            to_status: $history->to_status,
            notes: $history->notes,
            performed_by_name: $history->performedBy?->full_name,
            created_at: $history->created_at->toIso8601String(),
            time_ago: $history->created_at->diffForHumans(),
        );
    }
}
