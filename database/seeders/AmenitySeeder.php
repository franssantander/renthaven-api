<?php

namespace Database\Seeders;

use App\Enum\AmenityCategory;
use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            ['name' => 'WiFi', 'category' => AmenityCategory::INTERNET],
            ['name' => 'Dedicated Workspace', 'category' => AmenityCategory::INTERNET],

            ['name' => 'Air Conditioning', 'category' => AmenityCategory::GENERAL],
            ['name' => 'Heating', 'category' => AmenityCategory::GENERAL],
            ['name' => 'Washer', 'category' => AmenityCategory::GENERAL],
            ['name' => 'Dryer', 'category' => AmenityCategory::GENERAL],
            ['name' => 'Iron', 'category' => AmenityCategory::GENERAL],

            ['name' => 'Kitchen', 'category' => AmenityCategory::KITCHEN],
            ['name' => 'Refrigerator', 'category' => AmenityCategory::KITCHEN],
            ['name' => 'Microwave', 'category' => AmenityCategory::KITCHEN],
            ['name' => 'Cooking Basics', 'category' => AmenityCategory::KITCHEN],
            ['name' => 'Dishwasher', 'category' => AmenityCategory::KITCHEN],

            ['name' => 'Hot Water', 'category' => AmenityCategory::BATHROOM],
            ['name' => 'Bathtub', 'category' => AmenityCategory::BATHROOM],
            ['name' => 'Hair Dryer', 'category' => AmenityCategory::BATHROOM],

            ['name' => 'Pool', 'category' => AmenityCategory::OUTDOOR],
            ['name' => 'Balcony', 'category' => AmenityCategory::OUTDOOR],
            ['name' => 'Garden', 'category' => AmenityCategory::OUTDOOR],
            ['name' => 'BBQ Grill', 'category' => AmenityCategory::OUTDOOR],

            ['name' => 'Free Parking', 'category' => AmenityCategory::PARKING],
            ['name' => 'Paid Parking', 'category' => AmenityCategory::PARKING],
            ['name' => 'Elevator', 'category' => AmenityCategory::PARKING],
            ['name' => 'Gym', 'category' => AmenityCategory::PARKING],

            ['name' => 'Smoke Alarm', 'category' => AmenityCategory::SAFETY],
            ['name' => 'Carbon Monoxide Alarm', 'category' => AmenityCategory::SAFETY],
            ['name' => 'Fire Extinguisher', 'category' => AmenityCategory::SAFETY],
            ['name' => 'First Aid Kit', 'category' => AmenityCategory::SAFETY],
            ['name' => 'Security Cameras', 'category' => AmenityCategory::SAFETY],
            ['name' => '24/7 Security Guard', 'category' => AmenityCategory::SAFETY],

            ['name' => 'TV', 'category' => AmenityCategory::ENTERTAINMENT],
            ['name' => 'Cable/Streaming Service', 'category' => AmenityCategory::ENTERTAINMENT],
        ];

        foreach ($amenities as $amenity) {
            Amenity::updateOrCreate(
                ['slug' => Str::slug($amenity['name'])],
                [
                    'name'     => $amenity['name'],
                    'category' => $amenity['category']->value,
                ]
            );
        }
    }
}
