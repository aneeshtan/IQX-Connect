<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_segment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_definition_id')->constrained('customer_segment_definitions')->cascadeOnDelete();
            $table->string('metric_key');
            $table->string('operator', 12);
            $table->decimal('threshold_value', 14, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_segment_rules');
    }
};
