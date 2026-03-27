<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sheet_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('year_month');
            $table->date('month_start')->nullable();
            $table->unsignedInteger('linkedin_ads_leads')->default(0);
            $table->unsignedInteger('organic_leads')->default(0);
            $table->unsignedInteger('email_leads')->default(0);
            $table->unsignedInteger('google_ads_leads')->default(0);
            $table->unsignedInteger('total_leads')->default(0);
            $table->decimal('linkedin_ads_cost', 14, 2)->default(0);
            $table->decimal('google_ads_cost', 14, 2)->default(0);
            $table->decimal('total_ads_cost', 14, 2)->default(0);
            $table->decimal('cost_per_conversion', 14, 2)->default(0);
            $table->decimal('total_revenue_potential', 14, 2)->default(0);
            $table->decimal('won_revenue_potential', 14, 2)->default(0);
            $table->unsignedInteger('closed_won_count')->default(0);
            $table->unsignedInteger('closed_lost_count')->default(0);
            $table->unsignedInteger('proposal_sent_count')->default(0);
            $table->unsignedInteger('initial_contact_count')->default(0);
            $table->unsignedInteger('organic_opportunities_count')->default(0);
            $table->unsignedInteger('total_opportunities_count')->default(0);
            $table->decimal('mql_to_sql_rate', 8, 2)->default(0);
            $table->decimal('sql_conversion_rate', 8, 2)->default(0);
            $table->decimal('total_revenue_potential_2025', 14, 2)->default(0);
            $table->decimal('won_revenue_potential_2025', 14, 2)->default(0);
            $table->unsignedInteger('total_deals_2026')->default(0);
            $table->unsignedInteger('total_deals_2025')->default(0);
            $table->unsignedInteger('total_deals_2024')->default(0);
            $table->decimal('romi_2025', 10, 2)->default(0);
            $table->json('source_payload')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'workspace_id', 'year_month'], 'monthly_reports_company_workspace_month_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
