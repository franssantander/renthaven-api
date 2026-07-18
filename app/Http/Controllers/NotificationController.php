<?php

namespace App\Http\Controllers;

use App\Data\Notification\NotificationData;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

class NotificationController extends Controller
{
    /**
     * The authenticated user's notifications, newest first. Visibility scoping
     * is inherent: rows are only ever created for their intended recipient.
     */
    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->when($request->boolean('unread_only'), fn ($q) => $q->whereNull('read_at'))
            ->latest('created_at')
            ->latest('id')
            ->paginate($request->input('per_page', 15));

        return NotificationData::collect($notifications, PaginatedDataCollection::class);
    }

    /**
     * Unread badge count for the authenticated user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return $this->success(['unread_count' => $count], 'Unread notification count retrieved successfully.');
    }

    /**
     * Mark one of the authenticated user's notifications as read.
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $this->success(NotificationData::from($notification), 'Notification marked as read.');
    }

    /**
     * Mark all of the authenticated user's notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $updated = Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(['marked_read' => $updated], 'All notifications marked as read.');
    }
}
