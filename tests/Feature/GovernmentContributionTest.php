<?php

use App\Models\GovernmentContribution;

describe('GovernmentContribution bracket lookups', function () {
    it('finds the sss employee share for a salary within a bracket', function () {
        GovernmentContribution::factory()->sss()->create([
            'salary_from' => 10000, 'salary_to' => 14999, 'employee_share' => 250,
        ]);
        GovernmentContribution::factory()->sss()->create([
            'salary_from' => 15000, 'salary_to' => 19999, 'employee_share' => 350,
        ]);

        expect(GovernmentContribution::getSssDeduction(13000))->toBe(250.0)
            ->and(GovernmentContribution::getSssDeduction(18000))->toBe(350.0);
    });

    it('returns zero when no bracket matches the salary', function () {
        GovernmentContribution::factory()->sss()->create([
            'salary_from' => 10000, 'salary_to' => 14999, 'employee_share' => 250,
        ]);

        expect(GovernmentContribution::getSssDeduction(999999))->toBe(0.0);
    });

    it('ignores inactive brackets', function () {
        GovernmentContribution::factory()->sss()->create([
            'salary_from' => 10000, 'salary_to' => 14999, 'employee_share' => 250, 'is_active' => false,
        ]);

        expect(GovernmentContribution::getSssDeduction(13000))->toBe(0.0);
    });

    it('looks up philhealth and pagibig independently of sss', function () {
        GovernmentContribution::factory()->philhealth()->create([
            'salary_from' => 0, 'salary_to' => 999999, 'employee_share' => 137.5,
        ]);
        GovernmentContribution::factory()->pagibig()->create([
            'salary_from' => 0, 'salary_to' => 999999, 'employee_share' => 100,
        ]);

        expect(GovernmentContribution::getPhilhealthDeduction(20000))->toBe(137.5)
            ->and(GovernmentContribution::getPagibigDeduction(20000))->toBe(100.0)
            ->and(GovernmentContribution::getSssDeduction(20000))->toBe(0.0);
    });
});
