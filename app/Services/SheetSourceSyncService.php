<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Carrier;
use App\Models\Lead;
use App\Models\MonthlyReport;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\SheetSource;
use App\Models\ShipmentJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SheetSourceSyncService
{
    public function importLeadCsvForWorkspace(SheetSource $source, string $contents): int
    {
        return $this->importCsvForSource($source, $contents);
    }

    public function importCsvForSource(SheetSource $source, string $contents): int
    {
        $source->forceFill([
            'sync_status' => 'syncing',
            'last_error' => null,
        ])->save();

        try {
            $rows = $this->parseCsv($contents);

            $count = DB::transaction(fn () => match ($source->type) {
                SheetSource::TYPE_LEADS => $this->syncLeads($source, $rows),
                SheetSource::TYPE_OPPORTUNITIES => $this->syncOpportunities($source, $rows),
                SheetSource::TYPE_CONTACTS => $this->syncLeads($source, $rows),
                SheetSource::TYPE_CUSTOMERS => $this->syncOpportunities($source, $rows),
                SheetSource::TYPE_QUOTES => $this->syncQuotes($source, $rows),
                SheetSource::TYPE_SHIPMENTS => $this->syncShipmentJobs($source, $rows),
                SheetSource::TYPE_CARRIERS => $this->syncCarriers($source, $rows),
                SheetSource::TYPE_BOOKINGS => $this->syncBookings($source, $rows),
                SheetSource::TYPE_REPORTS => $this->syncMonthlyReports($source, $rows),
                SheetSource::TYPE_GOOGLE_ADS => 0,
                default => throw new RuntimeException('Sync is not available for '.SheetSource::typeLabel($source->type).' sources yet.'),
            });

            $source->forceFill([
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'last_error' => null,
            ])->save();

            return $count;
        } catch (Throwable $exception) {
            $source->forceFill([
                'sync_status' => 'failed',
                'last_error' => Str::limit($exception->getMessage(), 4000),
            ])->save();

            report($exception);

            throw $exception;
        }
    }

    public function sync(SheetSource $source): int
    {
        $source->forceFill([
            'sync_status' => 'syncing',
            'last_error' => null,
        ])->save();

        try {
            $rows = $this->downloadRowsForSource($source);

            $count = DB::transaction(fn () => match ($source->type) {
                SheetSource::TYPE_LEADS => $this->syncLeads($source, $rows),
                SheetSource::TYPE_OPPORTUNITIES => $this->syncOpportunities($source, $rows),
                SheetSource::TYPE_CONTACTS => $this->syncLeads($source, $rows),
                SheetSource::TYPE_CUSTOMERS => $this->syncOpportunities($source, $rows),
                SheetSource::TYPE_QUOTES => $this->syncQuotes($source, $rows),
                SheetSource::TYPE_SHIPMENTS => $this->syncShipmentJobs($source, $rows),
                SheetSource::TYPE_CARRIERS => $this->syncCarriers($source, $rows),
                SheetSource::TYPE_BOOKINGS => $this->syncBookings($source, $rows),
                SheetSource::TYPE_REPORTS => $this->syncMonthlyReports($source, $rows),
                SheetSource::TYPE_GOOGLE_ADS => 0,
                default => throw new RuntimeException('Sync is not available for '.SheetSource::typeLabel($source->type).' sources yet.'),
            });

            $source->forceFill([
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'last_error' => null,
            ])->save();

            return $count;
        } catch (Throwable $exception) {
            $source->forceFill([
                'sync_status' => 'failed',
                'last_error' => Str::limit($exception->getMessage(), 4000),
            ])->save();

            report($exception);

            throw $exception;
        }
    }

    protected function syncLeads(SheetSource $source, array $rows): int
    {
        if (! $source->workspace_id) {
            throw new RuntimeException('Lead sources must be attached to a workspace.');
        }

        $count = 0;

        foreach ($rows as $row) {
            $leadId = $this->value($row, ['Lead ID']);
            $rfid = $this->value($row, ['RFID']);
            $leadKey = $this->value($row, ['LeadKey']);

            $externalKey = $leadId
                ?: $rfid
                ?: $leadKey
                ?: sha1(json_encode([
                    $this->value($row, ['Email']),
                    $this->value($row, ['Submission Create Date']),
                    $this->value($row, ['Company name', 'Company Name', 'Company']),
                ]));

            if (! $externalKey) {
                continue;
            }

            $lead = Lead::firstOrNew([
                'workspace_id' => $source->workspace_id,
                'external_key' => $externalKey,
            ]);

            $incomingStatus = $this->normalizeLeadStatus($this->value($row, ['Lead Status']));

            $lead->fill([
                'company_id' => $source->company_id,
                'workspace_id' => $source->workspace_id,
                'sheet_source_id' => $source->id,
                'lead_id' => $leadId,
                'rfid' => $rfid,
                'lead_key' => $leadKey,
                'contact_name' => $this->value($row, ['Column 1', 'Name', 'Contact Name', 'Full Name']),
                'company_name' => $this->value($row, ['Company name', 'Company Name', 'Company']),
                'email' => $this->value($row, ['Email']),
                'phone' => $this->value($row, ['Phone number']),
                'service' => $this->normalizeService($this->value($row, ['Service'])),
                'submission_date' => $this->parseDate($this->value($row, ['Submission Create Date'])),
                'lead_source' => $this->value($row, ['Lead Source']),
                'status' => $lead->exists ? $lead->status : $incomingStatus,
                'disqualification_reason' => $lead->disqualification_reason ?: $this->value($row, ['Reason of Disqualification']),
                'notes' => $lead->notes ?: $this->value($row, ['Note']),
                'nurture_minutes' => $this->parseInteger($this->value($row, ['Time to Nurture (minutes)'])),
                'nurture_hours' => $this->parseMoney($this->value($row, ['Time to Nurture (hours)'])),
                'lead_value' => $this->parseMoney($this->value($row, ['Lead Value'])),
                'hashed_email' => $this->value($row, ['Hashed Email']),
                'hashed_phone' => $this->value($row, ['Hashed Phone']),
                'is_converted' => $this->parseBoolean($this->value($row, ['Is Coverted'])),
                'manual_entry' => false,
                'source_payload' => $row,
            ]);

            $lead->save();

            $count++;
        }

        return $count;
    }

    protected function syncOpportunities(SheetSource $source, array $rows): int
    {
        if (! $source->workspace_id) {
            throw new RuntimeException('Opportunity sources must be attached to a workspace.');
        }

        $count = 0;

        foreach ($rows as $row) {
            $rfid = $this->value($row, ['RFID']);
            $leadReference = $this->value($row, ['Lead ID']);

            $externalKey = $rfid
                ?: $leadReference
                ?: sha1(json_encode([
                    $this->value($row, ['Company Name']),
                    $this->value($row, ['Contact Email']),
                    $this->value($row, ['Submission Date']),
                ]));

            if (! $externalKey) {
                continue;
            }

            $opportunity = Opportunity::firstOrNew([
                'workspace_id' => $source->workspace_id,
                'external_key' => $externalKey,
            ]);

            $incomingStage = $this->normalizeOpportunityStage($this->value($row, ['Sales Stage']));

            $opportunity->fill([
                'company_id' => $source->company_id,
                'workspace_id' => $source->workspace_id,
                'sheet_source_id' => $source->id,
                'lead_id' => $this->resolveLeadId($source, $leadReference, $rfid),
                'rfid' => $rfid,
                'lead_reference' => $leadReference,
                'company_name' => $this->value($row, ['Company Name']),
                'contact_email' => $this->value($row, ['Contact Email']),
                'lead_source' => $this->value($row, ['Lead Source']),
                'required_service' => $this->normalizeService($this->value($row, ['Required Service'])),
                'revenue_potential' => $this->parseMoney($this->value($row, ['Revenue Potential (AED)'])),
                'project_timeline_days' => $this->parseInteger($this->value($row, ['Project Timeline (days)'])),
                'sales_stage' => $opportunity->exists ? $opportunity->sales_stage : $incomingStage,
                'reason_for_loss' => $opportunity->reason_for_loss ?: $this->value($row, ['Reason for Loss']),
                'notes' => $opportunity->notes ?: $this->value($row, ['Notes']),
                'submission_date' => $this->parseDate($this->value($row, ['Submission Date'])),
                'year_month' => $this->normalizeYearMonth($this->value($row, ['YearMonth'])),
                'manual_entry' => false,
                'source_payload' => $row,
            ]);

            $opportunity->save();

            $count++;
        }

        return $count;
    }

    protected function syncMonthlyReports(SheetSource $source, array $rows): int
    {
        $count = 0;

        foreach ($rows as $row) {
            $yearMonth = $this->normalizeYearMonth($this->value($row, ['YearMonth']));

            if (! $yearMonth) {
                continue;
            }

            MonthlyReport::updateOrCreate(
                [
                    'company_id' => $source->company_id,
                    'workspace_id' => $source->workspace_id,
                    'year_month' => $yearMonth,
                ],
                [
                    'sheet_source_id' => $source->id,
                    'month_start' => $this->parseMonth($yearMonth),
                    'linkedin_ads_leads' => $this->parseInteger($this->value($row, ['LinkedIn Ads Leads'])) ?? 0,
                    'organic_leads' => $this->parseInteger($this->value($row, ['Organic Leads'])) ?? 0,
                    'email_leads' => $this->parseInteger($this->value($row, ['Email Leads'])) ?? 0,
                    'google_ads_leads' => $this->parseInteger($this->value($row, ['Google Ads Leads'])) ?? 0,
                    'total_leads' => $this->parseInteger($this->value($row, ['Total Leads'])) ?? 0,
                    'linkedin_ads_cost' => $this->parseMoney($this->value($row, ['LinkedIn Ads Cost (AED)'])) ?? 0,
                    'google_ads_cost' => $this->parseMoney($this->value($row, ['Google Ads Cost (AED)'])) ?? 0,
                    'total_ads_cost' => $this->parseMoney($this->value($row, ['Total Ads Cost (AED)'])) ?? 0,
                    'cost_per_conversion' => $this->parseMoney($this->value($row, ['Cost Per Conversion (AED)'])) ?? 0,
                    'total_revenue_potential' => $this->parseMoney($this->value($row, ['Total Revenue Potential (AED)'])) ?? 0,
                    'won_revenue_potential' => $this->parseMoney($this->value($row, ['Won Revenue Potential (AED)'])) ?? 0,
                    'closed_won_count' => $this->parseInteger($this->value($row, ['No of Closed Won'])) ?? 0,
                    'closed_lost_count' => $this->parseInteger($this->value($row, ['No of Closed Last'])) ?? 0,
                    'proposal_sent_count' => $this->parseInteger($this->value($row, ['No of Proposal Sent'])) ?? 0,
                    'initial_contact_count' => $this->parseInteger($this->value($row, ['No of Initial Contact'])) ?? 0,
                    'organic_opportunities_count' => $this->parseInteger($this->value($row, ['Total Organic Opportunities'])) ?? 0,
                    'total_opportunities_count' => $this->parseInteger($this->value($row, ['Total Opportunities'])) ?? 0,
                    'mql_to_sql_rate' => $this->parseMoney($this->value($row, ['MQL to SQL Rate'])) ?? 0,
                    'sql_conversion_rate' => $this->parseMoney($this->value($row, ['SQL conversion rate'])) ?? 0,
                    'total_revenue_potential_2025' => $this->parseMoney($this->value($row, ['Total Revenue Potential (AED) in 2025'])) ?? 0,
                    'won_revenue_potential_2025' => $this->parseMoney($this->value($row, ['Won Revenue Potential (AED) in 2025'])) ?? 0,
                    'total_deals_2026' => $this->parseInteger($this->value($row, ['Total Deals 2026'])) ?? 0,
                    'total_deals_2025' => $this->parseInteger($this->value($row, ['Total Deals 2025'])) ?? 0,
                    'total_deals_2024' => $this->parseInteger($this->value($row, ['Total Deals 2024'])) ?? 0,
                    'romi_2025' => $this->parseMoney($this->value($row, ['Romi 2025'])) ?? 0,
                    'source_payload' => $row,
                ],
            );

            $count++;
        }

        return $count;
    }

    protected function syncQuotes(SheetSource $source, array $rows): int
    {
        if (! $source->workspace_id) {
            throw new RuntimeException('Quote sources must be attached to a workspace.');
        }

        $count = 0;

        foreach ($rows as $row) {
            $quoteNumber = $this->value($row, ['Quote Number', 'Quote ID', 'Quote Ref', 'Quote Reference']);

            if (! $quoteNumber) {
                $quoteNumber = 'SRC-'.strtoupper(substr(sha1(json_encode([
                    $this->value($row, ['Company Name', 'Company']),
                    $this->value($row, ['Contact Email', 'Email']),
                    $this->value($row, ['Quoted At', 'Quote Date']),
                    $this->value($row, ['Origin']),
                    $this->value($row, ['Destination']),
                ])), 0, 10));
            }

            $quote = Quote::firstOrNew([
                'workspace_id' => $source->workspace_id,
                'quote_number' => $quoteNumber,
            ]);

            $incomingStatus = $this->normalizeQuoteStatus($this->value($row, ['Status', 'Quote Status']));

            $payload = [
                'company_id' => $source->company_id,
                'workspace_id' => $source->workspace_id,
                'sheet_source_id' => $source->id,
                'lead_id' => $this->resolveLeadId(
                    $source,
                    $this->value($row, ['Lead ID']),
                    $this->value($row, ['RFID']),
                ),
                'opportunity_id' => $this->resolveOpportunityId(
                    $source,
                    $this->value($row, ['Opportunity ID', 'Opportunity Key']),
                    $this->value($row, ['Lead ID']),
                    $this->value($row, ['RFID']),
                ),
                'quote_number' => $quoteNumber,
                'company_name' => $this->value($row, ['Company Name', 'Company']),
                'contact_name' => $this->value($row, ['Contact Name', 'Name', 'Full Name']),
                'contact_email' => $this->value($row, ['Contact Email', 'Email']),
                'service_mode' => $this->normalizeService($this->value($row, ['Service Mode', 'Required Service', 'Service'])),
                'origin' => $this->value($row, ['Origin']),
                'destination' => $this->value($row, ['Destination']),
                'incoterm' => $this->value($row, ['Incoterm']),
                'commodity' => $this->value($row, ['Commodity']),
                'equipment_type' => $this->value($row, ['Equipment Type', 'Container Type']),
                'weight_kg' => $this->parseMoney($this->value($row, ['Weight Kg', 'Weight (Kg)', 'Weight'])),
                'volume_cbm' => $this->parseMoney($this->value($row, ['Volume Cbm', 'Volume (CBM)', 'Volume'])),
                'buy_amount' => $this->parseMoney($this->value($row, ['Buy Amount', 'Buy Rate', 'Buy'])),
                'sell_amount' => $this->parseMoney($this->value($row, ['Sell Amount', 'Sell Rate', 'Sell'])),
                'currency' => $this->value($row, ['Currency']) ?: 'AED',
                'status' => $quote->exists ? $quote->status : $incomingStatus,
                'valid_until' => $this->parseDate($this->value($row, ['Valid Until', 'Validity Date'])),
                'quoted_at' => $this->parseDate($this->value($row, ['Quoted At', 'Quote Date', 'Submission Date'])),
                'notes' => $quote->notes ?: $this->value($row, ['Notes', 'Note']),
                'source_payload' => $row,
            ];

            $payload['margin_amount'] = $this->resolveMarginAmount($row, $payload);

            $quote->fill($payload);
            $quote->save();

            $count++;
        }

        return $count;
    }

    protected function syncShipmentJobs(SheetSource $source, array $rows): int
    {
        if (! $source->workspace_id) {
            throw new RuntimeException('Shipment sources must be attached to a workspace.');
        }

        $count = 0;

        foreach ($rows as $row) {
            $jobNumber = $this->value($row, ['Job Number', 'Shipment Number', 'Shipment Job Number', 'Shipment Job']);

            if (! $jobNumber) {
                $jobNumber = 'SJ-'.strtoupper(substr(sha1(json_encode([
                    $this->value($row, ['Company Name', 'Company']),
                    $this->value($row, ['Contact Email', 'Email']),
                    $this->value($row, ['Origin']),
                    $this->value($row, ['Destination']),
                    $this->value($row, ['ETD', 'Estimated Departure', 'Estimated Departure At']),
                ])), 0, 10));
            }

            $shipment = ShipmentJob::firstOrNew([
                'workspace_id' => $source->workspace_id,
                'job_number' => $jobNumber,
            ]);

            $incomingStatus = $this->normalizeShipmentStatus($this->value($row, ['Status', 'Shipment Status', 'Job Status']));

            $payload = [
                'company_id' => $source->company_id,
                'workspace_id' => $source->workspace_id,
                'sheet_source_id' => $source->id,
                'lead_id' => $this->resolveLeadId(
                    $source,
                    $this->value($row, ['Lead ID']),
                    $this->value($row, ['RFID']),
                ),
                'opportunity_id' => $this->resolveOpportunityId(
                    $source,
                    $this->value($row, ['Opportunity ID', 'Opportunity Key']),
                    $this->value($row, ['Lead ID']),
                    $this->value($row, ['RFID']),
                ),
                'quote_id' => $this->resolveQuoteId(
                    $source,
                    $this->value($row, ['Quote Number', 'Quote ID', 'Quote Ref']),
                ),
                'job_number' => $jobNumber,
                'external_reference' => $this->value($row, ['External Reference', 'Shipment Reference', 'CW Ref']),
                'company_name' => $this->value($row, ['Company Name', 'Company']),
                'contact_name' => $this->value($row, ['Contact Name', 'Name', 'Full Name']),
                'contact_email' => $this->value($row, ['Contact Email', 'Email']),
                'service_mode' => $this->normalizeService($this->value($row, ['Service Mode', 'Mode', 'Shipment Mode', 'Required Service', 'Service'])),
                'origin' => $this->value($row, ['Origin']),
                'destination' => $this->value($row, ['Destination']),
                'incoterm' => $this->value($row, ['Incoterm']),
                'commodity' => $this->value($row, ['Commodity']),
                'equipment_type' => $this->value($row, ['Equipment Type', 'Container Type']),
                'container_count' => $this->parseInteger($this->value($row, ['Container Count', 'Containers'])),
                'weight_kg' => $this->parseMoney($this->value($row, ['Weight Kg', 'Weight (Kg)', 'Weight'])),
                'volume_cbm' => $this->parseMoney($this->value($row, ['Volume Cbm', 'Volume (CBM)', 'Volume'])),
                'carrier_name' => $this->value($row, ['Carrier Name', 'Carrier']),
                'vessel_name' => $this->value($row, ['Vessel Name', 'Vessel']),
                'voyage_number' => $this->value($row, ['Voyage Number', 'Voyage']),
                'house_bill_no' => $this->value($row, ['House Bill No', 'HBL', 'HAWB']),
                'master_bill_no' => $this->value($row, ['Master Bill No', 'MBL', 'MAWB']),
                'estimated_departure_at' => $this->parseDate($this->value($row, ['ETD', 'Estimated Departure', 'Estimated Departure At'])),
                'estimated_arrival_at' => $this->parseDate($this->value($row, ['ETA', 'Estimated Arrival', 'Estimated Arrival At'])),
                'actual_departure_at' => $this->parseDate($this->value($row, ['ATD', 'Actual Departure', 'Actual Departure At'])),
                'actual_arrival_at' => $this->parseDate($this->value($row, ['ATA', 'Actual Arrival', 'Actual Arrival At'])),
                'status' => $shipment->exists ? $shipment->status : $incomingStatus,
                'buy_amount' => $this->parseMoney($this->value($row, ['Buy Amount', 'Buy Rate', 'Buy'])),
                'sell_amount' => $this->parseMoney($this->value($row, ['Sell Amount', 'Sell Rate', 'Sell'])),
                'currency' => $this->value($row, ['Currency']) ?: 'AED',
                'notes' => $shipment->notes ?: $this->value($row, ['Notes', 'Note']),
                'source_payload' => $row,
            ];

            $payload['margin_amount'] = $this->resolveMarginAmount($row, $payload);

            $shipment->fill($payload);
            $shipment->save();

            $count++;
        }

        return $count;
    }

    protected function syncCarriers(SheetSource $source, array $rows): int
    {
        if (! $source->workspace_id) {
            throw new RuntimeException('Carrier sources must be attached to a workspace.');
        }

        $count = 0;

        foreach ($rows as $row) {
            $name = $this->value($row, ['Carrier Name', 'Carrier', 'Name']);

            if (! $name) {
                continue;
            }

            $carrier = Carrier::firstOrNew([
                'workspace_id' => $source->workspace_id,
                'name' => $name,
            ]);

            $carrier->fill([
                'company_id' => $source->company_id,
                'workspace_id' => $source->workspace_id,
                'sheet_source_id' => $source->id,
                'name' => $name,
                'mode' => $this->normalizeCarrierMode($this->value($row, ['Mode', 'Service Mode', 'Carrier Mode'])),
                'code' => $this->value($row, ['Carrier Code', 'Code']),
                'scac_code' => $this->value($row, ['SCAC', 'SCAC Code']),
                'iata_code' => $this->value($row, ['IATA', 'IATA Code']),
                'contact_name' => $this->value($row, ['Contact Name', 'Name']),
                'contact_email' => $this->value($row, ['Contact Email', 'Email']),
                'contact_phone' => $this->value($row, ['Contact Phone', 'Phone']),
                'website' => $this->value($row, ['Website']),
                'service_lanes' => $this->value($row, ['Service Lanes', 'Lanes', 'Coverage']),
                'notes' => $carrier->notes ?: $this->value($row, ['Notes', 'Note']),
                'is_active' => $this->parseBoolean($this->value($row, ['Is Active', 'Active'])),
                'source_payload' => $row,
            ]);

            if (! filled($this->value($row, ['Is Active', 'Active']))) {
                $carrier->is_active = true;
            }

            $carrier->save();
            $count++;
        }

        return $count;
    }

    protected function syncBookings(SheetSource $source, array $rows): int
    {
        if (! $source->workspace_id) {
            throw new RuntimeException('Booking sources must be attached to a workspace.');
        }

        $count = 0;

        foreach ($rows as $row) {
            $bookingNumber = $this->value($row, ['Booking Number', 'Booking Ref', 'Booking Reference']);

            if (! $bookingNumber) {
                $bookingNumber = 'BK-'.strtoupper(substr(sha1(json_encode([
                    $this->value($row, ['Customer Name', 'Company Name', 'Company']),
                    $this->value($row, ['Carrier Name', 'Carrier']),
                    $this->value($row, ['Requested ETD', 'ETD']),
                    $this->value($row, ['Origin']),
                    $this->value($row, ['Destination']),
                ])), 0, 10));
            }

            $booking = Booking::firstOrNew([
                'workspace_id' => $source->workspace_id,
                'booking_number' => $bookingNumber,
            ]);

            $booking->fill([
                'company_id' => $source->company_id,
                'workspace_id' => $source->workspace_id,
                'sheet_source_id' => $source->id,
                'carrier_id' => $this->resolveCarrierId($source, $this->value($row, ['Carrier Name', 'Carrier'])),
                'shipment_job_id' => $this->resolveShipmentId($source, $this->value($row, ['Shipment Job', 'Job Number', 'Shipment Number'])),
                'quote_id' => $this->resolveQuoteId($source, $this->value($row, ['Quote Number', 'Quote ID', 'Quote Ref'])),
                'opportunity_id' => $this->resolveOpportunityId(
                    $source,
                    $this->value($row, ['Opportunity ID', 'Opportunity Key']),
                    $this->value($row, ['Lead ID']),
                    $this->value($row, ['RFID']),
                ),
                'lead_id' => $this->resolveLeadId(
                    $source,
                    $this->value($row, ['Lead ID']),
                    $this->value($row, ['RFID']),
                ),
                'booking_number' => $bookingNumber,
                'external_reference' => $this->value($row, ['External Reference', 'Booking External Ref']),
                'carrier_confirmation_ref' => $this->value($row, ['Carrier Confirmation Ref', 'Carrier Booking Ref', 'Confirmation Number']),
                'customer_name' => $this->value($row, ['Customer Name', 'Company Name', 'Company']) ?: 'Unknown customer',
                'contact_name' => $this->value($row, ['Contact Name', 'Name']),
                'contact_email' => $this->value($row, ['Contact Email', 'Email']),
                'service_mode' => $this->normalizeService($this->value($row, ['Service Mode', 'Mode', 'Shipment Mode', 'Required Service', 'Service'])),
                'origin' => $this->value($row, ['Origin']),
                'destination' => $this->value($row, ['Destination']),
                'incoterm' => $this->value($row, ['Incoterm']),
                'commodity' => $this->value($row, ['Commodity']),
                'equipment_type' => $this->value($row, ['Equipment Type', 'Container Type']),
                'container_count' => $this->parseInteger($this->value($row, ['Container Count', 'Containers'])),
                'weight_kg' => $this->parseMoney($this->value($row, ['Weight Kg', 'Weight (Kg)', 'Weight'])),
                'volume_cbm' => $this->parseMoney($this->value($row, ['Volume Cbm', 'Volume (CBM)', 'Volume'])),
                'requested_etd' => $this->parseDate($this->value($row, ['Requested ETD', 'ETD'])),
                'requested_eta' => $this->parseDate($this->value($row, ['Requested ETA', 'ETA'])),
                'confirmed_etd' => $this->parseDate($this->value($row, ['Confirmed ETD', 'Booked ETD'])),
                'confirmed_eta' => $this->parseDate($this->value($row, ['Confirmed ETA', 'Booked ETA'])),
                'status' => $booking->exists
                    ? $booking->status
                    : $this->normalizeBookingStatus($this->value($row, ['Status', 'Booking Status'])),
                'notes' => $booking->notes ?: $this->value($row, ['Notes', 'Note']),
                'source_payload' => $row,
            ]);

            $booking->save();
            $this->applyBookingShipmentConnection($booking->fresh(['carrier']));
            $count++;
        }

        return $count;
    }

    protected function downloadRows(string $url): array
    {
        $response = Http::timeout(30)
            ->retry(2, 500)
            ->get($this->normalizeUrl($url))
            ->throw()
            ->body();

        return $this->parseCsv($response);
    }

    protected function downloadRowsForSource(SheetSource $source): array
    {
        if ($source->source_kind === SheetSource::SOURCE_KIND_CARGOWISE_API) {
            return app(CargoWiseSourceService::class)->readRows($source);
        }

        $googleSheets = app(GoogleSheetsService::class);

        if ($source->source_kind === SheetSource::SOURCE_KIND_GOOGLE_SHEETS_API) {
            try {
                return $googleSheets->readRows($source);
            } catch (RuntimeException $exception) {
                if ($this->canFallbackToPublicCsv($source, $exception)) {
                    $rows = $this->downloadRows($source->url);

                    if (! filled(data_get($source->mapping, 'spreadsheet_id'))) {
                        $source->forceFill([
                            'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEET_CSV,
                        ])->save();
                    }

                    return $rows;
                }

                throw $exception;
            }
        }

        return $this->downloadRows($source->url);
    }

    protected function parseCsv(string $contents): array
    {
        $stream = fopen('php://temp', 'r+');

        fwrite($stream, $contents);
        rewind($stream);

        $headers = fgetcsv($stream, 0, ',', '"', '\\');

        if (! $headers) {
            return [];
        }

        $headers = array_map(fn ($header) => trim((string) $header), $headers);

        $rows = [];

        while (($row = fgetcsv($stream, 0, ',', '"', '\\')) !== false) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $rows[] = array_combine($headers, array_map(
                fn ($value) => is_string($value) ? trim($value) : $value,
                array_slice($row, 0, count($headers)),
            ));
        }

        fclose($stream);

        return $rows;
    }

    protected function normalizeUrl(string $url): string
    {
        if (! str_contains($url, 'docs.google.com/spreadsheets/d/')) {
            return $url;
        }

        preg_match('~/spreadsheets/d/([^/]+)~', $url, $spreadsheetMatches);

        if (! isset($spreadsheetMatches[1])) {
            return $url;
        }

        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        $gid = $query['gid'] ?? '0';

        if ($gid === '0') {
            $fragment = parse_url($url, PHP_URL_FRAGMENT) ?: '';

            if (preg_match('/gid=(\d+)/', $fragment, $gidMatches) === 1) {
                $gid = $gidMatches[1];
            }
        }

        return "https://docs.google.com/spreadsheets/d/{$spreadsheetMatches[1]}/export?format=csv&gid={$gid}";
    }

    protected function canFallbackToPublicCsv(SheetSource $source, RuntimeException $exception): bool
    {
        if (! str_contains($source->url, 'docs.google.com/spreadsheets/d/')) {
            return false;
        }

        return in_array($exception->getMessage(), [
            'Google OAuth client ID and secret must be saved first.',
            'Google is not connected for this company.',
            'Google access expired and no refresh token is available. Reconnect Google.',
        ], true);
    }

    protected function resolveLeadId(SheetSource $source, ?string $leadReference, ?string $rfid): ?int
    {
        return Lead::query()
            ->where('workspace_id', $source->workspace_id)
            ->where(function ($query) use ($leadReference, $rfid) {
                if ($leadReference) {
                    $query->orWhere('lead_id', $leadReference);
                }

                if ($rfid) {
                    $query->orWhere('rfid', $rfid);
                }
            })
            ->value('id');
    }

    protected function resolveOpportunityId(SheetSource $source, ?string $opportunityReference, ?string $leadReference, ?string $rfid): ?int
    {
        return Opportunity::query()
            ->where('workspace_id', $source->workspace_id)
            ->where(function ($query) use ($opportunityReference, $leadReference, $rfid) {
                if ($opportunityReference) {
                    $query->orWhere('external_key', $opportunityReference);
                }

                if ($leadReference) {
                    $query->orWhere('lead_reference', $leadReference);
                }

                if ($rfid) {
                    $query->orWhere('rfid', $rfid);
                }
            })
            ->value('id');
    }

    protected function resolveQuoteId(SheetSource $source, ?string $quoteNumber): ?int
    {
        if (! $quoteNumber) {
            return null;
        }

        return Quote::query()
            ->where('workspace_id', $source->workspace_id)
            ->where('quote_number', $quoteNumber)
            ->value('id');
    }

    protected function resolveShipmentId(SheetSource $source, ?string $jobNumber): ?int
    {
        if (! $jobNumber) {
            return null;
        }

        return ShipmentJob::query()
            ->where('workspace_id', $source->workspace_id)
            ->where('job_number', $jobNumber)
            ->value('id');
    }

    protected function resolveCarrierId(SheetSource $source, ?string $carrierName): ?int
    {
        if (! $carrierName) {
            return null;
        }

        return Carrier::query()
            ->where('workspace_id', $source->workspace_id)
            ->where('name', $carrierName)
            ->value('id');
    }

    protected function applyBookingShipmentConnection(Booking $booking): void
    {
        if (! $booking->shipment_job_id) {
            return;
        }

        $shipment = ShipmentJob::query()
            ->where('workspace_id', $booking->workspace_id)
            ->find($booking->shipment_job_id);

        if (! $shipment) {
            return;
        }

        $booking->loadMissing('carrier');

        $shipment->forceFill([
            'carrier_name' => $booking->carrier?->name ?: $shipment->carrier_name,
            'estimated_departure_at' => $booking->confirmed_etd ?: $booking->requested_etd ?: $shipment->estimated_departure_at,
            'estimated_arrival_at' => $booking->confirmed_eta ?: $booking->requested_eta ?: $shipment->estimated_arrival_at,
            'status' => match ($booking->status) {
                Booking::STATUS_REQUESTED => ShipmentJob::STATUS_BOOKING_REQUESTED,
                Booking::STATUS_CONFIRMED => ShipmentJob::STATUS_BOOKED,
                Booking::STATUS_ROLLED => ShipmentJob::STATUS_BOOKING_REQUESTED,
                Booking::STATUS_IN_TRANSIT => ShipmentJob::STATUS_IN_TRANSIT,
                Booking::STATUS_COMPLETED => ShipmentJob::STATUS_DELIVERED,
                Booking::STATUS_CANCELLED => ShipmentJob::STATUS_CANCELLED,
                default => $shipment->status ?: ShipmentJob::STATUS_DRAFT,
            },
        ])->save();
    }

    protected function normalizeLeadStatus(?string $status): string
    {
        return in_array($status, Lead::STATUSES, true)
            ? $status
            : Lead::STATUS_IN_PROGRESS;
    }

    protected function normalizeOpportunityStage(?string $stage): string
    {
        return in_array($stage, Opportunity::STAGES, true)
            ? $stage
            : Opportunity::STAGE_INITIAL_CONTACT;
    }

    protected function normalizeQuoteStatus(?string $status): string
    {
        return in_array($status, Quote::STATUSES, true)
            ? $status
            : Quote::STATUS_DRAFT;
    }

    protected function normalizeShipmentStatus(?string $status): string
    {
        return in_array($status, ShipmentJob::STATUSES, true)
            ? $status
            : ShipmentJob::STATUS_DRAFT;
    }

    protected function normalizeCarrierMode(?string $mode): ?string
    {
        return match (Str::lower(trim((string) $mode))) {
            'ocean', 'sea' => Carrier::MODE_OCEAN,
            'air' => Carrier::MODE_AIR,
            'road', 'truck', 'trucking' => Carrier::MODE_ROAD,
            'rail' => Carrier::MODE_RAIL,
            'multimodal', 'multi-modal' => Carrier::MODE_MULTIMODAL,
            default => filled($mode) ? trim((string) $mode) : null,
        };
    }

    protected function normalizeBookingStatus(?string $status): string
    {
        return match (Str::lower(trim((string) $status))) {
            'requested', 'request sent' => Booking::STATUS_REQUESTED,
            'confirmed', 'booked', 'booking confirmed' => Booking::STATUS_CONFIRMED,
            'rolled' => Booking::STATUS_ROLLED,
            'in transit', 'shipped' => Booking::STATUS_IN_TRANSIT,
            'completed', 'delivered' => Booking::STATUS_COMPLETED,
            'cancelled', 'canceled' => Booking::STATUS_CANCELLED,
            default => Booking::STATUS_DRAFT,
        };
    }

    protected function resolveMarginAmount(array $row, array $payload): ?float
    {
        $margin = $this->parseMoney($this->value($row, ['Margin', 'Margin Amount']));

        if ($margin !== null) {
            return $margin;
        }

        $buy = data_get($payload, 'buy_amount');
        $sell = data_get($payload, 'sell_amount');

        if ($buy === null || $sell === null) {
            return null;
        }

        return (float) $sell - (float) $buy;
    }

    protected function normalizeService(?string $service): ?string
    {
        if (! $service) {
            return null;
        }

        return Str::of($service)
            ->lower()
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    protected function normalizeYearMonth(?string $value): ?string
    {
        if (! $value || in_array($value, ['0', '#REF!', 'Submission Date'], true)) {
            return null;
        }

        return $value;
    }

    protected function value(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            foreach ($row as $rowKey => $value) {
                if (mb_strtolower(trim((string) $rowKey)) === mb_strtolower(trim($key))) {
                    return trim((string) $value) !== '' ? trim((string) $value) : null;
                }
            }
        }

        return null;
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function parseMonth(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('M-y', $value)->startOfMonth();
        } catch (Throwable) {
            return null;
        }
    }

    protected function parseMoney(?string $value): ?float
    {
        if (! $value) {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\\-]/', '', str_ireplace('dh', '', $value));

        return $normalized === '' ? null : (float) $normalized;
    }

    protected function parseInteger(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/-?\\d+/', $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }

    protected function parseBoolean(?string $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes'], true);
    }
}
