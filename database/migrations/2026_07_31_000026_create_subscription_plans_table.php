<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->decimal('annual_price', 12, 2)->nullable();
            $table->unsignedInteger('employee_limit')->default(5);
            $table->unsignedInteger('client_limit')->default(10);
            $table->unsignedInteger('project_limit')->default(10);
            $table->unsignedInteger('storage_limit_mb')->default(1024);
            $table->unsignedInteger('trial_days')->default(0);
            $table->json('features')->nullable();
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('display_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
