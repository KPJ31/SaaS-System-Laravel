<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('event_type')->default('company');
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('visibility')->default('company');
            $table->string('status')->default('scheduled')->index();
            $table->timestamps();

            $table->index(['company_id', 'start_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_events');
    }
};
