<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sheet_source_id')->nullable()->constrained('sheet_sources')->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->nullOnDelete();
            $table->foreignId('shipment_job_id')->nullable()->constrained('shipment_jobs')->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('booking_number');
            $table->string('external_reference')->nullable();
            $table->string('carrier_confirmation_ref')->nullable();
            $table->string('customer_name');
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('service_mode')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('incoterm')->nullable();
            $table->string('commodity')->nullable();
            $table->string('equipment_type')->nullable();
            $table->unsignedInteger('container_count')->nullable();
            $table->decimal('weight_kg', 12, 2)->nullable();
            $table->decimal('volume_cbm', 12, 3)->nullable();
            $table->dateTime('requested_etd')->nullable();
            $table->dateTime('requested_eta')->nullable();
            $table->dateTime('confirmed_etd')->nullable();
            $table->dateTime('confirmed_eta')->nullable();
            $table->string('status', 50)->default('Draft');
            $table->text('notes')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'booking_number']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'carrier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
