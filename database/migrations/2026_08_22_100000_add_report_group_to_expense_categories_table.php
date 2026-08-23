<?php

use App\Models\ExpenseCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->string('report_group')->default('other')->after('description');
        });

        // The classification + seed row below are one-off data fixes for the real
        // database, not schema — skip them in testing so `migrate:fresh` (which
        // RefreshDatabase runs before every test) doesn't inject an extra category
        // that CRUD tests asserting exact counts don't expect.
        if (app()->environment('testing')) {
            return;
        }

        // Classify existing categories so the Daily/Period Transaction Reports can
        // group them correctly straight away. Matching is case-insensitive and
        // tolerant of the odd naming already in use (e.g. "Maintenance1").
        $map = [
            'maintenance' => 'maintenance',
            'gas' => 'logistics',
            'logistics' => 'logistics',
            'utilities' => 'utilities',
            'salaries' => 'payroll',
            'labor payment' => 'payroll',
            'payroll' => 'payroll',
        ];

        foreach (ExpenseCategory::all() as $category) {
            $key = strtolower(rtrim((string) $category->name, '0123456789 '));
            $key = trim($key);
            if (isset($map[$key])) {
                $category->update(['report_group' => $map[$key]]);
            }
        }

        // Add a dedicated category for supplier payments, since none existed —
        // this is how "payment to suppliers" now gets logged and dated so it
        // shows up in the transaction reports.
        if (! ExpenseCategory::where('name', 'SUPPLIER PAYMENT')->exists()) {
            ExpenseCategory::create([
                'name' => 'SUPPLIER PAYMENT',
                'description' => 'Cash paid out to suppliers for received stock.',
                'is_active' => true,
                'report_group' => 'supplier_payment',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('report_group');
        });
    }
};
