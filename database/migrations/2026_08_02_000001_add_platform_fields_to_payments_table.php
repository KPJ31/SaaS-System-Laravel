<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'subscription_id')) {
                $table->foreignId('subscription_id')->nullable()->after('project_id')->constrained('subscriptions')->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'subscription_plan_id')) {
                $table->foreignId('subscription_plan_id')->nullable()->after('subscription_id')->constrained('subscription_plans')->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'transaction_reference')) {
                $table->string('transaction_reference')->nullable()->after('created_by')->index();
            }

            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->string('payment_type')->default('client_project')->after('transaction_reference')->index();
            }

            if (! Schema::hasColumn('payments', 'proof_path')) {
                $table->string('proof_path')->nullable()->after('method');
            }

            if (! Schema::hasColumn('payments', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }

            if (! Schema::hasColumn('payments', 'verification_note')) {
                $table->text('verification_note')->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            foreach (['subscription_id', 'subscription_plan_id', 'verified_by'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['transaction_reference', 'payment_type', 'proof_path', 'verified_at', 'verification_note'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
