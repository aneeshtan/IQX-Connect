<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_costing_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_costing_id')->constrained()->cascadeOnDelete();
            $table->string('line_type');
            $table->string('charge_code')->nullable();
            $table->string('description');
            $table->string('vendor_name')->nullable();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->boolean('is_billable')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['job_costing_id', 'line_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_costing_lines');
    }
};
