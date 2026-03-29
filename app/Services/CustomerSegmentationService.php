<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMetricSnapshot;
use App\Models\AccountSegmentAssignment;
use App\Models\Booking;
use App\Models\CustomerSegmentDefinition;
use App\Models\CustomerSegmentRule;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\ShipmentJob;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CustomerSegmentationService
{
    public const SNAPSHOT_KEY_CURRENT = 'current';

    public static function presetDefinitionsForTemplate(string $templateKey): array
    {
        if ($templateKey !== 'freight_forwarding') {
            return [
                [
                    'name' => 'Active Customer',
                    'slug' => 'active-customer',
                    'description' => 'Recent account activity shows this customer is still engaged.',
                    'color' => CustomerSegmentDefinition::COLOR_EMERALD,
                    'priority' => 90,
                    'rules' => [
                        ['metric_key' => 'inquiries_90d', 'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN_OR_EQUAL, 'threshold_value' => 1],
                    ],
                ],
                [
                    'name' => 'At Risk',
                    'slug' => 'at-risk',
                    'description' => 'Activity has slowed and the account may need re-engagement.',
                    'color' => CustomerSegmentDefinition::COLOR_AMBER,
                    'priority' => 80,
                    'rules' => [
                        ['metric_key' => 'days_since_last_inquiry', 'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN, 'threshold_value' => 90],
                    ],
                ],
            ];
        }

        return [
            [
                'name' => 'Benchmark Account',
                'slug' => 'benchmark-account',
                'description' => 'High-value, repeat forwarding account with consistent operational volume.',
                'color' => CustomerSegmentDefinition::COLOR_VIOLET,
                'priority' => 100,
                'rules' => [
                    ['metric_key' => 'shipments_90d', 'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN_OR_EQUAL, 'threshold_value' => 3],
                    ['metric_key' => 'revenue_365d', 'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN_OR_EQUAL, 'threshold_value' => 100000],
                ],
            ],
            [
                'name' => 'Active Customer',
                'slug' => 'active-customer',
                'description' => 'Recent inquiries or shipments show this forwarding customer is currently engaged.',
                'color' => CustomerSegmentDefinition::COLOR_EMERALD,
                'priority' => 90,
                'rules' => [
                    ['metric_key' => 'inquiries_90d', 'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN_OR_EQUAL, 'threshold_value' => 1],
                ],
            ],
            [
                'name' => 'Churn Risk',
                'slug' => 'churn-risk',
                'description' => 'The customer used to move shipments but recent activity has dropped.',
                'color' => CustomerSegmentDefinition::COLOR_AMBER,
                'priority' => 80,
                'rules' => [
                    ['metric_key' => 'lifetime_shipments', 'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN_OR_EQUAL, 'threshold_value' => 2],
                    ['metric_key' => 'days_since_last_shipment', 'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN, 'threshold_value' => 60],
                    ['metric_key' => 'shipments_90d', 'operator' => CustomerSegmentRule::OPERATOR_LESS_THAN, 'threshold_value' => 2],
                ],
            ],
            [
                'name' => 'Early Churned',
                'slug' => 'early-churned',
                'description' => 'The customer converted early but did not build repeat forwarding volume.',
                'color' => CustomerSegmentDefinition::COLOR_ROSE,
                'priority' => 70,
                'rules' => [
                    ['metric_key' => 'lifetime_shipments', 'operator' => CustomerSegmentRule::OPERATOR_LESS_THAN_OR_EQUAL, 'threshold_value' => 2],
                    ['metric_key' => 'days_since_last_shipment', 'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN, 'threshold_value' => 120],
                ],
            ],
            [
                'name' => 'Late Churned',
                'slug' => 'late-churned',
                'description' => 'The account was historically active but has gone quiet for an extended period.',
                'color' => CustomerSegmentDefinition::COLOR_ROSE,
                'priority' => 60,
                'rules' => [
                    ['metric_key' => 'lifetime_shipments', 'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN_OR_EQUAL, 'threshold_value' => 6],
                    ['metric_key' => 'days_since_last_shipment', 'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN, 'threshold_value' => 180],
                ],
            ],
            [
                'name' => 'Low Conversion',
                'slug' => 'low-conversion',
                'description' => 'The customer asks for rates and inquiries, but shipments are not converting.',
                'color' => CustomerSegmentDefinition::COLOR_SKY,
                'priority' => 50,
                'rules' => [
                    ['metric_key' => 'inquiries_90d', 'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN_OR_EQUAL, 'threshold_value' => 3],
                    ['metric_key' => 'shipments_90d', 'operator' => CustomerSegmentRule::OPERATOR_EQUAL, 'threshold_value' => 0],
                ],
            ],
        ];
    }

    public static function metricCatalog(): array
    {
        return [
            'inquiries_30d' => 'Inquiries in last 30 days',
            'inquiries_90d' => 'Inquiries in last 90 days',
            'quotes_30d' => 'Quotes in last 30 days',
            'quotes_90d' => 'Quotes in last 90 days',
            'shipments_30d' => 'Shipments in last 30 days',
            'shipments_90d' => 'Shipments in last 90 days',
            'shipments_prev_90d' => 'Shipments in previous 90 days',
            'bookings_90d' => 'Bookings in last 90 days',
            'won_opportunities_180d' => 'Won opportunities in last 180 days',
            'revenue_365d' => 'Revenue in last 365 days',
            'lifetime_inquiries' => 'Lifetime inquiries',
            'lifetime_shipments' => 'Lifetime shipments',
            'days_since_last_inquiry' => 'Days since last inquiry',
            'days_since_last_quote' => 'Days since last quote',
            'days_since_last_shipment' => 'Days since last shipment',
            'days_since_last_booking' => 'Days since last booking',
        ];
    }

    public static function operatorCatalog(): array
    {
        return [
            CustomerSegmentRule::OPERATOR_GREATER_THAN => '>',
            CustomerSegmentRule::OPERATOR_GREATER_THAN_OR_EQUAL => '>=',
            CustomerSegmentRule::OPERATOR_LESS_THAN => '<',
            CustomerSegmentRule::OPERATOR_LESS_THAN_OR_EQUAL => '<=',
            CustomerSegmentRule::OPERATOR_EQUAL => '=',
        ];
    }

    public function ensureDefaultSegments(Workspace $workspace): void
    {
        if ($workspace->segmentDefinitions()->exists()) {
            return;
        }

        $definitions = $this->presetDefinitions($workspace);

        foreach ($definitions as $definition) {
            $segment = CustomerSegmentDefinition::query()->create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'description' => $definition['description'],
                'color' => $definition['color'],
                'priority' => $definition['priority'],
                'is_active' => true,
            ]);

            foreach ($definition['rules'] as $index => $rule) {
                $segment->rules()->create([
                    'metric_key' => $rule['metric_key'],
                    'operator' => $rule['operator'],
                    'threshold_value' => $rule['threshold_value'],
                    'sort_order' => $index,
                ]);
            }
        }
    }

    public function syncWorkspace(Workspace $workspace): void
    {
        $this->ensureDefaultSegments($workspace);

        $accounts = Account::query()
            ->where('workspace_id', $workspace->id)
            ->with(['leads', 'opportunities', 'quotes', 'shipmentJobs', 'bookings'])
            ->get();

        foreach ($accounts as $account) {
            $this->syncAccount($account, $workspace);
        }
    }

    public function syncAccount(Account $account, ?Workspace $workspace = null): AccountMetricSnapshot
    {
        $workspace ??= $account->workspace;
        $now = now();
        $snapshot = $this->buildSnapshotPayload($account, $workspace, $now);

        $metricSnapshot = AccountMetricSnapshot::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'account_id' => $account->id,
                'snapshot_key' => self::SNAPSHOT_KEY_CURRENT,
            ],
            [
                'company_id' => $workspace->company_id,
                ...$snapshot,
            ],
        );

        $this->syncAssignments($account, $workspace, $metricSnapshot);

        return $metricSnapshot;
    }

    protected function syncAssignments(Account $account, Workspace $workspace, AccountMetricSnapshot $snapshot): void
    {
        $segments = CustomerSegmentDefinition::query()
            ->with('rules')
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

        $matchedSegmentIds = $segments
            ->filter(fn (CustomerSegmentDefinition $segment) => $this->segmentMatches($segment, $snapshot))
            ->pluck('id')
            ->all();

        AccountSegmentAssignment::query()
            ->where('workspace_id', $workspace->id)
            ->where('account_id', $account->id)
            ->whereNotIn('segment_definition_id', $matchedSegmentIds ?: [0])
            ->delete();

        foreach ($matchedSegmentIds as $segmentId) {
            AccountSegmentAssignment::query()->updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'account_id' => $account->id,
                    'segment_definition_id' => $segmentId,
                ],
                [
                    'company_id' => $workspace->company_id,
                    'account_metric_snapshot_id' => $snapshot->id,
                    'assigned_at' => now(),
                ],
            );
        }
    }

    protected function segmentMatches(CustomerSegmentDefinition $segment, AccountMetricSnapshot $snapshot): bool
    {
        $rules = $segment->rules;

        if ($rules->isEmpty()) {
            return false;
        }

        foreach ($rules as $rule) {
            $metricValue = (float) data_get($snapshot, $rule->metric_key, 0);
            $threshold = (float) $rule->threshold_value;

            $matches = match ($rule->operator) {
                CustomerSegmentRule::OPERATOR_GREATER_THAN => $metricValue > $threshold,
                CustomerSegmentRule::OPERATOR_GREATER_THAN_OR_EQUAL => $metricValue >= $threshold,
                CustomerSegmentRule::OPERATOR_LESS_THAN => $metricValue < $threshold,
                CustomerSegmentRule::OPERATOR_LESS_THAN_OR_EQUAL => $metricValue <= $threshold,
                CustomerSegmentRule::OPERATOR_EQUAL => $metricValue === $threshold,
                default => false,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    protected function buildSnapshotPayload(Account $account, Workspace $workspace, Carbon $now): array
    {
        $leads = Lead::query()->where('workspace_id', $workspace->id)->where('account_id', $account->id);
        $opportunities = Opportunity::query()->where('workspace_id', $workspace->id)->where('account_id', $account->id);
        $quotes = Quote::query()->where('workspace_id', $workspace->id)->where('account_id', $account->id);
        $shipments = ShipmentJob::query()->where('workspace_id', $workspace->id)->where('account_id', $account->id);
        $bookings = Booking::query()->where('workspace_id', $workspace->id)->where('account_id', $account->id);

        $lastInquiryAt = $this->maxDate([
            (clone $leads)->max('submission_date'),
            (clone $opportunities)->max('submission_date'),
        ]);
        $lastQuoteAt = $this->maxDate([(clone $quotes)->max('quoted_at')]);
        $lastShipmentAt = $this->maxDate([
            (clone $shipments)->max('actual_departure_at'),
            (clone $shipments)->max('estimated_departure_at'),
            (clone $shipments)->max('created_at'),
        ]);
        $lastBookingAt = $this->maxDate([
            (clone $bookings)->max('confirmed_etd'),
            (clone $bookings)->max('requested_etd'),
            (clone $bookings)->max('created_at'),
        ]);

        $inquiries30 = $this->countInquiriesWithinDays($account, $workspace, 30);
        $inquiries90 = $this->countInquiriesWithinDays($account, $workspace, 90);

        return [
            'inquiries_30d' => $inquiries30,
            'inquiries_90d' => $inquiries90,
            'quotes_30d' => $this->countWithinDays($quotes, 'quoted_at', 30),
            'quotes_90d' => $this->countWithinDays($quotes, 'quoted_at', 90),
            'shipments_30d' => $this->countShipmentWithinDays($shipments, 30),
            'shipments_90d' => $this->countShipmentWithinDays($shipments, 90),
            'shipments_prev_90d' => $this->countShipmentBetweenDays($shipments, 91, 180),
            'bookings_90d' => $this->countBookingWithinDays($bookings, 90),
            'won_opportunities_180d' => (clone $opportunities)
                ->where('sales_stage', Opportunity::STAGE_CLOSED_WON)
                ->where('submission_date', '>=', $now->copy()->subDays(180))
                ->count(),
            'revenue_365d' => (clone $opportunities)
                ->where('submission_date', '>=', $now->copy()->subDays(365))
                ->sum('revenue_potential'),
            'lifetime_inquiries' => (clone $leads)->count() + (clone $opportunities)->count(),
            'lifetime_shipments' => (clone $shipments)->count(),
            'days_since_last_inquiry' => $this->daysSince($lastInquiryAt, $now),
            'days_since_last_quote' => $this->daysSince($lastQuoteAt, $now),
            'days_since_last_shipment' => $this->daysSince($lastShipmentAt, $now),
            'days_since_last_booking' => $this->daysSince($lastBookingAt, $now),
            'last_inquiry_at' => $lastInquiryAt,
            'last_quote_at' => $lastQuoteAt,
            'last_shipment_at' => $lastShipmentAt,
            'last_booking_at' => $lastBookingAt,
            'evaluated_at' => $now,
        ];
    }

    protected function countInquiriesWithinDays(Account $account, Workspace $workspace, int $days): int
    {
        $from = now()->subDays($days);

        return Lead::query()
            ->where('workspace_id', $workspace->id)
            ->where('account_id', $account->id)
            ->where('submission_date', '>=', $from)
            ->count()
            + Opportunity::query()
                ->where('workspace_id', $workspace->id)
                ->where('account_id', $account->id)
                ->where('submission_date', '>=', $from)
                ->count();
    }

    protected function countWithinDays($query, string $column, int $days): int
    {
        return (clone $query)
            ->where($column, '>=', now()->subDays($days))
            ->count();
    }

    protected function countShipmentWithinDays($query, int $days): int
    {
        return (clone $query)
            ->where(function ($builder) use ($days) {
                $from = now()->subDays($days);

                $builder->where('actual_departure_at', '>=', $from)
                    ->orWhere('estimated_departure_at', '>=', $from)
                    ->orWhere('created_at', '>=', $from);
            })
            ->count();
    }

    protected function countShipmentBetweenDays($query, int $startDays, int $endDays): int
    {
        $from = now()->subDays($endDays);
        $to = now()->subDays($startDays);

        return (clone $query)
            ->where(function ($builder) use ($from, $to) {
                $builder->whereBetween('actual_departure_at', [$from, $to])
                    ->orWhereBetween('estimated_departure_at', [$from, $to])
                    ->orWhereBetween('created_at', [$from, $to]);
            })
            ->count();
    }

    protected function countBookingWithinDays($query, int $days): int
    {
        return (clone $query)
            ->where(function ($builder) use ($days) {
                $from = now()->subDays($days);

                $builder->where('confirmed_etd', '>=', $from)
                    ->orWhere('requested_etd', '>=', $from)
                    ->orWhere('created_at', '>=', $from);
            })
            ->count();
    }

    protected function maxDate(array $dates): ?Carbon
    {
        $values = collect($dates)
            ->filter()
            ->map(fn ($value) => Carbon::parse($value));

        return $values->isEmpty() ? null : $values->max();
    }

    protected function daysSince(?Carbon $date, Carbon $now): ?int
    {
        return $date ? $date->diffInDays($now) : null;
    }

    protected function presetDefinitions(Workspace $workspace): array
    {
        return static::presetDefinitionsForTemplate($workspace->templateKey());
    }

    public function segmentSummaryFor(Account $account): array
    {
        $snapshot = $account->currentMetricSnapshot;
        $segments = $account->segmentAssignments
            ->map(fn (AccountSegmentAssignment $assignment) => $assignment->segmentDefinition)
            ->filter()
            ->sortByDesc('priority')
            ->values();

        return [
            'segments' => $segments,
            'headline' => $segments->first()?->name ?: 'Unsegmented',
            'snapshot' => $snapshot,
        ];
    }
}
