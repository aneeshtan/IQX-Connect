<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sheet_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('job_number');
            $table->string('external_reference')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('service_mode')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('incoterm')->nullable();
            $table->string('commodity')->nullable();
            $table->string('equipment_type')->nullable();
            $table->unsignedInteger('container_count')->nullable();
            $table->decimal('weight_kg', 14, 2)->nullable();
            $table->decimal('volume_cbm', 14, 3)->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('vessel_name')->nullable();
            $table->string('voyage_number')->nullable();
            $table->string('house_bill_no')->nullable();
            $table->string('master_bill_no')->nullable();
            $table->timestamp('estimated_departure_at')->nullable();
            $table->timestamp('estimated_arrival_at')->nullable();
            $table->timestamp('actual_departure_at')->nullable();
            $table->timestamp('actual_arrival_at')->nullable();
            $table->string('status')->default('Draft');
            $table->decimal('buy_amount', 14, 2)->nullable();
            $table->decimal('sell_amount', 14, 2)->nullable();
            $table->decimal('margin_amount', 14, 2)->nullable();
            $table->string('currency')->default('AED');
            $table->text('notes')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'job_number']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'estimated_departure_at']);
            $table->index(['workspace_id', 'estimated_arrival_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_jobs');
    }
};
