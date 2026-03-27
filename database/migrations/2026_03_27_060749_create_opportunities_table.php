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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sheet_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_key');
            $table->string('rfid')->nullable();
            $table->string('lead_reference')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('lead_source')->nullable();
            $table->string('required_service')->nullable();
            $table->decimal('revenue_potential', 14, 2)->nullable();
            $table->unsignedInteger('project_timeline_days')->nullable();
            $table->string('sales_stage')->default('Initial Contact');
            $table->string('reason_for_loss')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submission_date')->nullable();
            $table->string('year_month')->nullable();
            $table->boolean('manual_entry')->default(false);
            $table->json('source_payload')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'external_key']);
            $table->index(['workspace_id', 'sales_stage']);
            $table->index(['workspace_id', 'submission_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
