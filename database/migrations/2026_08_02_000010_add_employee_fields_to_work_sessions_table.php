<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('work_sessions', 'status')) {
                $table->string('status')->default('running')->after('notes')->index();
            }
            if (! Schema::hasColumn('work_sessions', 'is_manual')) {
                $table->boolean('is_manual')->default(false)->after('status');
            }
            if (! Schema::hasColumn('work_sessions', 'approval_status')) {
                $table->string('approval_status')->nullable()->after('is_manual')->index();
            }
            if (! Schema::hasColumn('work_sessions', 'adjustment_reason')) {
                $table->text('adjustment_reason')->nullable()->after('approval_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_sessions', function (Blueprint $table): void {
            foreach (['adjustment_reason', 'approval_status', 'is_manual', 'status'] as $column) {
                if (Schema::hasColumn('work_sessions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
