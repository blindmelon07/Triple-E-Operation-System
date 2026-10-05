<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Shield-style permissions for the new resource + report page, so the
     * super_admin role can see them immediately without re-running
     * shield:generate.
     *
     * @var array<int, string>
     */
    private array $permissions = [
        'ViewAny:CashAdvance',
        'View:CashAdvance',
        'Create:CashAdvance',
        'Update:CashAdvance',
        'Delete:CashAdvance',
        'View:CashAdvanceReport',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_number')->nullable();
            $table->date('date_granted');
            $table->decimal('amount', 12, 2);
            $table->decimal('deduction_per_payroll', 12, 2)->default(0);
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // One row per repayment. Rows with a payroll_item_id were created by
        // payroll generation; rows without one are manual (cash) repayments.
        Schema::create('cash_advance_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_advance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('cash_advance_deduction', 12, 2)->default(0)->after('pagibig_deduction');
        });

        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::where('name', 'super_admin')->first()?->givePermissionTo($this->permissions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)->delete();

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn('cash_advance_deduction');
        });

        Schema::dropIfExists('cash_advance_payments');
        Schema::dropIfExists('cash_advances');
    }
};
