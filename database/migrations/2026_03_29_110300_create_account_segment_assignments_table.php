<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_segment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('segment_definition_id')->constrained('customer_segment_definitions')->cascadeOnDelete();
            $table->foreignId('account_metric_snapshot_id')->nullable()->constrained('account_metric_snapshots')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'account_id', 'segment_definition_id'], 'account_segment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_segment_assignments');
    }
};
