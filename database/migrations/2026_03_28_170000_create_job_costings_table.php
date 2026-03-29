<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_costings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_job_id')->nullable()->constrained('shipment_jobs')->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('costing_number');
            $table->string('customer_name');
            $table->string('service_mode')->nullable();
            $table->string('currency', 10)->default('AED');
            $table->decimal('total_cost_amount', 14, 2)->default(0);
            $table->decimal('total_sell_amount', 14, 2)->default(0);
            $table->decimal('margin_amount', 14, 2)->default(0);
            $table->decimal('margin_percent', 8, 2)->nullable();
            $table->string('status')->default('Draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'costing_number']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'shipment_job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_costings');
    }
};
