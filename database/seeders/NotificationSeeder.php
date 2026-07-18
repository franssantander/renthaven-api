<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Replay the seeded audit trail through the real NotificationService so
     * seeded notifications obey the exact same trigger + scoping rules as
     * production (super admin privacy, per-business fan-out, renter targeting).
     */
    public function run(): void
    {
        $service = app(NotificationService::class);
        $total = 0;

        AuditLog::query()
            ->orderBy('created_at')
            ->each(function (AuditLog $log) use ($service, &$total) {
                $notifications = $service->dispatchFromAuditLog($log);

                // Backdate to the source event so relative "time ago" display
                // is realistic instead of everything reading "seconds ago".
                foreach ($notifications as $notification) {
                    $notification->created_at = $log->created_at;
                    $notification->updated_at = $log->created_at;
                    $notification->save();
                }

                $total += $notifications->count();
            });

        $this->command->info("Fanned out {$total} notifications from the seeded audit trail.");
    }
}
