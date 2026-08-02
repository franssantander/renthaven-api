<?php

namespace Database\Seeders;

use App\Enum\AmenityCategory;
use App\Enum\AmenityScope;
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
            ['name' => 'WiFi', 'category' => AmenityCategory::INTERNET, 'scope' => AmenityScope::BOTH],
            ['name' => 'Dedicated Workspace', 'category' => AmenityCategory::INTERNET, 'scope' => AmenityScope::UNIT],

            ['name' => 'Air Conditioning', 'category' => AmenityCategory::GENERAL, 'scope' => AmenityScope::UNIT],
            ['name' => 'Heating', 'category' => AmenityCategory::GENERAL, 'scope' => AmenityScope::UNIT],
            ['name' => 'Washer', 'category' => AmenityCategory::GENERAL, 'scope' => AmenityScope::UNIT],
            ['name' => 'Dryer', 'category' => AmenityCategory::GENERAL, 'scope' => AmenityScope::UNIT],
            ['name' => 'Iron', 'category' => AmenityCategory::GENERAL, 'scope' => AmenityScope::UNIT],

            ['name' => 'Kitchen', 'category' => AmenityCategory::KITCHEN, 'scope' => AmenityScope::UNIT],
            ['name' => 'Refrigerator', 'category' => AmenityCategory::KITCHEN, 'scope' => AmenityScope::UNIT],
            ['name' => 'Microwave', 'category' => AmenityCategory::KITCHEN, 'scope' => AmenityScope::UNIT],
            ['name' => 'Cooking Basics', 'category' => AmenityCategory::KITCHEN, 'scope' => AmenityScope::UNIT],
            ['name' => 'Dishwasher', 'category' => AmenityCategory::KITCHEN, 'scope' => AmenityScope::UNIT],

            ['name' => 'Hot Water', 'category' => AmenityCategory::BATHROOM, 'scope' => AmenityScope::UNIT],
            ['name' => 'Bathtub', 'category' => AmenityCategory::BATHROOM, 'scope' => AmenityScope::UNIT],
            ['name' => 'Hair Dryer', 'category' => AmenityCategory::BATHROOM, 'scope' => AmenityScope::UNIT],

            ['name' => 'Pool', 'category' => AmenityCategory::OUTDOOR, 'scope' => AmenityScope::PROPERTY],
            ['name' => 'Balcony', 'category' => AmenityCategory::OUTDOOR, 'scope' => AmenityScope::UNIT],
            ['name' => 'Garden', 'category' => AmenityCategory::OUTDOOR, 'scope' => AmenityScope::PROPERTY],
            ['name' => 'BBQ Grill', 'category' => AmenityCategory::OUTDOOR, 'scope' => AmenityScope::PROPERTY],

            ['name' => 'Free Parking', 'category' => AmenityCategory::PARKING, 'scope' => AmenityScope::PROPERTY],
            ['name' => 'Paid Parking', 'category' => AmenityCategory::PARKING, 'scope' => AmenityScope::PROPERTY],
            ['name' => 'Elevator', 'category' => AmenityCategory::PARKING, 'scope' => AmenityScope::PROPERTY],
            ['name' => 'Gym', 'category' => AmenityCategory::PARKING, 'scope' => AmenityScope::PROPERTY],

            ['name' => 'Smoke Alarm', 'category' => AmenityCategory::SAFETY, 'scope' => AmenityScope::UNIT],
            ['name' => 'Carbon Monoxide Alarm', 'category' => AmenityCategory::SAFETY, 'scope' => AmenityScope::UNIT],
            ['name' => 'Fire Extinguisher', 'category' => AmenityCategory::SAFETY, 'scope' => AmenityScope::BOTH],
            ['name' => 'First Aid Kit', 'category' => AmenityCategory::SAFETY, 'scope' => AmenityScope::UNIT],
            ['name' => 'Security Cameras', 'category' => AmenityCategory::SAFETY, 'scope' => AmenityScope::PROPERTY],
            ['name' => '24/7 Security Guard', 'category' => AmenityCategory::SAFETY, 'scope' => AmenityScope::PROPERTY],

            ['name' => 'TV', 'category' => AmenityCategory::ENTERTAINMENT, 'scope' => AmenityScope::UNIT],
            ['name' => 'Cable/Streaming Service', 'category' => AmenityCategory::ENTERTAINMENT, 'scope' => AmenityScope::UNIT],
        ];

        foreach ($amenities as $amenity) {
            Amenity::updateOrCreate(
                ['slug' => Str::slug($amenity['name'])],
                [
                    'name'     => $amenity['name'],
                    'category' => $amenity['category']->value,
                    'scope'    => $amenity['scope']->value,
                ]
            );
        }
    }
}
