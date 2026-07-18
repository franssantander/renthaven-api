<?php

namespace App\Data\Notification;

use App\Models\Notification;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

class NotificationData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $module,
        public string $module_label,
        public string $action,
        public string $action_label,
        public string $title,
        public ?string $message,
        public ?array $data,
        public bool $is_read,
        public ?string $read_at,
        public string $timestamp,
        public string $timezone,
        public string $time_ago,
    ) {}

    public static function fromModel(Notification $notification): self
    {
        return new self(
            id: $notification->id,
            uuid: $notification->uuid,
            module: $notification->module,
            module_label: Str::headline($notification->module),
            action: $notification->action,
            action_label: Str::headline($notification->action),
            title: $notification->title,
            message: $notification->message,
            data: $notification->data,
            is_read: $notification->read_at !== null,
            read_at: $notification->read_at?->toIso8601String(),
            timestamp: $notification->created_at->toIso8601String(),
            timezone: config('app.timezone'),
            time_ago: $notification->created_at->diffForHumans(),
        );
    }
}
