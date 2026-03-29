<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\Workspace;

class WorkspaceBillingService
{
    public const BILLING_KEY = 'billing';

    public function planCatalog(): array
    {
        return config('pricing.plans', []);
    }

    public function usageMetricCatalog(): array
    {
        return config('pricing.usage_metrics', []);
    }

    public function defaultPlanKey(): string
    {
        return (string) config('pricing.default_plan', 'free');
    }

    public function resolvePlanKey(Workspace $workspace): string
    {
        $key = data_get($workspace->settings, self::BILLING_KEY.'.plan_key')
            ?: data_get($workspace->company?->settings, self::BILLING_KEY.'.plan_key')
            ?: data_get($workspace->settings, 'subscription_plan')
            ?: data_get($workspace->company?->settings, 'subscription_plan')
            ?: $this->defaultPlanKey();

        return array_key_exists($key, $this->planCatalog()) ? $key : $this->defaultPlanKey();
    }

    public function planDefinition(Workspace|string|null $workspaceOrPlan): array
    {
        $planKey = $workspaceOrPlan instanceof Workspace
            ? $this->resolvePlanKey($workspaceOrPlan)
            : (is_string($workspaceOrPlan) ? $workspaceOrPlan : $this->defaultPlanKey());

        return $this->planCatalog()[$planKey] ?? $this->planCatalog()[$this->defaultPlanKey()] ?? [];
    }

    public function usageMetricDefinition(Workspace $workspace): array
    {
        $templateKey = $workspace->templateKey();

        return $this->usageMetricCatalog()[$templateKey]
            ?? [
                'key' => 'won_opportunities',
                'label' => 'Operational records',
                'description' => 'Commercial records used as the operational benchmark.',
            ];
    }

    public function operationalUsageCount(Workspace $workspace): int
    {
        return match ($this->usageMetricDefinition($workspace)['key']) {
            'shipment_jobs' => $workspace->shipmentJobs()->count(),
            'projects' => $workspace->projects()->count(),
            'bookings' => $workspace->bookings()->count(),
            'quotes' => $workspace->quotes()->count(),
            'won_opportunities' => $workspace->opportunities()->where('sales_stage', Opportunity::STAGE_CLOSED_WON)->count(),
            default => $workspace->opportunities()->where('sales_stage', Opportunity::STAGE_CLOSED_WON)->count(),
        };
    }

    public function includedUsers(Workspace $workspace): ?int
    {
        $custom = data_get($workspace->settings, self::BILLING_KEY.'.included_users');

        if ($custom !== null && $custom !== '') {
            return (int) $custom;
        }

        return $this->planDefinition($workspace)['included_users'] ?? null;
    }

    public function includedOperationalRecords(Workspace $workspace): ?int
    {
        $custom = data_get($workspace->settings, self::BILLING_KEY.'.included_operational_records');

        if ($custom !== null && $custom !== '') {
            return (int) $custom;
        }

        return $this->planDefinition($workspace)['included_operational_records'] ?? null;
    }

    public function summary(Workspace $workspace): array
    {
        $planKey = $this->resolvePlanKey($workspace);
        $plan = $this->planDefinition($planKey);
        $metric = $this->usageMetricDefinition($workspace);
        $currentUsers = $workspace->users()->count();
        $currentOperationalRecords = $this->operationalUsageCount($workspace);
        $includedUsers = $this->includedUsers($workspace);
        $includedOperationalRecords = $this->includedOperationalRecords($workspace);

        return [
            'plan_key' => $planKey,
            'plan_name' => $plan['name'] ?? ucfirst($planKey),
            'price_label' => $plan['price_label'] ?? 'Custom',
            'workspace_price_monthly' => $plan['workspace_price_monthly'] ?? null,
            'included_users' => $includedUsers,
            'included_operational_records' => $includedOperationalRecords,
            'current_users' => $currentUsers,
            'current_operational_records' => $currentOperationalRecords,
            'usage_metric_key' => $metric['key'],
            'usage_metric_label' => $metric['label'],
            'usage_metric_description' => $metric['description'],
            'users_over_limit' => $includedUsers !== null && $currentUsers > $includedUsers,
            'operational_over_limit' => $includedOperationalRecords !== null && $currentOperationalRecords > $includedOperationalRecords,
            'highlights' => $plan['highlights'] ?? [],
            'feature_flags' => $plan['feature_flags'] ?? [],
        ];
    }

    public function setWorkspacePlan(Workspace $workspace, string $planKey, ?int $includedUsers = null, ?int $includedOperationalRecords = null): void
    {
        $settings = $workspace->settings ?? [];
        data_set($settings, self::BILLING_KEY.'.plan_key', $planKey);
        data_set($settings, self::BILLING_KEY.'.included_users', $includedUsers);
        data_set($settings, self::BILLING_KEY.'.included_operational_records', $includedOperationalRecords);
        data_set($settings, 'subscription_plan', $planKey);

        $workspace->forceFill([
            'settings' => $settings,
        ])->save();
    }
}
