<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Opportunity;

class WorkspaceEnrichmentService
{
    public function __construct(
        protected LeadScoringService $leadScoringService,
    ) {}

    public function contactInsights(Lead $lead): array
    {
        $lead->loadMissing('assignedUser');
        $lead->loadCount('opportunities');
        $leadScore = $this->leadScoringService->score($lead);

        $knownFields = collect([
            $lead->contact_name,
            $lead->company_name,
            $lead->email,
            $lead->phone,
            $lead->service,
            $lead->lead_source,
        ])->filter(fn ($value) => filled($value))->count();

        $readiness = match (true) {
            $lead->status === Lead::STATUS_SALES_QUALIFIED && (float) $lead->lead_value >= 100000 => 'High intent',
            $lead->status === Lead::STATUS_SALES_QUALIFIED => 'Qualified',
            $lead->status === Lead::STATUS_DISQUALIFIED => 'Low priority',
            default => 'Active nurture',
        };

        $headline = match (true) {
            $lead->status === Lead::STATUS_SALES_QUALIFIED => 'Sales-qualified contact with active buying signals.',
            $lead->status === Lead::STATUS_DISQUALIFIED => 'Disqualified contact that should stay out of the active pipeline.',
            default => 'Open contact record that still needs qualification or follow-up.',
        };

        $signals = array_values(array_filter([
            $lead->lead_source ? "Source: {$lead->lead_source}" : null,
            $lead->service ? "Interested in {$lead->service}" : null,
            $lead->submission_date ? 'Added '.$lead->submission_date->diffForHumans() : null,
            $lead->lead_value ? 'Estimated value AED '.number_format((float) $lead->lead_value, 0) : null,
            $lead->opportunities_count > 0 ? "{$lead->opportunities_count} linked opportunity records" : 'No linked opportunity yet',
            $lead->assignedUser?->name ? "Owner: {$lead->assignedUser->name}" : 'No owner assigned',
        ]));

        $recommendations = array_values(array_filter([
            $lead->status === Lead::STATUS_IN_PROGRESS ? 'Confirm qualification and move the contact to Sales Qualified or Disqualified.' : null,
            $lead->status === Lead::STATUS_SALES_QUALIFIED && $lead->opportunities_count === 0 ? 'Create or update an opportunity so the sales team can forecast revenue.' : null,
            blank($lead->phone) ? 'Add a direct phone number to improve contactability.' : null,
            blank($lead->email) ? 'Capture a working email before handoff.' : null,
            blank($lead->notes) ? 'Add context notes so the next user understands the requirement and urgency.' : null,
        ]));

        $missingFields = array_values(array_filter([
            blank($lead->contact_name) ? 'Contact name' : null,
            blank($lead->company_name) ? 'Company name' : null,
            blank($lead->email) ? 'Email' : null,
            blank($lead->phone) ? 'Phone' : null,
            blank($lead->service) ? 'Service' : null,
            blank($lead->notes) ? 'Notes' : null,
        ]));

        return [
            'headline' => $headline,
            'summary' => trim(implode(' ', array_filter([
                $lead->contact_name ? "{$lead->contact_name} is attached to {$lead->company_name}." : null,
                $lead->lead_source ? "This contact entered through {$lead->lead_source}." : null,
                $lead->service ? "The current service interest is {$lead->service}." : null,
                $lead->lead_value ? 'The potential deal size is about AED '.number_format((float) $lead->lead_value, 0).'.' : null,
                "Lead score: {$leadScore['score']}/100 ({$leadScore['label']}).",
            ]))),
            'readiness' => $readiness,
            'coverage' => "{$knownFields}/6 core fields present",
            'lead_score' => $leadScore['score'],
            'lead_score_label' => $leadScore['label'],
            'lead_score_summary' => $leadScore['summary'],
            'lead_score_reasons' => $leadScore['reasons'],
            'signals' => $signals,
            'recommendations' => $recommendations,
            'missing_fields' => $missingFields,
        ];
    }

    public function customerInsights(Opportunity $opportunity): array
    {
        $opportunity->loadMissing(['lead', 'assignedUser']);

        $expansionTier = match (true) {
            (float) $opportunity->revenue_potential >= 250000 => 'Strategic account',
            (float) $opportunity->revenue_potential >= 75000 => 'Expansion candidate',
            default => 'Standard account',
        };

        $headline = match (true) {
            (float) $opportunity->revenue_potential >= 250000 => 'High-value customer with strong expansion potential.',
            filled($opportunity->required_service) => "Closed-won customer for {$opportunity->required_service}.",
            default => 'Closed-won customer record ready for follow-up and retention.',
        };

        $signals = array_values(array_filter([
            $opportunity->company_name ? "Customer: {$opportunity->company_name}" : null,
            $opportunity->required_service ? "Won service: {$opportunity->required_service}" : null,
            $opportunity->lead_source ? "Original source: {$opportunity->lead_source}" : null,
            $opportunity->submission_date ? 'Won record created '.$opportunity->submission_date->diffForHumans() : null,
            $opportunity->revenue_potential ? 'Converted value AED '.number_format((float) $opportunity->revenue_potential, 0) : null,
            $opportunity->assignedUser?->name ? "Owner: {$opportunity->assignedUser->name}" : null,
        ]));

        $recommendations = array_values(array_filter([
            filled($opportunity->required_service) ? "Use the {$opportunity->required_service} win to open cross-sell discussions." : 'Identify the next service offer for this customer.',
            blank($opportunity->notes) ? 'Add delivery or relationship notes so account management has context.' : null,
            blank($opportunity->contact_email) ? 'Capture a customer email for post-sale follow-up and renewal sequences.' : null,
            $opportunity->project_timeline_days ? "Plan a follow-up before day {$opportunity->project_timeline_days} to look for the next requirement." : 'Set a post-win review cadence for this account.',
        ]));

        $relationshipSummary = trim(implode(' ', array_filter([
            $opportunity->company_name ? "{$opportunity->company_name} is now a customer." : null,
            $opportunity->lead_source ? "The account originated from {$opportunity->lead_source}." : null,
            $opportunity->revenue_potential ? 'The converted value is AED '.number_format((float) $opportunity->revenue_potential, 0).'.' : null,
            $opportunity->lead?->status ? "The linked lead status is {$opportunity->lead->status}." : null,
        ])));

        return [
            'headline' => $headline,
            'summary' => $relationshipSummary,
            'tier' => $expansionTier,
            'signals' => $signals,
            'recommendations' => $recommendations,
            'missing_fields' => array_values(array_filter([
                blank($opportunity->contact_email) ? 'Customer email' : null,
                blank($opportunity->notes) ? 'Post-win notes' : null,
                blank($opportunity->required_service) ? 'Won service' : null,
            ])),
        ];
    }
}
