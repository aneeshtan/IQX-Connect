<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_job_id')->constrained()->cascadeOnDelete();
            $table->string('event_key')->nullable();
            $table->string('label');
            $table->unsignedInteger('sequence')->default(1);
            $table->string('status')->default('Pending');
            $table->timestamp('planned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'shipment_job_id', 'sequence']);
            $table->unique(['shipment_job_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_milestones');
    }
};
