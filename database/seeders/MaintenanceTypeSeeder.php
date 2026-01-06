<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MaintenanceType;
use Illuminate\Database\Seeder;

class MaintenanceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Oil Change',
                'description' => 'Replace engine oil and oil filter',
                'recommended_interval_km' => 5000,
                'recommended_interval_months' => 3,
            ],
            [
                'name' => 'Tire Rotation',
                'description' => 'Rotate tires to ensure even wear',
                'recommended_interval_km' => 10000,
                'recommended_interval_months' => 6,
            ],
            [
                'name' => 'Brake Inspection',
                'description' => 'Inspect brake pads, rotors, and brake fluid',
                'recommended_interval_km' => 20000,
                'recommended_interval_months' => 12,
            ],
            [
                'name' => 'Brake Pad Replacement',
                'description' => 'Replace worn brake pads',
                'recommended_interval_km' => 40000,
                'recommended_interval_months' => null,
            ],
            [
                'name' => 'Air Filter Replacement',
                'description' => 'Replace engine air filter',
                'recommended_interval_km' => 15000,
                'recommended_interval_months' => 12,
            ],
            [
                'name' => 'Cabin Air Filter Replacement',
                'description' => 'Replace cabin/AC air filter',
                'recommended_interval_km' => 20000,
                'recommended_interval_months' => 12,
            ],
            [
                'name' => 'Battery Check/Replacement',
                'description' => 'Test battery health and replace if needed',
                'recommended_interval_km' => null,
                'recommended_interval_months' => 12,
            ],
            [
                'name' => 'Transmission Service',
                'description' => 'Change transmission fluid and filter',
                'recommended_interval_km' => 50000,
                'recommended_interval_months' => 24,
            ],
            [
                'name' => 'Coolant Flush',
                'description' => 'Drain and replace engine coolant',
                'recommended_interval_km' => 40000,
                'recommended_interval_months' => 24,
            ],
            [
                'name' => 'Spark Plug Replacement',
                'description' => 'Replace spark plugs',
                'recommended_interval_km' => 40000,
                'recommended_interval_months' => null,
            ],
            [
                'name' => 'Timing Belt Replacement',
                'description' => 'Replace timing belt/chain',
                'recommended_interval_km' => 100000,
                'recommended_interval_months' => null,
            ],
            [
                'name' => 'Wheel Alignment',
                'description' => 'Adjust wheel angles for proper alignment',
                'recommended_interval_km' => 20000,
                'recommended_interval_months' => 12,
            ],
            [
                'name' => 'Tire Replacement',
                'description' => 'Replace worn or damaged tires',
                'recommended_interval_km' => 50000,
                'recommended_interval_months' => null,
            ],
            [
                'name' => 'AC Service',
                'description' => 'Check and recharge AC refrigerant',
                'recommended_interval_km' => null,
                'recommended_interval_months' => 12,
            ],
            [
                'name' => 'Full Service / PMS',
                'description' => 'Comprehensive preventive maintenance service',
                'recommended_interval_km' => 10000,
                'recommended_interval_months' => 6,
            ],
            [
                'name' => 'General Repair',
                'description' => 'General repairs and fixes',
                'recommended_interval_km' => null,
                'recommended_interval_months' => null,
            ],
        ];

        foreach ($types as $type) {
            MaintenanceType::firstOrCreate(
                ['name' => $type['name']],
                array_merge($type, ['is_active' => true])
            );
        }
    }
}
