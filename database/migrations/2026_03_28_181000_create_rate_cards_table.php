<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rate_code');
            $table->string('customer_name')->nullable();
            $table->string('service_mode')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('via_port')->nullable();
            $table->string('incoterm')->nullable();
            $table->string('commodity')->nullable();
            $table->string('equipment_type')->nullable();
            $table->unsignedInteger('transit_days')->nullable();
            $table->decimal('buy_amount', 14, 2)->nullable();
            $table->decimal('sell_amount', 14, 2)->nullable();
            $table->decimal('margin_amount', 14, 2)->nullable();
            $table->string('currency')->default('AED');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'rate_code']);
            $table->index(['workspace_id', 'service_mode']);
            $table->index(['workspace_id', 'origin', 'destination']);
            $table->index(['workspace_id', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_cards');
    }
};
