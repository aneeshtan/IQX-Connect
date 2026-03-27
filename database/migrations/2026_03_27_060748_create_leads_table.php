<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sheet_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_key');
            $table->string('lead_id')->nullable();
            $table->string('rfid')->nullable();
            $table->string('lead_key')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('service')->nullable();
            $table->timestamp('submission_date')->nullable();
            $table->string('lead_source')->nullable();
            $table->string('status')->default('In-progress');
            $table->string('disqualification_reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('nurture_minutes')->nullable();
            $table->decimal('nurture_hours', 8, 2)->nullable();
            $table->decimal('lead_value', 14, 2)->nullable();
            $table->string('hashed_email')->nullable();
            $table->string('hashed_phone')->nullable();
            $table->boolean('is_converted')->default(false);
            $table->boolean('manual_entry')->default(false);
            $table->json('source_payload')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'external_key']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'lead_source']);
            $table->index(['workspace_id', 'submission_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
