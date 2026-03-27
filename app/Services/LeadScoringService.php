<?php

namespace App\Services;

use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LeadScoringService
{
    public function score(Lead $lead): array
    {
        $score = 0;
        $reasons = [];

        [$statusScore, $statusReason] = $this->statusScore($lead);
        $score += $statusScore;
        $reasons[] = $statusReason;

        [$serviceScore, $serviceReason] = $this->serviceScore($lead->service);
        $score += $serviceScore;
        $reasons[] = $serviceReason;

        [$sourceScore, $sourceReason] = $this->sourceScore($lead->lead_source);
        $score += $sourceScore;
        $reasons[] = $sourceReason;

        [$valueScore, $valueReason] = $this->valueScore($lead->lead_value);
        $score += $valueScore;
        $reasons[] = $valueReason;

        [$profileScore, $profileReasons] = $this->profileScore($lead);
        $score += $profileScore;
        $reasons = array_merge($reasons, $profileReasons);

        [$recencyScore, $recencyReason] = $this->recencyScore($lead->submission_date);
        $score += $recencyScore;
        $reasons[] = $recencyReason;

        [$sectorScore, $sectorReason] = $this->sectorFitScore($lead);
        $score += $sectorScore;
        $reasons[] = $sectorReason;

        $score = (int) max(0, min(100, $score));

        return [
            'score' => $score,
            'label' => $this->scoreLabel($score),
            'summary' => $this->scoreSummary($score),
            'reasons' => array_values(array_filter($reasons)),
        ];
    }

    protected function statusScore(Lead $lead): array
    {
        return match ($lead->status) {
            Lead::STATUS_SALES_QUALIFIED => [28, 'Already sales qualified, which is the strongest purchase-intent signal.'],
            Lead::STATUS_DISQUALIFIED => [-30, 'Currently disqualified, so the score stays low until the record is reopened.'],
            default => [16, 'Still in progress, so there is active potential but more qualification is needed.'],
        };
    }

    protected function serviceScore(?string $service): array
    {
        if (blank($service)) {
            return [0, 'The required service is still missing, which weakens qualification.'];
        }

        $service = Str::lower($service);

        return match (true) {
            Str::contains($service, ['container conversion']) => [20, 'Container conversion is typically a high-value maritime project.'],
            Str::contains($service, ['freight', 'ocean freight', 'shipping', 'cargo forwarding']) => [18, 'Freight-related demand is a strong fit for maritime and logistics sales.'],
            Str::contains($service, ['air freight']) => [14, 'Air freight is still a strong logistics signal with revenue potential.'],
            Str::contains($service, ['warehouse', 'customs', 'clearance', 'transport']) => [12, 'The requested service matches core logistics operations.'],
            default => [8, 'A service is defined, but it is not one of the strongest maritime-fit offers.'],
        };
    }

    protected function sourceScore(?string $source): array
    {
        if (blank($source)) {
            return [2, 'Lead source is unknown, so intent confidence is lower.'];
        }

        $source = Str::lower($source);

        return match (true) {
            Str::contains($source, ['quote form']) => [18, 'Quote-form leads usually have immediate buying intent.'],
            Str::contains($source, ['contact form', 'website']) => [16, 'Inbound website enquiries usually show strong commercial intent.'],
            Str::contains($source, ['google ads']) => [14, 'Google Ads leads are typically active demand rather than passive awareness.'],
            Str::contains($source, ['email', 'referral', 'partner']) => [15, 'Direct email or referral leads often come with stronger context and urgency.'],
            default => [8, 'The source is valid, but it is not one of the highest-intent inbound channels.'],
        };
    }

    protected function valueScore($leadValue): array
    {
        $value = (float) $leadValue;

        return match (true) {
            $value >= 250000 => [20, 'Pipeline value is large enough to justify priority follow-up.'],
            $value >= 100000 => [16, 'Lead value is strong for a maritime B2B opportunity.'],
            $value >= 50000 => [10, 'Lead value shows meaningful commercial potential.'],
            $value > 0 => [6, 'A value has been captured, but it still sits in the lower range.'],
            default => [0, 'No deal value has been captured yet.'],
        };
    }

    protected function profileScore(Lead $lead): array
    {
        $score = 0;
        $reasons = [];

        if (filled($lead->contact_name)) {
            $score += 8;
            $reasons[] = 'A named contact makes the lead easier to work and qualify.';
        } else {
            $reasons[] = 'Missing contact name lowers follow-up quality.';
        }

        if (filled($lead->company_name)) {
            $score += 8;
            $reasons[] = 'A known company improves account qualification.';
        } else {
            $reasons[] = 'Missing company name reduces account confidence.';
        }

        if (filled($lead->email)) {
            $domain = Str::after($lead->email, '@');
            $genericDomains = ['gmail.com', 'icloud.com', 'outlook.com', 'hotmail.com', 'yahoo.com'];
            $score += in_array(Str::lower($domain), $genericDomains, true) ? 4 : 8;
            $reasons[] = in_array(Str::lower($domain), $genericDomains, true)
                ? 'The lead has an email, but it is a generic mailbox rather than a company domain.'
                : 'A company-domain email improves trust and outreach quality.';
        } else {
            $reasons[] = 'Missing email reduces the ability to nurture the lead.';
        }

        if (filled($lead->phone)) {
            $score += 6;
            $reasons[] = 'A phone number supports faster sales follow-up.';
        } else {
            $reasons[] = 'No phone number is available for direct follow-up.';
        }

        if (filled($lead->notes)) {
            $score += 4;
            $reasons[] = 'Internal notes add context for qualification and handoff.';
        }

        return [$score, $reasons];
    }

    protected function recencyScore($submissionDate): array
    {
        if (! $submissionDate) {
            return [0, 'Submission date is missing, so freshness cannot be confirmed.'];
        }

        $days = Carbon::parse($submissionDate)->diffInDays(now());

        return match (true) {
            $days <= 7 => [10, 'Recent enquiries convert better because intent is still fresh.'],
            $days <= 30 => [6, 'The enquiry is still recent enough to be commercially active.'],
            $days <= 90 => [3, 'The lead is aging, which lowers conversion likelihood.'],
            default => [0, 'The lead is old and likely needs requalification.'],
        };
    }

    protected function sectorFitScore(Lead $lead): array
    {
        $signalText = Str::lower(trim(($lead->company_name ?: '').' '.($lead->service ?: '').' '.($lead->lead_source ?: '')));

        if ($signalText === '') {
            return [0, 'There is not enough sector context yet to detect maritime fit.'];
        }

        return Str::contains($signalText, ['marine', 'maritime', 'shipping', 'cargo', 'container', 'freight', 'logistics', 'port', 'vessel', 'offshore'])
            ? [8, 'The company or service language shows a clear maritime/logistics fit.']
            : [2, 'The lead may still fit, but the record does not strongly signal maritime relevance yet.'];
    }

    protected function scoreLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Hot',
            $score >= 60 => 'Warm',
            $score >= 40 => 'Monitor',
            default => 'Cold',
        };
    }

    protected function scoreSummary(int $score): string
    {
        return match (true) {
            $score >= 80 => 'High-priority lead with strong fit and intent.',
            $score >= 60 => 'Promising lead that should be actively worked.',
            $score >= 40 => 'Potential exists, but qualification gaps still need attention.',
            default => 'Low-priority lead until stronger buying signals appear.',
        };
    }
}
