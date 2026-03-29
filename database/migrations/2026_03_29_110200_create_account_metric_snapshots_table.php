<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('snapshot_key')->default('current');
            $table->unsignedInteger('inquiries_30d')->default(0);
            $table->unsignedInteger('inquiries_90d')->default(0);
            $table->unsignedInteger('quotes_30d')->default(0);
            $table->unsignedInteger('quotes_90d')->default(0);
            $table->unsignedInteger('shipments_30d')->default(0);
            $table->unsignedInteger('shipments_90d')->default(0);
            $table->unsignedInteger('shipments_prev_90d')->default(0);
            $table->unsignedInteger('bookings_90d')->default(0);
            $table->unsignedInteger('won_opportunities_180d')->default(0);
            $table->decimal('revenue_365d', 14, 2)->default(0);
            $table->unsignedInteger('lifetime_inquiries')->default(0);
            $table->unsignedInteger('lifetime_shipments')->default(0);
            $table->unsignedInteger('days_since_last_inquiry')->nullable();
            $table->unsignedInteger('days_since_last_quote')->nullable();
            $table->unsignedInteger('days_since_last_shipment')->nullable();
            $table->unsignedInteger('days_since_last_booking')->nullable();
            $table->timestamp('last_inquiry_at')->nullable();
            $table->timestamp('last_quote_at')->nullable();
            $table->timestamp('last_shipment_at')->nullable();
            $table->timestamp('last_booking_at')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'account_id', 'snapshot_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_metric_snapshots');
    }
};
