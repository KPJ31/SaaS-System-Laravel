<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_registration_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name');
            $table->string('company_email')->unique();
            $table->string('company_phone');
            $table->text('company_address');
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('admin_name');
            $table->string('admin_email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('status')->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_registration_requests');
    }
};
