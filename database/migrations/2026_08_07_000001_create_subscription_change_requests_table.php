<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('current_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->foreignId('requested_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('change_type')->index();
            $table->decimal('current_price', 12, 2)->default(0);
            $table->decimal('requested_price', 12, 2)->default(0);
            $table->decimal('payable_amount', 12, 2)->default(0);
            $table->string('billing_cycle')->default('monthly');
            $table->date('effective_date')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('request_note')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['requested_plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_change_requests');
    }
};
