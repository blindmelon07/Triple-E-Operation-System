<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GovernmentContribution;
use Illuminate\Database\Seeder;

class GovernmentContributionSeeder extends Seeder
{
    public function run(): void
    {
        // SSS 2025 Contribution Table (Monthly Salary Credit)
        $sss = [
            ['salary_from' => 0, 'salary_to' => 4249.99, 'employee_share' => 180.00, 'employer_share' => 390.00],
            ['salary_from' => 4250, 'salary_to' => 4749.99, 'employee_share' => 202.50, 'employer_share' => 437.50],
            ['salary_from' => 4750, 'salary_to' => 5249.99, 'employee_share' => 225.00, 'employer_share' => 485.00],
            ['salary_from' => 5250, 'salary_to' => 5749.99, 'employee_share' => 247.50, 'employer_share' => 532.50],
            ['salary_from' => 5750, 'salary_to' => 6249.99, 'employee_share' => 270.00, 'employer_share' => 580.00],
            ['salary_from' => 6250, 'salary_to' => 6749.99, 'employee_share' => 292.50, 'employer_share' => 627.50],
            ['salary_from' => 6750, 'salary_to' => 7249.99, 'employee_share' => 315.00, 'employer_share' => 675.00],
            ['salary_from' => 7250, 'salary_to' => 7749.99, 'employee_share' => 337.50, 'employer_share' => 722.50],
            ['salary_from' => 7750, 'salary_to' => 8249.99, 'employee_share' => 360.00, 'employer_share' => 770.00],
            ['salary_from' => 8250, 'salary_to' => 8749.99, 'employee_share' => 382.50, 'employer_share' => 817.50],
            ['salary_from' => 8750, 'salary_to' => 9249.99, 'employee_share' => 405.00, 'employer_share' => 865.00],
            ['salary_from' => 9250, 'salary_to' => 9749.99, 'employee_share' => 427.50, 'employer_share' => 912.50],
            ['salary_from' => 9750, 'salary_to' => 10249.99, 'employee_share' => 450.00, 'employer_share' => 960.00],
            ['salary_from' => 10250, 'salary_to' => 10749.99, 'employee_share' => 472.50, 'employer_share' => 1007.50],
            ['salary_from' => 10750, 'salary_to' => 11249.99, 'employee_share' => 495.00, 'employer_share' => 1055.00],
            ['salary_from' => 11250, 'salary_to' => 11749.99, 'employee_share' => 517.50, 'employer_share' => 1102.50],
            ['salary_from' => 11750, 'salary_to' => 12249.99, 'employee_share' => 540.00, 'employer_share' => 1150.00],
            ['salary_from' => 12250, 'salary_to' => 12749.99, 'employee_share' => 562.50, 'employer_share' => 1197.50],
            ['salary_from' => 12750, 'salary_to' => 13249.99, 'employee_share' => 585.00, 'employer_share' => 1245.00],
            ['salary_from' => 13250, 'salary_to' => 13749.99, 'employee_share' => 607.50, 'employer_share' => 1292.50],
            ['salary_from' => 13750, 'salary_to' => 14249.99, 'employee_share' => 630.00, 'employer_share' => 1340.00],
            ['salary_from' => 14250, 'salary_to' => 14749.99, 'employee_share' => 652.50, 'employer_share' => 1387.50],
            ['salary_from' => 14750, 'salary_to' => 15249.99, 'employee_share' => 675.00, 'employer_share' => 1435.00],
            ['salary_from' => 15250, 'salary_to' => 15749.99, 'employee_share' => 697.50, 'employer_share' => 1482.50],
            ['salary_from' => 15750, 'salary_to' => 16249.99, 'employee_share' => 720.00, 'employer_share' => 1530.00],
            ['salary_from' => 16250, 'salary_to' => 16749.99, 'employee_share' => 742.50, 'employer_share' => 1577.50],
            ['salary_from' => 16750, 'salary_to' => 17249.99, 'employee_share' => 765.00, 'employer_share' => 1625.00],
            ['salary_from' => 17250, 'salary_to' => 17749.99, 'employee_share' => 787.50, 'employer_share' => 1672.50],
            ['salary_from' => 17750, 'salary_to' => 18249.99, 'employee_share' => 810.00, 'employer_share' => 1720.00],
            ['salary_from' => 18250, 'salary_to' => 18749.99, 'employee_share' => 832.50, 'employer_share' => 1767.50],
            ['salary_from' => 18750, 'salary_to' => 19249.99, 'employee_share' => 855.00, 'employer_share' => 1815.00],
            ['salary_from' => 19250, 'salary_to' => 19749.99, 'employee_share' => 877.50, 'employer_share' => 1862.50],
            ['salary_from' => 19750, 'salary_to' => 20249.99, 'employee_share' => 900.00, 'employer_share' => 1910.00],
            ['salary_from' => 20250, 'salary_to' => 20749.99, 'employee_share' => 922.50, 'employer_share' => 1957.50],
            ['salary_from' => 20750, 'salary_to' => 21249.99, 'employee_share' => 945.00, 'employer_share' => 2005.00],
            ['salary_from' => 21250, 'salary_to' => 21749.99, 'employee_share' => 967.50, 'employer_share' => 2052.50],
            ['salary_from' => 21750, 'salary_to' => 22249.99, 'employee_share' => 990.00, 'employer_share' => 2100.00],
            ['salary_from' => 22250, 'salary_to' => 22749.99, 'employee_share' => 1012.50, 'employer_share' => 2147.50],
            ['salary_from' => 22750, 'salary_to' => 23249.99, 'employee_share' => 1035.00, 'employer_share' => 2195.00],
            ['salary_from' => 23250, 'salary_to' => 23749.99, 'employee_share' => 1057.50, 'employer_share' => 2242.50],
            ['salary_from' => 23750, 'salary_to' => 24249.99, 'employee_share' => 1080.00, 'employer_share' => 2290.00],
            ['salary_from' => 24250, 'salary_to' => 24749.99, 'employee_share' => 1102.50, 'employer_share' => 2337.50],
            ['salary_from' => 24750, 'salary_to' => 25249.99, 'employee_share' => 1125.00, 'employer_share' => 2385.00],
            ['salary_from' => 25250, 'salary_to' => 25749.99, 'employee_share' => 1147.50, 'employer_share' => 2432.50],
            ['salary_from' => 25750, 'salary_to' => 29999.99, 'employee_share' => 1125.00, 'employer_share' => 2385.00],
            ['salary_from' => 30000, 'salary_to' => 99999.99, 'employee_share' => 1350.00, 'employer_share' => 2850.00],
        ];

        foreach ($sss as $bracket) {
            GovernmentContribution::firstOrCreate(
                ['type' => 'sss', 'salary_from' => $bracket['salary_from'], 'salary_to' => $bracket['salary_to']],
                array_merge($bracket, ['type' => 'sss', 'is_active' => true])
            );
        }

        // PhilHealth 2025 - 5% of monthly salary, split 50/50
        // Min: ₱500/month total (₱250 each), Max: ₱5,000/month total (₱2,500 each)
        $philhealth = [
            ['salary_from' => 0, 'salary_to' => 10000.00, 'employee_share' => 250.00, 'employer_share' => 250.00],
            ['salary_from' => 10000.01, 'salary_to' => 99999.99, 'employee_share' => 0, 'employer_share' => 0], // Computed: 2.5% each
        ];

        // For PhilHealth, we store simplified brackets. The model's method will handle percentage calc.
        // Bracket 1: floor (min contribution)
        GovernmentContribution::firstOrCreate(
            ['type' => 'philhealth', 'salary_from' => 0, 'salary_to' => 10000.00],
            ['type' => 'philhealth', 'salary_from' => 0, 'salary_to' => 10000.00, 'employee_share' => 250.00, 'employer_share' => 250.00, 'is_active' => true]
        );
        // Bracket 2: ₱10,001 - ₱100,000 (2.5% each, max ₱2,500)
        GovernmentContribution::firstOrCreate(
            ['type' => 'philhealth', 'salary_from' => 10000.01, 'salary_to' => 100000.00],
            ['type' => 'philhealth', 'salary_from' => 10000.01, 'salary_to' => 100000.00, 'employee_share' => 2500.00, 'employer_share' => 2500.00, 'is_active' => true]
        );

        // Pag-IBIG 2025
        // ≤ ₱1,500: employee 1%, employer 2%
        // > ₱1,500: employee 2%, employer 2%, max ₱200 (₱100 each)
        GovernmentContribution::firstOrCreate(
            ['type' => 'pagibig', 'salary_from' => 0, 'salary_to' => 1500.00],
            ['type' => 'pagibig', 'salary_from' => 0, 'salary_to' => 1500.00, 'employee_share' => 15.00, 'employer_share' => 30.00, 'is_active' => true]
        );
        GovernmentContribution::firstOrCreate(
            ['type' => 'pagibig', 'salary_from' => 1500.01, 'salary_to' => 99999.99],
            ['type' => 'pagibig', 'salary_from' => 1500.01, 'salary_to' => 99999.99, 'employee_share' => 100.00, 'employer_share' => 100.00, 'is_active' => true]
        );
    }
}
