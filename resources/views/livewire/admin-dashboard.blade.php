<div class="space-y-6">
    <section class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-zinc-500">Admin</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Platform control center</h1>
                <p class="mt-2 max-w-2xl text-sm text-zinc-500">
                    Control workspaces, users, sources, billing plans, and platform-wide governance from one admin center.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <select wire:model.live="workspaceId" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none">
                    @forelse ($workspaces as $workspace)
                        <option value="{{ $workspace->id }}">{{ $workspace->company->name }} / {{ $workspace->name }}</option>
                    @empty
                        <option value="">No workspace found</option>
                    @endforelse
                </select>

                @if ($currentWorkspace)
                    <button wire:click="syncWorkspaceSources" type="button" class="rounded-2xl bg-sky-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-sky-800">
                        Sync Workspace
                    </button>
                @endif
            </div>
        </div>
    </section>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <section class="overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-zinc-50 px-4">
            <div class="flex flex-wrap gap-1 py-2">
                @foreach ([
                    'analytics' => 'Overview',
                    'billing' => 'Billing',
                    'sources' => 'Data Sources',
                    'access' => 'Users & Roles',
                    'structure' => 'Companies & Workspaces',
                ] as $tabKey => $label)
                    <button
                        wire:click="$set('activeTab', '{{ $tabKey }}')"
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $activeTab === $tabKey ? 'bg-white text-zinc-950 shadow-sm' : 'text-zinc-500 hover:text-zinc-900' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        @if ($activeTab === 'analytics')
            <div class="space-y-6 p-4">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($overviewStats as $stat)
                        <article class="rounded-[1.5rem] border border-zinc-200 bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $stat['label'] }}</p>
                            <p class="mt-3 text-2xl font-semibold text-zinc-950">{{ $stat['value'] }}</p>
                            <p class="mt-2 text-sm text-zinc-500">{{ $stat['detail'] }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
                    <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-zinc-950">Growth over time</h2>
                                <p class="mt-1 text-sm text-zinc-500">Track new users, workspaces, and companies created over the last six months.</p>
                            </div>
                            <div class="flex flex-wrap gap-3 text-xs font-medium text-zinc-500">
                                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>Users</span>
                                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>Workspaces</span>
                                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Companies</span>
                            </div>
                        </div>

                        <div class="mt-6 overflow-x-auto">
                            <div class="flex min-w-[640px] items-end gap-4">
                                @foreach ($growthSeries as $point)
                                    @php
                                        $usersHeight = $point['users'] > 0 ? max(18, (int) round(($point['users'] / $growthMax) * 180)) : 8;
                                        $workspacesHeight = $point['workspaces'] > 0 ? max(18, (int) round(($point['workspaces'] / $growthMax) * 180)) : 8;
                                        $companiesHeight = $point['companies'] > 0 ? max(18, (int) round(($point['companies'] / $growthMax) * 180)) : 8;
                                    @endphp
                                    <div class="flex min-w-[92px] flex-1 flex-col items-center gap-3">
                                        <div class="flex h-52 items-end gap-2">
                                            <div class="w-4 rounded-t-full bg-sky-500" style="height: {{ $usersHeight }}px" title="Users: {{ $point['users'] }}"></div>
                                            <div class="w-4 rounded-t-full bg-indigo-500" style="height: {{ $workspacesHeight }}px" title="Workspaces: {{ $point['workspaces'] }}"></div>
                                            <div class="w-4 rounded-t-full bg-emerald-500" style="height: {{ $companiesHeight }}px" title="Companies: {{ $point['companies'] }}"></div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-sm font-semibold text-zinc-900">{{ $point['label'] }}</div>
                                            <div class="mt-1 text-xs text-zinc-500">
                                                {{ $point['users'] }} / {{ $point['workspaces'] }} / {{ $point['companies'] }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </article>

                    <div class="space-y-4">
                        <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                            <h2 class="text-lg font-semibold text-zinc-950">Subscription mix</h2>
                            <p class="mt-1 text-sm text-zinc-500">Monitor how the customer base is distributed across plans.</p>
                            <div class="mt-4 space-y-4">
                                @foreach ($subscriptionRows as $subscription)
                                    <div>
                                        <div class="flex items-center justify-between gap-4 text-sm">
                                            <div>
                                                <div class="font-medium text-zinc-800">{{ $subscription['name'] }}</div>
                                                <div class="text-xs text-zinc-500">{{ $subscription['price_label'] }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-medium text-zinc-800">{{ $subscription['count'] }} workspaces</div>
                                                <div class="text-xs text-zinc-500">
                                                    {{ $subscription['share'] }}%
                                                    @if ($subscription['estimated_mrr'] !== null)
                                                        · ${{ number_format($subscription['estimated_mrr'], 0) }}/mo
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2 h-2 rounded-full bg-zinc-100">
                                            <div class="h-2 rounded-full bg-sky-500" style="width: {{ max(6, $subscription['share']) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </article>

                        <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                            <h2 class="text-lg font-semibold text-zinc-950">Platform signals</h2>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($overviewSignals as $signal)
                                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $signal['label'] }}</div>
                                        <div class="mt-2 text-xl font-semibold text-zinc-950">{{ $signal['value'] }}</div>
                                        <div class="mt-1 text-sm text-zinc-500">{{ $signal['detail'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-[1fr_1fr_1fr]">
                    <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                        <h2 class="text-lg font-semibold text-zinc-950">Attention needed</h2>
                        <p class="mt-1 text-sm text-zinc-500">These workspaces need review because they have no users, no sources, or are over plan limits.</p>
                        <div class="mt-4 space-y-3">
                            @forelse ($attentionRows as $item)
                                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="font-medium text-zinc-900">{{ $item['workspace_name'] }}</div>
                                        <div class="text-xs text-zinc-500">{{ $item['company_name'] }}</div>
                                    </div>
                                    <div class="mt-2 text-sm leading-6 text-zinc-600">{{ implode(' · ', $item['reasons']) }}</div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-zinc-200 px-4 py-8 text-sm text-zinc-500">
                                    No urgent platform issues detected right now.
                                </div>
                            @endforelse
                        </div>
                    </article>

                    <article class="rounded-[1.5rem] border border-zinc-200 p-4 xl:col-span-2">
                        <h2 class="text-lg font-semibold text-zinc-950">Workspace mode distribution</h2>
                        <p class="mt-1 text-sm text-zinc-500">Check how customers are spread across the workspace templates and use cases you support.</p>
                        <div class="mt-4 grid gap-3 lg:grid-cols-2">
                            @foreach ($workspaceModeRows as $mode)
                                <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="font-medium text-zinc-900">{{ $mode['name'] }}</div>
                                            <div class="mt-1 text-sm leading-6 text-zinc-500">{{ $mode['description'] }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-semibold text-zinc-950">{{ $mode['count'] }}</div>
                                            <div class="text-xs text-zinc-500">{{ $mode['share'] }}%</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 h-2 rounded-full bg-zinc-100">
                                        <div class="h-2 rounded-full bg-indigo-500" style="width: {{ max(6, $mode['share']) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                </div>
            </div>
        @endif

        @if ($activeTab === 'billing')
            <div class="space-y-6 p-4">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($billingOverviewStats as $stat)
                        <article class="rounded-[1.5rem] border border-zinc-200 bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $stat['label'] }}</p>
                            <p class="mt-3 text-2xl font-semibold text-zinc-950">{{ $stat['value'] }}</p>
                            <p class="mt-2 text-sm text-zinc-500">{{ $stat['detail'] }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <div class="space-y-4">
                        <article class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-4">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-zinc-950">Billing directory</h2>
                                    <p class="mt-1 text-sm text-zinc-500">Search by workspace, company, plan, workspace mode, or usage metric. Prioritize over-limit accounts first.</p>
                                </div>
                                <div class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600">
                                    {{ $billingDirectoryRows->total() }} workspace{{ $billingDirectoryRows->total() === 1 ? '' : 's' }} found
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1.2fr)_180px_180px]">
                                <input wire:model.live.debounce.300ms="billingSearch" type="text" placeholder="Find by workspace, company, plan, or mode" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                                <select wire:model.live="billingPlanFilter" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none">
                                    <option value="all">All plans</option>
                                    @foreach ($billingPlans as $planKey => $plan)
                                        <option value="{{ $planKey }}">{{ $plan['name'] }}</option>
                                    @endforeach
                                </select>
                                <select wire:model.live="billingStatusFilter" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none">
                                    <option value="all">All billing states</option>
                                    <option value="over_limit">Over limit</option>
                                    <option value="paid">Paid plans</option>
                                    <option value="free">Free plan</option>
                                    <option value="custom">Custom pricing</option>
                                </select>
                            </div>
                        </article>

                        @foreach ($billingDirectoryRows as $billingRow)
                            @php
                                $workspaceRow = $billingRow['workspace'];
                                $summary = $billingRow['summary'];
                                $needsAttention = $summary['users_over_limit'] || $summary['operational_over_limit'];
                            @endphp
                            <article class="rounded-[1.5rem] border {{ $needsAttention ? 'border-amber-200 bg-amber-50/30' : 'border-zinc-200 bg-white' }} p-4 shadow-sm">
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div class="space-y-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="text-lg font-semibold text-zinc-950">{{ $workspaceRow->name }}</div>
                                            <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-[11px] font-medium text-zinc-600">{{ $workspaceRow->company->name }}</span>
                                            <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-medium text-sky-700">{{ $summary['plan_name'] }}</span>
                                            @if ($needsAttention)
                                                <span class="rounded-full border border-amber-200 bg-amber-100 px-3 py-1 text-[11px] font-medium text-amber-800">Needs review</span>
                                            @endif
                                        </div>

                                        <div class="grid gap-3 lg:grid-cols-3">
                                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                                <div class="text-[11px] uppercase tracking-[0.2em] text-zinc-400">Users</div>
                                                <div class="mt-2 text-sm font-semibold text-zinc-950">
                                                    {{ $summary['current_users'] }}
                                                    @if ($summary['included_users'])
                                                        <span class="font-medium text-zinc-500">/ {{ $summary['included_users'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                                <div class="text-[11px] uppercase tracking-[0.2em] text-zinc-400">{{ $summary['usage_metric_label'] }}</div>
                                                <div class="mt-2 text-sm font-semibold text-zinc-950">
                                                    {{ $summary['current_operational_records'] }}
                                                    @if ($summary['included_operational_records'])
                                                        <span class="font-medium text-zinc-500">/ {{ $summary['included_operational_records'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                                <div class="text-[11px] uppercase tracking-[0.2em] text-zinc-400">Workspace Mode</div>
                                                <div class="mt-2 text-sm font-semibold text-zinc-950">{{ $workspaceRow->templateName() }}</div>
                                            </div>
                                        </div>

                                        <div class="text-sm text-zinc-600">
                                            {{ $summary['price_label'] }} · {{ $summary['usage_metric_label'] }}
                                        </div>

                                        @if ($needsAttention)
                                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                                {{ $summary['users_over_limit'] ? 'User seats are over the included limit.' : '' }}
                                                {{ $summary['users_over_limit'] && $summary['operational_over_limit'] ? ' ' : '' }}
                                                {{ $summary['operational_over_limit'] ? $summary['usage_metric_label'].' is over the included limit.' : '' }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap gap-2 xl:max-w-[220px] xl:justify-end">
                                        <button wire:click="$set('workspaceId', {{ $workspaceRow->id }})" type="button" class="rounded-xl border border-zinc-200 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                            Select workspace
                                        </button>
                                        <button wire:click="startEditingWorkspace({{ $workspaceRow->id }})" type="button" class="rounded-xl border border-zinc-200 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                            Load workspace details
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @endforeach

                        <div>
                            {{ $billingDirectoryRows->links() }}
                        </div>
                    </div>

                    <div class="space-y-6">
                        <article class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-4">
                            <h2 class="text-lg font-semibold text-zinc-950">Manage selected workspace</h2>
                            <p class="mt-1 text-sm text-zinc-500">
                                Assign the plan, set custom included seats or operational volume, and review usage for the currently selected workspace.
                            </p>

                            @if ($currentWorkspace && $currentBillingSummary)
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3">
                                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Current Plan</div>
                                        <div class="mt-2 text-xl font-semibold text-zinc-950">{{ $currentBillingSummary['plan_name'] }}</div>
                                        <div class="mt-1 text-sm text-zinc-500">{{ $currentBillingSummary['price_label'] }}</div>
                                    </div>
                                    <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3">
                                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Usage Metric</div>
                                        <div class="mt-2 text-xl font-semibold text-zinc-950">{{ $currentBillingSummary['usage_metric_label'] }}</div>
                                        <div class="mt-1 text-sm text-zinc-500">{{ $currentBillingSummary['usage_metric_description'] }}</div>
                                    </div>
                                </div>

                                <form wire:submit="saveWorkspaceBilling" class="mt-4 grid gap-3">
                                    <select wire:model="billingForm.plan_key" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none">
                                        @foreach ($billingPlans as $planKey => $plan)
                                            <option value="{{ $planKey }}">{{ $plan['name'] }} · {{ $plan['price_label'] }}</option>
                                        @endforeach
                                    </select>
                                    <input wire:model="billingForm.included_users" type="number" min="1" placeholder="Custom included users (optional)" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                                    <input wire:model="billingForm.included_operational_records" type="number" min="1" placeholder="Custom included operational records (optional)" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                                    <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                        Save Billing Plan
                                    </button>
                                </form>
                            @endif
                        </article>

                        @if ($currentBillingSummary)
                            <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                                <h2 class="text-lg font-semibold text-zinc-950">Current usage</h2>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-xl bg-zinc-50 px-4 py-3">
                                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Users</div>
                                        <div class="mt-2 text-2xl font-semibold text-zinc-950">
                                            {{ $currentBillingSummary['current_users'] }}
                                            @if ($currentBillingSummary['included_users'])
                                                <span class="text-sm font-medium text-zinc-500">/ {{ $currentBillingSummary['included_users'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="rounded-xl bg-zinc-50 px-4 py-3">
                                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $currentBillingSummary['usage_metric_label'] }}</div>
                                        <div class="mt-2 text-2xl font-semibold text-zinc-950">
                                            {{ $currentBillingSummary['current_operational_records'] }}
                                            @if ($currentBillingSummary['included_operational_records'])
                                                <span class="text-sm font-medium text-zinc-500">/ {{ $currentBillingSummary['included_operational_records'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-2">
                                    @foreach ($currentBillingSummary['highlights'] as $highlight)
                                        <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-600">{{ $highlight }}</div>
                                    @endforeach
                                </div>
                            </article>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($activeTab === 'sources')
            <div class="space-y-6 p-4">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($sourceOverviewStats as $stat)
                        <article class="rounded-[1.5rem] border border-zinc-200 bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $stat['label'] }}</p>
                            <p class="mt-3 text-2xl font-semibold text-zinc-950">{{ $stat['value'] }}</p>
                            <p class="mt-2 text-sm text-zinc-500">{{ $stat['detail'] }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="space-y-4">
                    <article class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50/70 p-4 sm:p-5">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-zinc-950">All data sources</h2>
                                <p class="mt-1 text-sm text-zinc-500">Search by source, company, workspace, connector type, status, or sync issue.</p>
                            </div>
                            <div class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-semibold text-zinc-600 shadow-sm">
                                {{ $sheetSources->total() }} source{{ $sheetSources->total() === 1 ? '' : 's' }} found
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-center">
                                <label class="block">
                                    <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Search</span>
                                    <input
                                        wire:model.live.debounce.300ms="sourceSearch"
                                        type="text"
                                        placeholder="Find by source, company, workspace, connector, or issue"
                                        class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3.5 text-sm text-zinc-700 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-sky-300 focus:ring-2 focus:ring-sky-100"
                                    />
                                </label>
                                <div class="hidden xl:flex xl:justify-end">
                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-500 shadow-sm">
                                        <span class="font-medium text-zinc-900">{{ $sheetSources->total() }}</span>
                                        matching records
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-4">
                                <label class="block rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                                    <span class="block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Status</span>
                                    <select wire:model.live="sourceStatusFilter" class="mt-2 w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-700 outline-none focus:ring-0">
                                        <option value="all">All statuses</option>
                                        <option value="attention">Needs attention</option>
                                        <option value="failed">Failed sync</option>
                                        <option value="synced">Synced</option>
                                        <option value="never_synced">Never synced</option>
                                        <option value="active">Active only</option>
                                        <option value="paused">Paused only</option>
                                    </select>
                                </label>

                                <label class="block rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                                    <span class="block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Connector</span>
                                    <select wire:model.live="sourceKindFilter" class="mt-2 w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-700 outline-none focus:ring-0">
                                        <option value="all">All connector types</option>
                                        @foreach (\App\Models\SheetSource::SOURCE_KINDS as $sourceKind)
                                            <option value="{{ $sourceKind }}">{{ \App\Models\SheetSource::sourceKindLabel($sourceKind) }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                                    <span class="block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Sort</span>
                                    <select wire:model.live="sourceSort" class="mt-2 w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-700 outline-none focus:ring-0">
                                        <option value="newest">Newest date</option>
                                        <option value="oldest">Oldest date</option>
                                        <option value="name_asc">Name A-Z</option>
                                        <option value="name_desc">Name Z-A</option>
                                        <option value="company_asc">Company A-Z</option>
                                        <option value="status_desc">Needs attention first</option>
                                        <option value="synced_desc">Last synced</option>
                                    </select>
                                </label>

                                <label class="block rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                                    <span class="block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Density</span>
                                    <select wire:model.live="sourcePerPage" class="mt-2 w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-700 outline-none focus:ring-0">
                                        <option value="5">5 rows</option>
                                        <option value="10">10 rows</option>
                                        <option value="25">25 rows</option>
                                        <option value="50">50 rows</option>
                                    </select>
                                </label>
                            </div>
                        </div>

                        @if ($sourceSearch !== '' || $sourceStatusFilter !== 'all' || $sourceKindFilter !== 'all')
                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium text-zinc-600">
                                @if ($sourceSearch !== '')
                                    <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-sky-700">Search: {{ $sourceSearch }}</span>
                                @endif
                                @if ($sourceStatusFilter !== 'all')
                                    <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5">Status: {{ str_replace('_', ' ', $sourceStatusFilter) }}</span>
                                @endif
                                @if ($sourceKindFilter !== 'all')
                                    <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5">Type: {{ \App\Models\SheetSource::sourceKindLabel($sourceKindFilter) }}</span>
                                @endif
                            </div>
                        @endif
                    </article>

                    <div class="space-y-3 md:hidden">
                        @forelse ($sheetSources as $source)
                            <div class="mobile-record-card {{ $editingSourceId === $source->id ? 'border-sky-200 bg-sky-50/70' : '' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="mobile-record-card-label">Source</div>
                                        <div class="mobile-record-card-value">{{ $source->name }}</div>
                                        <div class="mt-1 text-xs text-zinc-400">{{ \App\Models\SheetSource::typeLabel($source->type) }} · {{ \App\Models\SheetSource::sourceKindLabel($source->source_kind) }}</div>
                                    </div>
                                    <div class="flex flex-col items-end gap-2 text-right">
                                        <span class="rounded-full px-3 py-1 text-[11px] font-medium {{ $source->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600' }}">
                                            {{ $source->is_active ? 'Active' : 'Paused' }}
                                        </span>
                                        <span class="rounded-full px-3 py-1 text-[11px] font-medium {{ $source->sync_status === 'failed' ? 'bg-rose-100 text-rose-800' : ($source->sync_status === 'synced' ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800') }}">
                                            {{ \Illuminate\Support\Str::headline($source->sync_status) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <div class="mobile-record-card-label">Company</div>
                                        <div class="mobile-record-card-value">{{ $source->company?->name ?? 'No company' }}</div>
                                    </div>
                                    <div>
                                        <div class="mobile-record-card-label">Workspace</div>
                                        <div class="mobile-record-card-value">{{ $source->workspace?->name ?? 'No workspace assigned' }}</div>
                                    </div>
                                    <div>
                                        <div class="mobile-record-card-label">Last synced</div>
                                        <div class="mobile-record-card-value">{{ $source->last_synced_at?->format('d M Y, H:i') ?? 'Never synced' }}</div>
                                    </div>
                                    <div>
                                        <div class="mobile-record-card-label">Created</div>
                                        <div class="mobile-record-card-value">{{ $source->created_at?->format('d M Y') ?? 'Unknown' }}</div>
                                    </div>
                                </div>

                                @if ($source->description)
                                    <p class="mt-4 text-sm leading-6 text-zinc-600">{{ $source->description }}</p>
                                @endif

                                @if ($source->last_error)
                                    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                                        <div class="text-[11px] uppercase tracking-[0.2em] text-rose-500">Latest error</div>
                                        <p class="mt-2 text-sm leading-6 text-rose-700">{{ $source->last_error }}</p>
                                    </div>
                                @endif

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if ($source->workspace_id)
                                        <button wire:click="$set('workspaceId', {{ $source->workspace_id }})" type="button" class="rounded-xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                            Open workspace
                                        </button>
                                    @endif
                                    <button wire:click="startEditingSource({{ $source->id }})" type="button" class="rounded-xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                        Review & edit
                                    </button>
                                    <button wire:click="syncSource({{ $source->id }})" type="button" class="rounded-xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                        Sync now
                                    </button>
                                    <button
                                        wire:click="deleteSheetSource({{ $source->id }})"
                                        wire:confirm="Delete this source? Imported leads and reports will stay in the database, but this source connection will be removed."
                                        type="button"
                                        class="rounded-xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-50"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="mobile-record-card text-sm text-zinc-500">No sources matched the current filters.</div>
                        @endforelse
                    </div>

                    <div class="hidden overflow-x-auto rounded-[1.5rem] border border-zinc-200 md:block">
                        <table class="min-w-full border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr class="bg-zinc-50 text-left text-zinc-500">
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Source</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Company</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Workspace</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Connector</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Sync</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Last synced</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sheetSources as $source)
                                    <tr class="odd:bg-white even:bg-zinc-50/60 {{ $editingSourceId === $source->id ? 'bg-sky-50 ring-1 ring-inset ring-sky-200' : '' }}">
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                            <div class="font-medium text-zinc-900">{{ $source->name }}</div>
                                            <div class="text-xs text-zinc-400">#{{ $source->id }}</div>
                                            <div class="mt-1 text-xs text-zinc-400">Created {{ $source->created_at?->format('d M Y') ?? 'Unknown' }}</div>
                                            @if ($source->description)
                                                <div class="mt-1 line-clamp-2 text-xs text-zinc-400">{{ $source->description }}</div>
                                            @endif
                                            @if ($source->last_error)
                                                <div class="mt-2 rounded-lg bg-rose-50 px-2 py-1 text-xs text-rose-700">{{ $source->last_error }}</div>
                                            @endif
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $source->company?->name ?? 'No company' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $source->workspace?->name ?? 'No workspace assigned' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                            <div>{{ \App\Models\SheetSource::typeLabel($source->type) }}</div>
                                            <div class="mt-1 text-xs text-zinc-400">{{ \App\Models\SheetSource::sourceKindLabel($source->source_kind) }}</div>
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3">
                                            <div class="flex flex-col items-start gap-2">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $source->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600' }}">
                                                    {{ $source->is_active ? 'Active' : 'Paused' }}
                                                </span>
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $source->sync_status === 'failed' ? 'bg-rose-100 text-rose-800' : ($source->sync_status === 'synced' ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800') }}">
                                                    {{ \Illuminate\Support\Str::headline($source->sync_status) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $source->last_synced_at?->format('d M Y, H:i') ?? 'Never synced' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                @if ($source->workspace_id)
                                                    <button wire:click="$set('workspaceId', {{ $source->workspace_id }})" type="button" class="rounded-xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                                        Open
                                                    </button>
                                                @endif
                                                <button wire:click="startEditingSource({{ $source->id }})" type="button" class="rounded-xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                                    Edit
                                                </button>
                                                <button wire:click="syncSource({{ $source->id }})" type="button" class="rounded-xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                                    Sync
                                                </button>
                                                <button
                                                    wire:click="deleteSheetSource({{ $source->id }})"
                                                    wire:confirm="Delete this source? Imported leads and reports will stay in the database, but this source connection will be removed."
                                                    type="button"
                                                    class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-50"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-10 text-center text-zinc-500">No sources matched the current filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $sheetSources->links() }}
                    </div>

                    @if ($editingSourceId)
                        <form wire:submit="updateSheetSource" class="grid gap-3 rounded-[1.5rem] border border-zinc-200 bg-white p-4">
                            <div>
                                <h3 class="text-sm font-semibold text-zinc-950">Edit source</h3>
                                <p class="mt-1 text-xs text-zinc-500">Update workspace assignment, connector settings, status, and sync details for the selected source.</p>
                            </div>
                            <div class="grid gap-3 lg:grid-cols-2">
                                <select wire:model="editingSourceForm.workspace_id" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                    <option value="">No workspace</option>
                                    @foreach ($workspaces as $workspace)
                                        <option value="{{ $workspace->id }}">{{ $workspace->company->name }} / {{ $workspace->name }}</option>
                                    @endforeach
                                </select>
                                <select wire:model="editingSourceForm.type" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                    @foreach (\App\Models\SheetSource::availableTypes() as $type)
                                        <option value="{{ $type }}">{{ \App\Models\SheetSource::typeLabel($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <input wire:model="editingSourceForm.name" type="text" placeholder="Source name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="editingSourceForm.url" type="text" placeholder="{{ ($editingSourceForm['source_kind'] ?? '') === \App\Models\SheetSource::SOURCE_KIND_CARGOWISE_API ? 'CargoWise endpoint URL' : 'Source URL or upload reference' }}" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <select wire:model.live="editingSourceForm.source_kind" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                @foreach (\App\Models\SheetSource::SOURCE_KINDS as $sourceKind)
                                    <option value="{{ $sourceKind }}">{{ \App\Models\SheetSource::sourceKindLabel($sourceKind) }}</option>
                                @endforeach
                            </select>
                            @if (($editingSourceForm['source_kind'] ?? '') === \App\Models\SheetSource::SOURCE_KIND_CARGOWISE_API)
                                <div class="grid gap-3 lg:grid-cols-2">
                                    <select wire:model.live="editingSourceForm.cargo_auth_mode" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                        @foreach (\App\Models\SheetSource::cargoWiseAuthModes() as $mode => $label)
                                            <option value="{{ $mode }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select wire:model="editingSourceForm.cargo_format" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                        @foreach (\App\Models\SheetSource::cargoWiseFormats() as $format => $label)
                                            <option value="{{ $format }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if (($editingSourceForm['cargo_auth_mode'] ?? 'basic') === 'basic')
                                    <div class="grid gap-3 lg:grid-cols-2">
                                        <input wire:model="editingSourceForm.cargo_username" type="text" placeholder="CargoWise username" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                        <input wire:model="editingSourceForm.cargo_password" type="password" placeholder="CargoWise password" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                    </div>
                                @elseif (($editingSourceForm['cargo_auth_mode'] ?? '') === 'bearer')
                                    <input wire:model="editingSourceForm.cargo_token" type="password" placeholder="CargoWise bearer token" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                @endif
                                <input wire:model="editingSourceForm.cargo_data_path" type="text" placeholder="Response data path, e.g. data.rows" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            @endif
                            <input wire:model="editingSourceForm.description" type="text" placeholder="Description" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <label class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700">
                                <input wire:model="editingSourceForm.is_active" type="checkbox" class="h-4 w-4 rounded border-zinc-300 text-sky-900 focus:ring-sky-900" />
                                Source is active
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                    Save source changes
                                </button>
                                <button wire:click="cancelEditingSource" type="button" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    @endif

                    <div class="grid gap-6 xl:grid-cols-3">
                        <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                            <h2 class="text-lg font-semibold text-zinc-950">Source support view</h2>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Connector mix</div>
                                    <div class="mt-3 space-y-3">
                                        @foreach ($sourceKindRows as $kindRow)
                                            <div>
                                                <div class="flex items-center justify-between gap-4 text-sm">
                                                    <span class="font-medium text-zinc-700">{{ $kindRow['label'] }}</span>
                                                    <span class="text-zinc-500">{{ $kindRow['count'] }}</span>
                                                </div>
                                                <div class="mt-2 h-2 rounded-full bg-zinc-100">
                                                    <div class="h-2 rounded-full bg-sky-500" style="width: {{ max(6, $kindRow['share']) }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="border-t border-zinc-200 pt-4">
                                    <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Needs support</div>
                                    <div class="mt-3 space-y-3">
                                        @forelse ($sourceAttentionRows as $attentionSource)
                                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="font-medium text-zinc-900">{{ $attentionSource['name'] }}</div>
                                                    <div class="text-xs text-zinc-500">#{{ $attentionSource['id'] }}</div>
                                                </div>
                                                <div class="mt-1 text-xs text-zinc-500">{{ $attentionSource['company_name'] }} · {{ $attentionSource['workspace_name'] }}</div>
                                                <div class="mt-2 text-sm leading-6 text-zinc-600">{{ implode(' · ', $attentionSource['reasons']) }}</div>
                                            </div>
                                        @empty
                                            <div class="rounded-xl border border-dashed border-zinc-200 px-4 py-8 text-sm text-zinc-500">
                                                No source issues need attention right now.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="grid gap-3 rounded-[1.5rem] border border-zinc-200 p-4">
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-950">Connect Google Sheets</h2>
                            <p class="mt-1 text-sm text-zinc-500">
                                Save your Google OAuth app once, connect the company Google account, then select the spreadsheet tab to sync.
                            </p>
                        </div>

                        @if ($currentWorkspace)
                            <div class="rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600">
                                Company connection: {{ $currentWorkspace->company->name }}
                            </div>
                        @endif

                        <form wire:submit="saveGoogleClientConfig" class="grid gap-3">
                            <input wire:model="googleOauthForm.client_id" type="text" placeholder="Google OAuth Client ID" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="googleOauthForm.client_secret" type="text" placeholder="Google OAuth Client Secret" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                Save Google App
                            </button>
                        </form>

                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600">
                            Redirect URI:
                            <span class="mt-1 block break-all font-medium text-zinc-900">{{ route('admin.google.callback') }}</span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if ($currentCompany && $googleHasClientConfig)
                                <a href="{{ route('admin.google.redirect', ['company' => $currentCompany->id, 'tab' => 'sources']) }}" class="rounded-xl bg-sky-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-sky-800">
                                    {{ $googleAccount ? 'Reconnect Google' : 'Connect Google' }}
                                </a>
                            @else
                                <button type="button" disabled class="rounded-xl bg-zinc-300 px-4 py-3 text-sm font-medium text-white">
                                    Save Google App First
                                </button>
                            @endif

                            @if ($googleAccount && $currentCompany)
                                <form method="POST" action="{{ route('admin.google.disconnect', ['company' => $currentCompany->id, 'tab' => 'sources']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-rose-200 px-4 py-3 text-sm font-medium text-rose-700 transition hover:bg-rose-50">
                                        Disconnect
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm">
                            @if ($googleAccount)
                                <div class="font-medium text-zinc-900">{{ $googleAccount->google_email ?: 'Google account connected' }}</div>
                                <div class="mt-1 text-zinc-500">Connected for {{ $currentCompany?->name }}{{ $googleAccount->expires_at ? ' · token refresh '.($googleAccount->expires_at->isFuture() ? 'active' : 'needed soon') : '' }}</div>
                            @else
                                <div class="text-zinc-500">No Google account connected for this company yet.</div>
                            @endif
                        </div>
                    </article>

                    <form wire:submit="createGoogleSheetSource" class="grid gap-3 rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-4">
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-950">Create source from Google</h2>
                            <p class="mt-1 text-sm text-zinc-500">
                                Load the connected account spreadsheets, choose a tab, and create a sync source for leads, opportunities, or reports.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                wire:click="loadGoogleSpreadsheets"
                                type="button"
                                class="rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-50"
                                @disabled(! $googleAccount)
                            >
                                Load Google Sheets
                            </button>
                            <button
                                wire:click="loadGoogleSheetTabs"
                                type="button"
                                class="rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-50"
                                @disabled(! $googleAccount || blank($googleSourceForm['spreadsheet_id']))
                            >
                                Load Tabs
                            </button>
                        </div>

                        <select wire:model="googleSourceForm.type" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" @disabled(! $googleAccount)>
                            <option value="{{ \App\Models\SheetSource::TYPE_LEADS }}">Leads</option>
                            <option value="{{ \App\Models\SheetSource::TYPE_OPPORTUNITIES }}">Opportunities</option>
                            <option value="{{ \App\Models\SheetSource::TYPE_REPORTS }}">Reports</option>
                        </select>

                        <select wire:model.live="googleSourceForm.spreadsheet_id" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" @disabled(! $googleAccount)>
                            <option value="">Select spreadsheet</option>
                            @foreach ($googleSpreadsheets as $spreadsheet)
                                <option value="{{ $spreadsheet['id'] }}">{{ $spreadsheet['name'] }}</option>
                            @endforeach
                        </select>

                        <select wire:model="googleSourceForm.sheet_gid" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" @disabled(blank($googleSheetTabs))>
                            <option value="">Select tab</option>
                            @foreach ($googleSheetTabs as $sheetTab)
                                <option value="{{ $sheetTab['gid'] }}">{{ $sheetTab['title'] }}</option>
                            @endforeach
                        </select>

                        <input wire:model="googleSourceForm.name" type="text" placeholder="Source name (optional)" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" @disabled(! $googleAccount) />
                        <input wire:model="googleSourceForm.description" type="text" placeholder="Description (optional)" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" @disabled(! $googleAccount) />

                        @if ($googleSpreadsheets === [] && $googleAccount)
                            <p class="text-xs text-zinc-500">Click `Load Google Sheets` to fetch the connected account spreadsheets.</p>
                        @endif

                        <button
                            type="submit"
                            class="rounded-xl bg-sky-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:bg-sky-400"
                            @disabled(! $googleAccount || blank($googleSourceForm['spreadsheet_id']) || blank($googleSourceForm['sheet_gid']))
                        >
                            Create Google Source
                        </button>
                    </form>

                    <form wire:submit="uploadLeadCsv" class="grid gap-3 rounded-[1.5rem] border border-zinc-200 p-4">
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-950">Upload CRM CSV</h2>
                            <p class="mt-1 text-sm text-zinc-500">
                                Import rows directly into the selected `Leads` or `Opportunities` table using the matching CSV export format.
                            </p>
                        </div>

                        @if ($currentWorkspace)
                            <div class="rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600">
                                Import target: {{ $currentWorkspace->company->name }} / {{ $currentWorkspace->name }}
                            </div>
                        @endif

                        <select wire:model="csvUploadForm.type" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none">
                            <option value="{{ \App\Models\SheetSource::TYPE_LEADS }}">Leads table</option>
                            <option value="{{ \App\Models\SheetSource::TYPE_OPPORTUNITIES }}">Opportunities table</option>
                        </select>

                        <input
                            wire:model="leadCsvUpload"
                            type="file"
                            accept=".csv,text/csv"
                            class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none"
                        />

                        @error('leadCsvUpload')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror

                        <div wire:loading wire:target="leadCsvUpload,uploadLeadCsv" class="text-sm text-zinc-500">
                            Uploading and importing CSV...
                        </div>

                        <button
                            type="submit"
                            class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-400"
                            wire:loading.attr="disabled"
                            wire:target="leadCsvUpload,uploadLeadCsv"
                        >
                            Import CSV
                        </button>
                    </form>

                    <form wire:submit="saveSheetSource" class="grid gap-3 rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-4">
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-950">Add public URL source</h2>
                            <p class="mt-1 text-sm text-zinc-500">
                                Use this for public CSV files or public Google Sheet export links. Private Google Sheets should use the Google connect flow above.
                            </p>
                        </div>

                        <select wire:model="sourceForm.workspace_id" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            @foreach ($workspaces as $workspace)
                                <option value="{{ $workspace->id }}">{{ $workspace->company->name }} / {{ $workspace->name }}</option>
                            @endforeach
                        </select>
                        <select wire:model="sourceForm.type" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            @foreach (\App\Models\SheetSource::availableTypes() as $type)
                                <option value="{{ $type }}">{{ \App\Models\SheetSource::typeLabel($type) }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="sourceForm.source_kind" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            @foreach (\App\Models\SheetSource::SOURCE_KINDS as $sourceKind)
                                <option value="{{ $sourceKind }}">{{ \App\Models\SheetSource::sourceKindLabel($sourceKind) }}</option>
                            @endforeach
                        </select>
                        <input wire:model="sourceForm.name" type="text" placeholder="Source name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="sourceForm.url" type="url" placeholder="{{ ($sourceForm['source_kind'] ?? '') === \App\Models\SheetSource::SOURCE_KIND_CARGOWISE_API ? 'CargoWise endpoint URL' : 'Public Google Sheet or CSV URL' }}" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        @if (($sourceForm['source_kind'] ?? '') === \App\Models\SheetSource::SOURCE_KIND_CARGOWISE_API)
                            <select wire:model.live="sourceForm.cargo_auth_mode" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                @foreach (\App\Models\SheetSource::cargoWiseAuthModes() as $mode => $label)
                                    <option value="{{ $mode }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <select wire:model="sourceForm.cargo_format" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                @foreach (\App\Models\SheetSource::cargoWiseFormats() as $format => $label)
                                    <option value="{{ $format }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @if (($sourceForm['cargo_auth_mode'] ?? 'basic') === 'basic')
                                <input wire:model="sourceForm.cargo_username" type="text" placeholder="CargoWise username" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <input wire:model="sourceForm.cargo_password" type="password" placeholder="CargoWise password" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            @elseif (($sourceForm['cargo_auth_mode'] ?? '') === 'bearer')
                                <input wire:model="sourceForm.cargo_token" type="password" placeholder="CargoWise bearer token" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            @endif
                            <input wire:model="sourceForm.cargo_data_path" type="text" placeholder="Response data path, e.g. data.rows" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        @endif
                        <input wire:model="sourceForm.description" type="text" placeholder="Description" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <button type="submit" class="rounded-xl bg-sky-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-sky-800">
                            Add Source
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if ($activeTab === 'access')
            <div class="space-y-6 p-4">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($userOverviewStats as $stat)
                        <article class="rounded-[1.5rem] border border-zinc-200 bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $stat['label'] }}</p>
                            <p class="mt-3 text-2xl font-semibold text-zinc-950">{{ $stat['value'] }}</p>
                            <p class="mt-2 text-sm text-zinc-500">{{ $stat['detail'] }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="space-y-4">
                    <article class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50/70 p-4 sm:p-5">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-zinc-950">All users</h2>
                                <p class="mt-1 text-sm text-zinc-500">Search by name, email, company, workspace, role, or title. Manage activation, assignments, and default workspace from one place.</p>
                            </div>
                            <div class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-semibold text-zinc-600 shadow-sm">
                                {{ $workspaceUsers->total() }} user{{ $workspaceUsers->total() === 1 ? '' : 's' }} found
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-center">
                                <label class="block">
                                    <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Search</span>
                                    <input
                                        wire:model.live.debounce.300ms="userSearch"
                                        type="text"
                                        placeholder="Find by user, company, workspace, role, or title"
                                        class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3.5 text-sm text-zinc-700 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-sky-300 focus:ring-2 focus:ring-sky-100"
                                    />
                                </label>
                                <div class="hidden xl:flex xl:justify-end">
                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-500 shadow-sm">
                                        <span class="font-medium text-zinc-900">{{ $workspaceUsers->total() }}</span>
                                        matching records
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-4">
                                <label class="block rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                                    <span class="block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Status</span>
                                    <select wire:model.live="userStatusFilter" class="mt-2 w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-700 outline-none focus:ring-0">
                                        <option value="all">All statuses</option>
                                        <option value="active">Active only</option>
                                        <option value="inactive">Inactive only</option>
                                        <option value="admins">Admins</option>
                                        <option value="unverified">Unverified email</option>
                                        <option value="multi_workspace">Multi-workspace</option>
                                    </select>
                                </label>

                                <label class="block rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                                    <span class="block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Role</span>
                                    <select wire:model.live="userRoleFilter" class="mt-2 w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-700 outline-none focus:ring-0">
                                        <option value="all">All roles</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->slug }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                                    <span class="block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Sort</span>
                                    <select wire:model.live="workspaceUserSort" class="mt-2 w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-700 outline-none focus:ring-0">
                                        <option value="newest">Newest date</option>
                                        <option value="oldest">Oldest date</option>
                                        <option value="name_asc">Name A-Z</option>
                                        <option value="name_desc">Name Z-A</option>
                                        <option value="company_asc">Company A-Z</option>
                                    </select>
                                </label>

                                <label class="block rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                                    <span class="block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Density</span>
                                    <select wire:model.live="workspaceUserPerPage" class="mt-2 w-full border-0 bg-transparent p-0 text-sm font-medium text-zinc-700 outline-none focus:ring-0">
                                        <option value="5">5 rows</option>
                                        <option value="10">10 rows</option>
                                        <option value="25">25 rows</option>
                                        <option value="50">50 rows</option>
                                    </select>
                                </label>
                            </div>
                        </div>

                        @if ($userSearch !== '' || $userStatusFilter !== 'all' || $userRoleFilter !== 'all')
                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium text-zinc-600">
                                @if ($userSearch !== '')
                                    <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-sky-700">Search: {{ $userSearch }}</span>
                                @endif
                                @if ($userStatusFilter !== 'all')
                                    <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5">Status: {{ str_replace('_', ' ', $userStatusFilter) }}</span>
                                @endif
                                @if ($userRoleFilter !== 'all')
                                    <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5">Role: {{ $roles->firstWhere('slug', $userRoleFilter)?->name ?? $userRoleFilter }}</span>
                                @endif
                            </div>
                        @endif
                    </article>

                    <div class="space-y-3 md:hidden">
                        @forelse ($workspaceUsers as $workspaceUser)
                            <div class="mobile-record-card {{ $editingUserId === $workspaceUser->id ? 'border-sky-200 bg-sky-50/70' : '' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="mobile-record-card-label">User</div>
                                        <div class="mobile-record-card-value">{{ $workspaceUser->name }}</div>
                                        <div class="mt-1 text-xs text-zinc-400">{{ $workspaceUser->email }}</div>
                                    </div>
                                    <div class="text-right text-xs text-zinc-500">{{ $workspaceUser->company?->name ?? 'No company' }}</div>
                                </div>

                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <div class="mobile-record-card-label">Role</div>
                                        <div class="mobile-record-card-value">{{ $workspaceUser->roles->pluck('name')->join(', ') ?: 'No role' }}</div>
                                    </div>
                                    <div>
                                        <div class="mobile-record-card-label">Status</div>
                                        <div class="mobile-record-card-value">{{ $workspaceUser->is_active ? 'Active' : 'Inactive' }}</div>
                                    </div>
                                    <div>
                                        <div class="mobile-record-card-label">Default workspace</div>
                                        <div class="mobile-record-card-value">{{ $workspaceUser->defaultWorkspace?->name ?? 'Not set' }}</div>
                                    </div>
                                    <div>
                                        <div class="mobile-record-card-label">Access</div>
                                        <div class="mobile-record-card-value">{{ $workspaceUser->workspaces_count }} workspace{{ $workspaceUser->workspaces_count === 1 ? '' : 's' }}</div>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if ($workspaceUser->default_workspace_id)
                                        <button wire:click="$set('workspaceId', {{ $workspaceUser->default_workspace_id }})" type="button" class="rounded-xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                            Open workspace
                                        </button>
                                    @endif
                                    <button wire:click="startEditingUser({{ $workspaceUser->id }})" type="button" class="rounded-xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                        Review & edit
                                    </button>
                                    <button wire:click="toggleUserActive({{ $workspaceUser->id }})" type="button" class="rounded-xl border {{ $workspaceUser->is_active ? 'border-rose-200 text-rose-700 hover:bg-rose-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }} px-4 py-2 text-sm font-medium transition">
                                        {{ $workspaceUser->is_active ? 'Pause user' : 'Activate user' }}
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="mobile-record-card text-sm text-zinc-500">No users matched the current filters.</div>
                        @endforelse
                    </div>

                    <div class="hidden overflow-x-auto rounded-[1.5rem] border border-zinc-200 md:block">
                        <table class="min-w-full border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr class="bg-zinc-50 text-left text-zinc-500">
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">User</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Company</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Role</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Default workspace</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Access</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Status</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($workspaceUsers as $workspaceUser)
                                    <tr class="odd:bg-white even:bg-zinc-50/60 {{ $editingUserId === $workspaceUser->id ? 'bg-sky-50 ring-1 ring-inset ring-sky-200' : '' }}">
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                            <div class="font-medium text-zinc-900">{{ $workspaceUser->name }}</div>
                                            <div class="text-xs text-zinc-400">{{ $workspaceUser->email }}</div>
                                            @if ($workspaceUser->job_title)
                                                <div class="mt-1 text-xs text-zinc-400">{{ $workspaceUser->job_title }}</div>
                                            @endif
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $workspaceUser->company?->name ?? 'No company' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $workspaceUser->roles->pluck('name')->join(', ') ?: 'No role' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $workspaceUser->defaultWorkspace?->name ?? 'Not set' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $workspaceUser->workspaces_count }} workspace{{ $workspaceUser->workspaces_count === 1 ? '' : 's' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $workspaceUser->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600' }}">
                                                {{ $workspaceUser->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                @if ($workspaceUser->default_workspace_id)
                                                    <button wire:click="$set('workspaceId', {{ $workspaceUser->default_workspace_id }})" type="button" class="rounded-xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                                        Open
                                                    </button>
                                                @endif
                                                <button wire:click="startEditingUser({{ $workspaceUser->id }})" type="button" class="rounded-xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                                    Edit
                                                </button>
                                                <button wire:click="toggleUserActive({{ $workspaceUser->id }})" type="button" class="rounded-xl border {{ $workspaceUser->is_active ? 'border-rose-200 text-rose-700 hover:bg-rose-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }} px-3 py-2 text-sm font-medium transition">
                                                    {{ $workspaceUser->is_active ? 'Pause' : 'Activate' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-10 text-center text-zinc-500">No users matched the current filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $workspaceUsers->links() }}
                    </div>

                    @if ($editingUserId)
                        <form wire:submit="updateUser" class="grid gap-3 rounded-[1.5rem] border border-zinc-200 bg-white p-4">
                            <div>
                                <h3 class="text-sm font-semibold text-zinc-950">Edit user</h3>
                                <p class="mt-1 text-xs text-zinc-500">Update identity, role, workspace access, default workspace, and activation state.</p>
                            </div>
                            <input wire:model="editingUserForm.name" type="text" placeholder="Full name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="editingUserForm.email" type="email" placeholder="Email" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="editingUserForm.password" type="text" placeholder="New password (optional)" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="editingUserForm.job_title" type="text" placeholder="Job title" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <div class="grid gap-3 lg:grid-cols-2">
                                <select wire:model="editingUserForm.role" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->slug }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <select wire:model="editingUserForm.default_workspace_id" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                    <option value="">Select default workspace</option>
                                    @foreach ($workspaces->whereIn('id', $editingUserForm['workspace_ids'] ?? []) as $workspace)
                                        <option value="{{ $workspace->id }}">{{ $workspace->company->name }} / {{ $workspace->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <select wire:model="editingUserForm.workspace_ids" multiple class="min-h-[160px] rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                @foreach ($workspaces as $workspace)
                                    <option value="{{ $workspace->id }}">{{ $workspace->company->name }} / {{ $workspace->name }}</option>
                                @endforeach
                            </select>
                            <label class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700">
                                <input wire:model="editingUserForm.is_active" type="checkbox" class="h-4 w-4 rounded border-zinc-300 text-sky-900 focus:ring-sky-900" />
                                User account is active
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                    Save user changes
                                </button>
                                <button wire:click="cancelEditingUser" type="button" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    @endif

                    <div class="grid gap-6 xl:grid-cols-3">
                        <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                            <h2 class="text-lg font-semibold text-zinc-950">User support view</h2>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Role distribution</div>
                                    <div class="mt-3 space-y-3">
                                        @foreach ($roleDistributionRows as $roleRow)
                                            <div>
                                                <div class="flex items-center justify-between gap-4 text-sm">
                                                    <span class="font-medium text-zinc-700">{{ $roleRow['name'] }}</span>
                                                    <span class="text-zinc-500">{{ $roleRow['count'] }}</span>
                                                </div>
                                                <div class="mt-2 h-2 rounded-full bg-zinc-100">
                                                    <div class="h-2 rounded-full bg-sky-500" style="width: {{ max(6, $roleRow['share']) }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="border-t border-zinc-200 pt-4">
                                    <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Needs attention</div>
                                    <div class="mt-3 space-y-3">
                                        @forelse ($userAttentionRows as $attentionUser)
                                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="font-medium text-zinc-900">{{ $attentionUser['name'] }}</div>
                                                    <div class="text-xs text-zinc-500">#{{ $attentionUser['id'] }}</div>
                                                </div>
                                                <div class="mt-1 text-xs text-zinc-500">{{ $attentionUser['email'] }}</div>
                                                <div class="mt-2 text-sm leading-6 text-zinc-600">{{ implode(' · ', $attentionUser['reasons']) }}</div>
                                            </div>
                                        @empty
                                            <div class="rounded-xl border border-dashed border-zinc-200 px-4 py-8 text-sm text-zinc-500">
                                                No urgent user account issues right now.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </article>

                        <form wire:submit="createUser" class="grid gap-3 rounded-[1.5rem] border border-zinc-200 p-4">
                            <h2 class="text-lg font-semibold text-zinc-950">Create user</h2>
                            <input wire:model="userForm.name" type="text" placeholder="Full name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="userForm.email" type="email" placeholder="Email" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="userForm.password" type="text" placeholder="Temporary password" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="userForm.job_title" type="text" placeholder="Job title" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <select wire:model="userForm.role" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->slug }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model="userForm.workspace_ids" multiple class="min-h-[160px] rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                @foreach ($workspaces as $workspace)
                                    <option value="{{ $workspace->id }}">{{ $workspace->company->name }} / {{ $workspace->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                Create User
                            </button>
                        </form>

                        <form wire:submit="createRole" class="grid gap-3 rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-4">
                            <h2 class="text-lg font-semibold text-zinc-950">Create role</h2>
                            <input wire:model="roleForm.name" type="text" placeholder="Role name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="roleForm.slug" type="text" placeholder="role-slug" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="roleForm.description" type="text" placeholder="Description" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="roleForm.level" type="number" min="1" max="99" placeholder="Level" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <button type="submit" class="rounded-xl bg-sky-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-sky-800">
                                Create Role
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if ($activeTab === 'structure')
            <div class="grid gap-6 p-4 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-6">
                    <article class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-4">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-zinc-950">Companies</h2>
                                <p class="mt-1 text-sm text-zinc-500">Search and review companies before creating workspaces or updating the selected record.</p>
                            </div>
                            <div class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600">
                                {{ $companyDirectoryRows->count() }} compan{{ $companyDirectoryRows->count() === 1 ? 'y' : 'ies' }} found
                            </div>
                        </div>
                        <div class="mt-4">
                            <input wire:model.live.debounce.300ms="companySearch" type="text" placeholder="Find by company, industry, timezone, or contact" class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse ($companyDirectoryRows as $company)
                                <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="font-medium text-zinc-900">{{ $company->name }}</div>
                                            <div class="mt-1 text-sm text-zinc-500">{{ $company->industry }} · {{ $company->timezone }}</div>
                                            @if ($company->contact_email || $company->contact_phone)
                                                <div class="mt-2 text-xs text-zinc-500">{{ $company->contact_email ?: 'No email' }}{{ $company->contact_email && $company->contact_phone ? ' · ' : '' }}{{ $company->contact_phone ?: '' }}</div>
                                            @endif
                                        </div>
                                        <div class="text-right text-sm text-zinc-500">{{ $company->workspaces_count }} workspace{{ $company->workspaces_count === 1 ? '' : 's' }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-zinc-200 px-4 py-8 text-sm text-zinc-500">
                                    No companies matched the current search.
                                </div>
                            @endforelse
                        </div>
                    </article>

                    <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                        <h2 class="text-lg font-semibold text-zinc-950">Workspaces</h2>
                        <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1.2fr)_220px_160px]">
                            <input
                                wire:model.live.debounce.300ms="workspaceSearch"
                                type="text"
                                placeholder="Find by workspace, company, or mode"
                                class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none"
                            />
                            <select wire:model.live="workspaceSort" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                <option value="newest">Newest date</option>
                                <option value="oldest">Oldest date</option>
                                <option value="name_asc">Name A-Z</option>
                                <option value="name_desc">Name Z-A</option>
                            </select>
                            <select wire:model.live="workspacePerPage" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                <option value="5">5 rows</option>
                                <option value="10">10 rows</option>
                                <option value="25">25 rows</option>
                                <option value="50">50 rows</option>
                            </select>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-zinc-50 px-4 py-3 text-sm">
                            <div class="text-zinc-600">
                                {{ $workspaceRows->total() }} workspace{{ $workspaceRows->total() === 1 ? '' : 's' }} found
                            </div>
                            @if ($workspaceSearch !== '')
                                <div class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600">
                                    Search: {{ $workspaceSearch }}
                                </div>
                            @endif
                        </div>
                        <div class="mt-4 space-y-4">
                            @forelse ($workspaceRows as $workspaceRow)
                                <article wire:key="workspace-row-{{ $workspaceRow->id }}" class="rounded-[1.35rem] border {{ $editingWorkspaceId === $workspaceRow->id ? 'border-sky-200 bg-sky-50/40' : 'border-zinc-200 bg-white' }} p-4 shadow-sm">
                                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div class="space-y-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <div class="text-lg font-semibold text-zinc-950">{{ $workspaceRow->name }}</div>
                                                <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-[11px] font-medium text-zinc-600">#{{ $workspaceRow->id }}</span>
                                                <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-medium text-sky-700">{{ $workspaceRow->templateName() }}</span>
                                                @if ($workspaceRow->is_default)
                                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-medium text-emerald-700">Default</span>
                                                @endif
                                                @if ((int) $workspaceId === $workspaceRow->id)
                                                    <span class="rounded-full border border-zinc-900 bg-zinc-950 px-3 py-1 text-[11px] font-medium text-white">Current</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-zinc-600">
                                                <span class="font-medium text-zinc-800">{{ $workspaceRow->company->name }}</span>
                                                @if ($workspaceRow->description)
                                                    <span> · {{ $workspaceRow->description }}</span>
                                                @endif
                                            </div>
                                            <p class="max-w-3xl text-sm leading-7 text-zinc-500">{{ $workspaceRow->templateDescription() }}</p>
                                            <div class="grid gap-3 sm:grid-cols-3">
                                                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                                    <div class="text-[11px] uppercase tracking-[0.2em] text-zinc-400">Users</div>
                                                    <div class="mt-2 text-lg font-semibold text-zinc-950">{{ $workspaceRow->users_count }}</div>
                                                </div>
                                                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                                    <div class="text-[11px] uppercase tracking-[0.2em] text-zinc-400">Leads</div>
                                                    <div class="mt-2 text-lg font-semibold text-zinc-950">{{ $workspaceRow->leads_count }}</div>
                                                </div>
                                                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                                    <div class="text-[11px] uppercase tracking-[0.2em] text-zinc-400">Opportunities</div>
                                                    <div class="mt-2 text-lg font-semibold text-zinc-950">{{ $workspaceRow->opportunities_count }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap gap-2 xl:max-w-[220px] xl:justify-end">
                                            <button
                                                wire:click="$set('workspaceId', {{ $workspaceRow->id }})"
                                                type="button"
                                                class="rounded-xl border border-zinc-200 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50"
                                            >
                                                Select
                                            </button>
                                            <button wire:click="startEditingWorkspace({{ $workspaceRow->id }})" type="button" class="rounded-xl border border-zinc-200 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                                Review & edit
                                            </button>
                                            <button
                                                wire:click="requestWorkspaceDeletion({{ $workspaceRow->id }})"
                                                type="button"
                                                class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-medium text-rose-700 transition hover:bg-rose-50"
                                            >
                                                Delete workspace
                                            </button>
                                        </div>
                                    </div>

                                    @if ($editingWorkspaceId === $workspaceRow->id)
                                        <div class="mt-4 border-t border-zinc-200 pt-4">
                                            <form wire:submit="updateWorkspace" class="grid gap-3 rounded-[1.25rem] border border-zinc-200 bg-white p-4">
                                                <div>
                                                    <h3 class="text-sm font-semibold text-zinc-950">Edit workspace</h3>
                                                    <p class="mt-1 text-xs text-zinc-500">Review the company, workspace name, description, default status, and raw settings for the selected workspace.</p>
                                                </div>

                                                <div class="rounded-xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-zinc-700">
                                                    <div class="font-medium text-zinc-950">Workspace mode</div>
                                                    <div class="mt-1">{{ $workspaceRow->templateName() }}</div>
                                                    <div class="mt-1 text-xs leading-6 text-zinc-500">{{ $workspaceRow->templateDescription() }}</div>
                                                </div>

                                                <select wire:model="editingWorkspaceForm.company_id" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                                    @foreach ($companies as $company)
                                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                    @endforeach
                                                </select>
                                                <input wire:model="editingWorkspaceForm.name" type="text" placeholder="Workspace name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                                <input wire:model="editingWorkspaceForm.description" type="text" placeholder="Description" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                                <textarea wire:model="editingWorkspaceForm.settings_json" rows="5" placeholder='{"timezone":"Asia/Dubai","currency":"AED"}' class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none"></textarea>
                                                <label class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700">
                                                    <input wire:model="editingWorkspaceForm.is_default" type="checkbox" class="h-4 w-4 rounded border-zinc-300 text-sky-900 focus:ring-sky-900" />
                                                    Default workspace for this company
                                                </label>
                                                <div class="flex flex-wrap gap-2">
                                                    <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                                        Save workspace changes
                                                    </button>
                                                    <button wire:click="cancelEditingWorkspace" type="button" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if ($pendingWorkspaceDeleteId === $workspaceRow->id)
                                        <div class="mt-4 border-t border-rose-200 pt-4">
                                            <div class="grid gap-3 rounded-[1.25rem] border border-rose-200 bg-rose-50 p-4">
                                                <div>
                                                    <h3 class="text-sm font-semibold text-rose-900">Confirm workspace deletion</h3>
                                                    <p class="mt-1 text-sm leading-6 text-rose-800">Type <span class="font-semibold">{{ $workspaceRow->name }}</span> to confirm. This permanently removes the workspace and all linked records.</p>
                                                </div>
                                                <input wire:model="workspaceDeleteConfirmation" type="text" placeholder="Type the workspace name exactly" class="rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm outline-none" />
                                                @error('workspaceDeleteConfirmation')
                                                    <p class="text-sm text-rose-700">{{ $message }}</p>
                                                @enderror
                                                <label class="flex items-start gap-3 rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm text-rose-900">
                                                    <input wire:model="workspaceDeleteAcknowledged" type="checkbox" class="mt-1 h-4 w-4 rounded border-rose-300 text-rose-700 focus:ring-rose-700" />
                                                    <span>I understand this will remove everything linked to this workspace.</span>
                                                </label>
                                                @error('workspaceDeleteAcknowledged')
                                                    <p class="text-sm text-rose-700">{{ $message }}</p>
                                                @enderror
                                                <div class="flex flex-wrap gap-2">
                                                    <button wire:click="confirmWorkspaceDeletion" type="button" class="rounded-xl bg-rose-700 px-4 py-3 text-sm font-medium text-white transition hover:bg-rose-800">
                                                        Permanently delete workspace
                                                    </button>
                                                    <button wire:click="cancelWorkspaceDeletion" type="button" class="rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100">
                                                        Cancel deletion
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div class="rounded-[1.35rem] border border-dashed border-zinc-300 bg-zinc-50 px-4 py-10 text-center text-sm text-zinc-500">
                                    No workspaces matched the current filters.
                                </div>
                            @endforelse
                        </div>
                        <div class="mt-4">
                            {{ $workspaceRows->links() }}
                        </div>
                    </article>
                </div>

                <div class="space-y-6">
                    @if ($currentWorkspace)
                        <form wire:submit="updateWorkspace" class="grid gap-3 rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-semibold text-zinc-950">Manage selected workspace</h2>
                                    <p class="mt-1 text-sm text-zinc-500">
                                        Edit the selected workspace name, company, description, and JSON settings.
                                    </p>
                                </div>

                                <button
                                    wire:click="requestWorkspaceDeletion({{ $currentWorkspace->id }})"
                                    type="button"
                                    class="rounded-xl border border-rose-200 px-4 py-3 text-sm font-medium text-rose-700 transition hover:bg-rose-50"
                                >
                                    Delete Workspace
                                </button>
                            </div>

                            <select wire:model="editingWorkspaceForm.company_id" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                            <input wire:model="editingWorkspaceForm.name" type="text" placeholder="Workspace name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <input wire:model="editingWorkspaceForm.description" type="text" placeholder="Description" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                            <textarea wire:model="editingWorkspaceForm.settings_json" rows="7" placeholder='{"timezone":"Asia/Dubai","currency":"AED"}' class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none"></textarea>
                            <label class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700">
                                <input wire:model="editingWorkspaceForm.is_default" type="checkbox" class="h-4 w-4 rounded border-zinc-300 text-sky-900 focus:ring-sky-900" />
                                Default workspace for this company
                            </label>
                            @if ($pendingWorkspaceDeleteId === $currentWorkspace->id)
                                <div class="grid gap-3 rounded-[1.25rem] border border-rose-200 bg-rose-50 p-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-rose-900">Confirm workspace deletion</h3>
                                        <p class="mt-1 text-sm leading-6 text-rose-800">Type <span class="font-semibold">{{ $currentWorkspace->name }}</span> to confirm. This deletes the workspace and everything linked to it, including leads, opportunities, assignments, and other workspace records.</p>
                                    </div>
                                    <input wire:model="workspaceDeleteConfirmation" type="text" placeholder="Type the workspace name exactly" class="rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm outline-none" />
                                    @error('workspaceDeleteConfirmation')
                                        <p class="text-sm text-rose-700">{{ $message }}</p>
                                    @enderror
                                    <label class="flex items-start gap-3 rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm text-rose-900">
                                        <input wire:model="workspaceDeleteAcknowledged" type="checkbox" class="mt-1 h-4 w-4 rounded border-rose-300 text-rose-700 focus:ring-rose-700" />
                                        <span>I understand this will permanently remove all data linked to this workspace.</span>
                                    </label>
                                    @error('workspaceDeleteAcknowledged')
                                        <p class="text-sm text-rose-700">{{ $message }}</p>
                                    @enderror
                                    <div class="flex flex-wrap gap-2">
                                        <button wire:click="confirmWorkspaceDeletion" type="button" class="rounded-xl bg-rose-700 px-4 py-3 text-sm font-medium text-white transition hover:bg-rose-800">
                                            Permanently delete workspace
                                        </button>
                                        <button wire:click="cancelWorkspaceDeletion" type="button" class="rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100">
                                            Cancel deletion
                                        </button>
                                    </div>
                                </div>
                            @endif
                            <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                Save Workspace Settings
                            </button>
                        </form>
                    @endif

                    <form wire:submit="saveCompany" class="grid gap-3 rounded-[1.5rem] border border-zinc-200 p-4">
                        <h2 class="text-lg font-semibold text-zinc-950">Create company</h2>
                        <input wire:model="companyForm.name" type="text" placeholder="Company name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="companyForm.industry" type="text" placeholder="Industry" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="companyForm.contact_email" type="email" placeholder="Contact email" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="companyForm.contact_phone" type="text" placeholder="Contact phone" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="companyForm.timezone" type="text" placeholder="Timezone" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                            Create Company
                        </button>
                    </form>

                    <form wire:submit="saveWorkspace" class="grid gap-3 rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-4">
                        <h2 class="text-lg font-semibold text-zinc-950">Create workspace</h2>
                        <select wire:model="workspaceForm.company_id" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="">Select company</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                        <input wire:model="workspaceForm.name" type="text" placeholder="Workspace name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="workspaceForm.description" type="text" placeholder="Description" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <select wire:model="workspaceForm.template_key" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            @foreach ($workspaceTemplates as $templateKey => $template)
                                <option value="{{ $templateKey }}">{{ $template['name'] }}</option>
                            @endforeach
                        </select>
                        <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-500">
                            {{ data_get($workspaceTemplates, $workspaceForm['template_key'].'.description') }}
                        </div>
                        <button type="submit" class="rounded-xl bg-sky-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-sky-800">
                            Create Workspace
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </section>
</div>
