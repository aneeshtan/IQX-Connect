<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Services\LeadScoringService;
use Carbon\Carbon;
use Tests\TestCase;

class LeadScoringServiceTest extends TestCase
{
    public function test_it_scores_a_strong_maritime_inbound_lead_as_hot(): void
    {
        Carbon::setTestNow('2026-03-27 12:00:00');

        $lead = new Lead([
            'status' => Lead::STATUS_SALES_QUALIFIED,
            'service' => 'Ocean Freight',
            'lead_source' => 'Website Quote Form',
            'lead_value' => 180000,
            'contact_name' => 'Ava Rahman',
            'company_name' => 'Blue Harbor Logistics',
            'email' => 'ava@blueharborlogistics.com',
            'phone' => '+971500000000',
            'notes' => 'Urgent shipping requirement for Q2',
            'submission_date' => now()->subDays(2),
        ]);

        $result = app(LeadScoringService::class)->score($lead);

        $this->assertGreaterThanOrEqual(80, $result['score']);
        $this->assertSame('Hot', $result['label']);
        $this->assertNotEmpty($result['reasons']);

        Carbon::setTestNow();
    }

    public function test_it_scores_a_disqualified_incomplete_lead_as_cold(): void
    {
        Carbon::setTestNow('2026-03-27 12:00:00');

        $lead = new Lead([
            'status' => Lead::STATUS_DISQUALIFIED,
            'service' => null,
            'lead_source' => null,
            'lead_value' => null,
            'contact_name' => null,
            'company_name' => null,
            'email' => 'contact@gmail.com',
            'phone' => null,
            'notes' => null,
            'submission_date' => now()->subDays(180),
        ]);

        $result = app(LeadScoringService::class)->score($lead);

        $this->assertLessThan(40, $result['score']);
        $this->assertSame('Cold', $result['label']);

        Carbon::setTestNow();
    }
}
