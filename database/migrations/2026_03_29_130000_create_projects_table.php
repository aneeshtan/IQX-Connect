<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('project_number')->index();
            $table->string('project_name');
            $table->string('customer_name');
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('service_type')->nullable();
            $table->string('container_type')->nullable();
            $table->unsignedInteger('unit_quantity')->nullable();
            $table->text('scope_summary')->nullable();
            $table->string('site_location')->nullable();
            $table->date('target_delivery_date')->nullable();
            $table->date('target_installation_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->date('actual_installation_date')->nullable();
            $table->decimal('estimated_value', 14, 2)->nullable();
            $table->string('status')->index();
            $table->text('notes')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
