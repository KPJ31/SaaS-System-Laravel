<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->unsignedInteger('gross_minutes')->default(0);
            $table->unsignedInteger('lunch_break_minutes')->default(0);
            $table->unsignedInteger('net_work_minutes')->default(0);
            $table->string('status')->default('not_checked_in')->index();
            $table->boolean('is_late')->default(false);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->boolean('is_early_departure')->default(false);
            $table->unsignedInteger('early_departure_minutes')->default(0);
            $table->text('note')->nullable();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('correction_reason')->nullable();
            $table->json('correction_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id', 'attendance_date']);
            $table->index(['company_id', 'attendance_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
