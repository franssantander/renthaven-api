<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired every time a notification row is created.
 *
 * Websocket hook: when broadcasting lands, implement ShouldBroadcast here and
 * return a PrivateChannel("users.{$this->notification->user_id}") from
 * broadcastOn() — no other part of the pipeline needs to change.
 */
class NotificationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Notification $notification) {}
}
