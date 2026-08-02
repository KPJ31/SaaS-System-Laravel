<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('active')->index();
            $table->date('starts_at');
            $table->date('trial_ends_at')->nullable();
            $table->date('renews_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['subscription_plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
