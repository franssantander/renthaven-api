<?php

namespace Database\Seeders;

use App\Models\PermissionAction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $actions = [
            [
                'name' => 'View',
                'slug' => 'view',
                'description' => 'Can view and list records.',
            ],
            [
                'name' => 'Create',
                'slug' => 'create',
                'description' => 'Can create new records.',
            ],
            [
                'name' => 'Update',
                'slug' => 'update',
                'description' => 'Can modify existing records.',
            ],
            [
                'name' => 'Delete',
                'slug' => 'delete',
                'description' => 'Can soft-delete records.',
            ],

            // 💡 Suggested Actions Based on Your System
            [
                'name' => 'Approve',
                'slug' => 'approve',
                'description' => 'Can approve or reject workflows (e.g., Payment Approvals).',
            ],
            [
                'name' => 'Export',
                'slug' => 'export',
                'description' => 'Can download data reports (e.g., Ledger CSV/PDFs).',
            ],
            [
                'name' => 'Restore',
                'slug' => 'restore',
                'description' => 'Can restore records from the trash.',
            ],
        ];

        foreach ($actions as $action) {
            PermissionAction::updateOrCreate(
                ['slug' => $action['slug']],
                $action
            );
        }
    }
}