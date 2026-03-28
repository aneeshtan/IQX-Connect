<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('quote_number');
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('service_mode')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('incoterm')->nullable();
            $table->string('commodity')->nullable();
            $table->string('equipment_type')->nullable();
            $table->decimal('weight_kg', 14, 2)->nullable();
            $table->decimal('volume_cbm', 14, 3)->nullable();
            $table->decimal('buy_amount', 14, 2)->nullable();
            $table->decimal('sell_amount', 14, 2)->nullable();
            $table->decimal('margin_amount', 14, 2)->nullable();
            $table->string('currency')->default('AED');
            $table->string('status')->default('Draft');
            $table->date('valid_until')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'quote_number']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'quoted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
