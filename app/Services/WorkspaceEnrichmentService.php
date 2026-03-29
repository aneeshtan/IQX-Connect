<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;

class WorkspaceEnrichmentService
{
    public function __construct(
        protected LeadScoringService $leadScoringService,
    ) {}

    public function contactInsights(Lead|Contact $lead): array
    {
        if ($lead instanceof Contact) {
            $lead->loadMissing('account');
            $lead->loadCount(['leads', 'opportunities', 'quotes', 'shipmentJobs', 'bookings', 'invoices']);

            $knownFields = collect([
                $lead->full_name,
                $lead->account?->name,
                $lead->email,
                $lead->phone,
            ])->filter(fn ($value) => filled($value))->count();

            return [
                'headline' => $lead->opportunities_count > 0
                    ? 'Contact linked to active commercial records.'
                    : 'Contact record ready for qualification and follow-up.',
                'summary' => trim(implode(' ', array_filter([
                    $lead->full_name ? "{$lead->full_name} is attached to ".($lead->account?->name ?: 'an account').'.' : null,
                    $lead->quotes_count > 0 ? "{$lead->quotes_count} quote records are linked." : null,
                    $lead->shipment_jobs_count > 0 ? "{$lead->shipment_jobs_count} shipment jobs are already tied to this contact." : null,
                    $lead->invoices_count > 0 ? "{$lead->invoices_count} invoices reference this contact." : null,
                ]))),
                'readiness' => match (true) {
                    $lead->shipment_jobs_count > 0 => 'Operational',
                    $lead->opportunities_count > 0 => 'Commercial',
                    $lead->leads_count > 0 => 'Qualified history',
                    default => 'Early stage',
                },
                'coverage' => "{$knownFields}/4 contact fields present",
                'signals' => array_values(array_filter([
                    $lead->account?->name ? "Account: {$lead->account->name}" : null,
                    $lead->email ? "Email: {$lead->email}" : null,
                    $lead->phone ? "Phone: {$lead->phone}" : null,
                    $lead->leads_count > 0 ? "{$lead->leads_count} leads" : null,
                    $lead->opportunities_count > 0 ? "{$lead->opportunities_count} opportunities" : null,
                    $lead->quotes_count > 0 ? "{$lead->quotes_count} quotes" : null,
                    $lead->shipment_jobs_count > 0 ? "{$lead->shipment_jobs_count} shipments" : null,
                ])),
                'recommendations' => array_values(array_filter([
                    blank($lead->email) ? 'Add a direct email for quoting and billing communication.' : null,
                    blank($lead->phone) ? 'Capture a phone number for urgent shipment coordination.' : null,
                    $lead->quotes_count === 0 && $lead->opportunities_count > 0 ? 'Create a quote if the commercial discussion is progressing.' : null,
                    $lead->shipment_jobs_count > 0 && $lead->invoices_count === 0 ? 'Review whether the related jobs are ready for invoicing.' : null,
                ])),
                'missing_fields' => array_values(array_filter([
                    blank($lead->full_name) ? 'Contact name' : null,
                    blank($lead->account?->name) ? 'Account link' : null,
                    blank($lead->email) ? 'Email' : null,
                    blank($lead->phone) ? 'Phone' : null,
                ])),
            ];
        }

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

    public function customerInsights(Opportunity|Account $opportunity): array
    {
        if ($opportunity instanceof Account) {
            $opportunity->loadMissing('contacts');
            $opportunity->loadCount(['contacts', 'leads', 'opportunities', 'quotes', 'shipmentJobs', 'bookings', 'invoices']);
            $revenue = (float) $opportunity->opportunities()->sum('revenue_potential');

            return [
                'headline' => $revenue >= 250000
                    ? 'High-value freight account with meaningful commercial history.'
                    : 'Customer account with linked sales and execution activity.',
                'summary' => trim(implode(' ', array_filter([
                    "{$opportunity->name} is now a first-class customer account.",
                    $opportunity->quotes_count > 0 ? "{$opportunity->quotes_count} quotes are linked." : null,
                    $opportunity->shipment_jobs_count > 0 ? "{$opportunity->shipment_jobs_count} shipment jobs are linked." : null,
                    $opportunity->invoices_count > 0 ? "{$opportunity->invoices_count} invoices are linked." : null,
                    $revenue > 0 ? 'Tracked opportunity value is AED '.number_format($revenue, 0).'.' : null,
                ]))),
                'tier' => match (true) {
                    $revenue >= 250000 => 'Strategic account',
                    $revenue >= 75000 => 'Expansion candidate',
                    default => 'Standard account',
                },
                'signals' => array_values(array_filter([
                    $opportunity->primary_email ? "Primary email: {$opportunity->primary_email}" : null,
                    $opportunity->latest_service ? "Latest service: {$opportunity->latest_service}" : null,
                    $opportunity->contacts_count > 0 ? "{$opportunity->contacts_count} linked contacts" : null,
                    $opportunity->opportunities_count > 0 ? "{$opportunity->opportunities_count} opportunities" : null,
                    $opportunity->quotes_count > 0 ? "{$opportunity->quotes_count} quotes" : null,
                    $opportunity->shipment_jobs_count > 0 ? "{$opportunity->shipment_jobs_count} shipments" : null,
                    $opportunity->invoices_count > 0 ? "{$opportunity->invoices_count} invoices" : null,
                ])),
                'recommendations' => array_values(array_filter([
                    blank($opportunity->primary_email) ? 'Capture a shared commercial email for this account.' : null,
                    $opportunity->contacts_count === 0 ? 'Create at least one named contact under this account.' : null,
                    $opportunity->shipment_jobs_count > 0 && $opportunity->invoices_count === 0 ? 'Review operational jobs that may be ready for billing.' : null,
                    $opportunity->quotes_count === 0 && $opportunity->opportunities_count > 0 ? 'Turn active opportunities into priced quotes sooner.' : null,
                ])),
                'missing_fields' => array_values(array_filter([
                    blank($opportunity->primary_email) ? 'Primary email' : null,
                    blank($opportunity->primary_phone) ? 'Primary phone' : null,
                    blank($opportunity->latest_service) ? 'Service profile' : null,
                ])),
            ];
        }

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
