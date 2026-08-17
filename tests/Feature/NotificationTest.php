<?php

namespace Tests\Feature;

use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Enum\LedgerStatus;
use App\Enum\MaintenanceCategory;
use App\Enum\MaintenancePriority;
use App\Enum\MaintenanceRequestStatus;
use App\Enum\NotificationType;
use App\Events\NotificationCreated;
use App\Models\AuditLog;
use App\Models\Lease;
use App\Models\LedgerEntry;
use App\Models\MaintenanceRequest;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\Renter;
use App\Models\Role;
use App\Models\TenantBusiness;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private TenantBusiness $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PlanSeeder::class, RoleSeeder::class]);
        $this->business = TenantBusiness::factory()->create([
            'plan_id' => Plan::where('slug', 'free')->value('id'),
        ]);
        Event::fake([NotificationCreated::class]);
    }

    public function test_tenant_maintenance_submission_notifies_only_same_business_operational_users(): void
    {
        $tenant = $this->user('tenant');
        $admin = $this->user('admin');
        $staff = $this->user('staff');
        $outsider = $this->user('admin', TenantBusiness::factory()->create());
        [$renter, $lease, $unit] = $this->rentalFor($tenant);

        $request = MaintenanceRequest::create([
            'property_unit_id' => $unit->id,
            'lease_id' => $lease->id,
            'renter_id' => $renter->id,
            'tenant_business_id' => $this->business->id,
            'title' => 'Leaking kitchen sink',
            'description' => 'Water is pooling below the sink.',
            'category' => MaintenanceCategory::PLUMBING,
            'priority' => MaintenancePriority::HIGH,
            'status' => MaintenanceRequestStatus::OPEN,
        ]);

        $this->dispatch($tenant, $request, AuditModule::MAINTENANCE_REQUEST, NotificationType::MAINTENANCE_SUBMITTED);

        $this->assertEqualsCanonicalizing([$admin->id, $staff->id], Notification::pluck('user_id')->all());
        $this->assertDatabaseMissing('notifications', ['user_id' => $tenant->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $outsider->id]);
        $this->assertDatabaseHas('notifications', ['title' => 'New maintenance request']);
    }

    public function test_payment_submission_notifies_operational_users_and_decisions_notify_the_renter(): void
    {
        $tenant = $this->user('tenant');
        $admin = $this->user('admin');
        $staff = $this->user('staff');
        [$renter, $lease, $unit] = $this->rentalFor($tenant);
        $ledger = $this->ledgerFor($renter, $lease, $unit);

        $ledger->update([
            'status' => LedgerStatus::SUBMITTED,
            'submitted_amount' => 2500,
            'submitted_at' => now(),
        ]);
        $this->dispatch($tenant, $ledger, AuditModule::BILLING, NotificationType::PAYMENT_SUBMITTED);

        $this->assertEqualsCanonicalizing([$admin->id, $staff->id], Notification::pluck('user_id')->all());
        $this->assertStringContainsString('2,500.00', Notification::firstOrFail()->message);

        Notification::query()->delete();
        $accepted = $this->audit(
            $admin,
            $ledger,
            AuditModule::BILLING,
            NotificationType::PAYMENT_ACCEPTED,
            ['amount_paid' => 0],
            ['amount_paid' => 2500],
        );
        app(NotificationService::class)->dispatchFromAuditLog($accepted);

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $tenant->id,
            'title' => 'Payment accepted',
        ]);

        Notification::query()->delete();
        $rejected = $this->audit(
            $staff,
            $ledger,
            AuditModule::BILLING,
            NotificationType::PAYMENT_REJECTED,
            ['submitted_amount' => 2500],
            ['submitted_amount' => null],
            ['reason' => 'Receipt could not be verified.'],
        );
        app(NotificationService::class)->dispatchFromAuditLog($rejected);

        $notification = Notification::sole();
        $this->assertSame($tenant->id, $notification->user_id);
        $this->assertStringContainsString('Receipt could not be verified.', $notification->message);
    }

    public function test_new_user_and_initial_lease_assignment_notify_other_operational_users(): void
    {
        $actor = $this->user('admin');
        $staff = $this->user('staff');
        $tenant = $this->user('tenant');

        $newUser = $this->user('tenant');
        $this->dispatch($actor, $newUser, AuditModule::USER_MANAGEMENT, NotificationType::USER_CREATED);

        [$renter, $lease] = $this->rentalFor($tenant);
        $this->dispatch($actor, $lease, AuditModule::LEASE, NotificationType::TENANT_ASSIGNED);

        $this->assertSame(2, Notification::where('user_id', $staff->id)->count());
        $this->assertDatabaseMissing('notifications', ['user_id' => $actor->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $tenant->id]);
        $this->assertDatabaseHas('notifications', ['title' => 'New user added']);
        $this->assertDatabaseHas('notifications', ['title' => 'New tenant assigned']);
    }

    public function test_untagged_and_role_mismatched_audits_do_not_notify(): void
    {
        $staff = $this->user('staff');
        $newUser = $this->user('tenant');

        $untagged = AuditLog::create([
            'user_id' => $staff->id,
            'tenant_business_id' => $this->business->id,
            'module' => AuditModule::USER_MANAGEMENT->value,
            'action' => AuditAction::UPDATED->value,
            'auditable_type' => $newUser->getMorphClass(),
            'auditable_id' => $newUser->id,
        ]);
        app(NotificationService::class)->dispatchFromAuditLog($untagged);

        $wrongRole = $this->audit($staff, $newUser, AuditModule::MAINTENANCE_REQUEST, NotificationType::MAINTENANCE_SUBMITTED);
        app(NotificationService::class)->dispatchFromAuditLog($wrongRole);

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_notification_endpoints_are_scoped_and_support_read_actions(): void
    {
        $user = $this->user('admin');
        $other = $this->user('staff');
        $ownNotifications = collect([
            $this->notificationFor($user),
            $this->notificationFor($user),
        ]);
        $otherNotification = $this->notificationFor($other);

        Passport::actingAs($user);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 2);
        $this->putJson("/api/v1/notifications/{$otherNotification->uuid}/read")
            ->assertNotFound();
        $this->putJson("/api/v1/notifications/{$ownNotifications->first()->uuid}/read")
            ->assertOk();
        $this->putJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.marked_read', 1);

        $this->assertSame(0, Notification::where('user_id', $user->id)->whereNull('read_at')->count());
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_notification_list_can_be_filtered_by_read_status(): void
    {
        $user = $this->user('admin');
        $other = $this->user('staff');
        $readNotification = $this->notificationFor($user);
        $unreadNotification = $this->notificationFor($user);
        $this->notificationFor($other);

        $readNotification->update(['read_at' => now()]);

        Passport::actingAs($user);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/notifications?read_status=read')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $readNotification->uuid);
        $this->getJson('/api/v1/notifications?read_status=unread')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $unreadNotification->uuid);
        $this->getJson('/api/v1/notifications?unread_only=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $unreadNotification->uuid);
        $this->getJson('/api/v1/notifications?read_status=archived')
            ->assertUnprocessable();
    }

    public function test_private_channel_authorization_only_allows_the_matching_user(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
            'broadcasting.connections.reverb.options.host' => 'localhost',
            'broadcasting.connections.reverb.options.port' => 8080,
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);
        require base_path('routes/channels.php');

        $user = $this->user('admin');
        $other = $this->user('staff');

        Passport::actingAs($user);

        $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-users.{$user->id}",
        ])->assertOk();

        $forbiddenResponse = $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-users.{$other->id}",
        ]);

        $this->assertSame(403, $forbiddenResponse->status(), $forbiddenResponse->getContent());
    }

    public function test_notification_event_uses_the_recipient_channel_and_public_payload(): void
    {
        $user = $this->user('tenant');
        $notification = $this->notificationFor($user);
        $event = new NotificationCreated($notification);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame("private-users.{$user->id}", $channels[0]->name);
        $this->assertSame('notification.created', $event->broadcastAs());
        $this->assertSame($notification->uuid, $event->broadcastWith()['uuid']);
    }

    private function user(string $role, ?TenantBusiness $business = null): User
    {
        $business ??= $this->business;

        return User::factory()->create([
            'role_id' => Role::where('slug', $role)->value('id'),
            'tenant_business_id' => $business->id,
            'username' => fake()->unique()->userName(),
        ]);
    }

    /** @return array{Renter, Lease, PropertyUnit} */
    private function rentalFor(User $tenant): array
    {
        $property = Property::factory()->create(['tenant_business_id' => $this->business->id]);
        $unit = PropertyUnit::factory()->create(['property_id' => $property->id]);
        $renter = Renter::factory()->create([
            'tenant_business_id' => $this->business->id,
            'user_id' => $tenant->id,
            'email' => $tenant->email,
        ]);
        $lease = Lease::factory()->create([
            'property_unit_id' => $unit->id,
            'renter_id' => $renter->id,
        ]);

        return [$renter, $lease, $unit];
    }

    private function ledgerFor(Renter $renter, Lease $lease, PropertyUnit $unit): LedgerEntry
    {
        return LedgerEntry::create([
            'lease_id' => $lease->id,
            'renter_id' => $renter->id,
            'property_unit_id' => $unit->id,
            'tenant_business_id' => $this->business->id,
            'amount' => 5000,
            'amount_paid' => 0,
            'penalty_amount' => 0,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_date' => now()->addDays(5),
            'status' => LedgerStatus::PENDING,
        ]);
    }

    private function notificationFor(User $user): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'tenant_business_id' => $this->business->id,
            'module' => AuditModule::BILLING->value,
            'action' => AuditAction::UPDATED->value,
            'title' => 'Test notification',
            'message' => 'Test notification body.',
        ]);
    }

    private function dispatch(User $actor, Model $auditable, AuditModule $module, NotificationType $type): void
    {
        app(NotificationService::class)->dispatchFromAuditLog(
            $this->audit($actor, $auditable, $module, $type),
        );
    }

    private function audit(
        User $actor,
        Model $auditable,
        AuditModule $module,
        NotificationType $type,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $context = [],
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actor->id,
            'tenant_business_id' => $this->business->id,
            'module' => $module->value,
            'action' => AuditAction::CREATED->value,
            'description' => 'Test notification event',
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'context' => [
                'notification_type' => $type->value,
                ...$context,
            ],
        ]);
    }
}
