<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('government_contributions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['sss', 'philhealth', 'pagibig']);
            $table->decimal('salary_from', 12, 2);
            $table->decimal('salary_to', 12, 2);
            $table->decimal('employee_share', 12, 2);
            $table->decimal('employer_share', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('government_contributions');
    }
};
