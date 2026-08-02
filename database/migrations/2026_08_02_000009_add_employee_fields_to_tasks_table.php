<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('tasks', 'blocked_reason')) {
                $table->text('blocked_reason')->nullable()->after('task_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            if (Schema::hasColumn('tasks', 'blocked_reason')) {
                $table->dropColumn('blocked_reason');
            }
        });
    }
};
