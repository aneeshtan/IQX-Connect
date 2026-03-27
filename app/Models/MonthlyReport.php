<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReport extends Model
{
    protected $fillable = [
        'company_id',
        'workspace_id',
        'sheet_source_id',
        'year_month',
        'month_start',
        'linkedin_ads_leads',
        'organic_leads',
        'email_leads',
        'google_ads_leads',
        'total_leads',
        'linkedin_ads_cost',
        'google_ads_cost',
        'total_ads_cost',
        'cost_per_conversion',
        'total_revenue_potential',
        'won_revenue_potential',
        'closed_won_count',
        'closed_lost_count',
        'proposal_sent_count',
        'initial_contact_count',
        'organic_opportunities_count',
        'total_opportunities_count',
        'mql_to_sql_rate',
        'sql_conversion_rate',
        'total_revenue_potential_2025',
        'won_revenue_potential_2025',
        'total_deals_2026',
        'total_deals_2025',
        'total_deals_2024',
        'romi_2025',
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'month_start' => 'date',
            'source_payload' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function sheetSource(): BelongsTo
    {
        return $this->belongsTo(SheetSource::class);
    }
}
