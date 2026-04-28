<?php

namespace Modules\Property\Database\Seeders;

use App\Modules\Property\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            'Wifi',
            'Electricity',
            'Water',
            'Swimming Pool',
            'Gym',
            'Parking',
            'Air Conditioning',
            'Pet Friendly',
            'Laundry',
            'Security Guard',
            'Balcony',
            'Elevator'
        ];

        foreach ($amenities as $name) {
            Amenity::updateOrCreate(['name' => $name]);
        }
    }
}
