<div class="space-y-6">
    <section class="rounded-[1.75rem] border border-sky-100 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-sky-700">Workspace CRM</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">
                    {{ $currentWorkspace ? $currentWorkspace->name.' Dashboard' : 'Workspace Dashboard' }}
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-zinc-500">
                    The workspace stays spreadsheet-simple: switch tabs, scan tables, and update lead or opportunity status inline.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <select wire:model.live="workspaceId" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none">
                    @forelse ($workspaces as $workspace)
                        <option value="{{ $workspace->id }}">{{ $workspace->company->name }} / {{ $workspace->name }}</option>
                    @empty
                        <option value="">No workspace assigned</option>
                    @endforelse
                </select>

                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin') }}" class="rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                        Open Admin
                    </a>
                @endif
            </div>
        </div>
    </section>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if (! $currentWorkspace)
        <section class="rounded-[1.75rem] border border-emerald-200 bg-white p-6 shadow-sm">
            <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <div class="rounded-[1.5rem] bg-[linear-gradient(135deg,_#06281f,_#0f766e_58%,_#16a34a)] p-6 text-white">
                    <p class="text-xs uppercase tracking-[0.3em] text-emerald-100">First Run Setup</p>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight">Start a new workspace</h2>
                    <p class="mt-4 max-w-lg text-sm leading-7 text-emerald-50">
                        This account is not assigned to a workspace yet. Create your company and first workspace here, then add lead sources like Google Sheets or CSV imports.
                    </p>
                    <div class="mt-6 space-y-3 text-sm text-emerald-50">
                        <div class="rounded-2xl bg-white/10 px-4 py-3">1. Create your company profile</div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3">2. Name the first workspace</div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3">3. Add data sources and start tracking leads</div>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-5">
                    <form wire:submit="startWorkspace" class="grid gap-4 md:grid-cols-2">
                        <input wire:model="companyForm.name" type="text" placeholder="Company name" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none md:col-span-2" />
                        <input wire:model="workspaceForm.name" type="text" placeholder="Workspace name" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                        <input wire:model="workspaceForm.description" type="text" placeholder="Workspace description" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                        <input wire:model="companyForm.contact_email" type="email" placeholder="Company email" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                        <input wire:model="companyForm.contact_phone" type="text" placeholder="Company phone" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                        <input wire:model="companyForm.industry" type="text" placeholder="Industry" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                        <input wire:model="companyForm.timezone" type="text" placeholder="Timezone" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                        <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800 md:col-span-2">
                            Start a new workspace
                        </button>
                    </form>
                </div>
            </div>
        </section>
    @else
        @if ($sheetSources->isEmpty() && auth()->user()->hasRole(['admin', 'manager']))
            <section class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="max-w-xl">
                        <p class="text-xs uppercase tracking-[0.3em] text-emerald-700">Getting Started</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Add your first source</h2>
                        <p class="mt-2 text-sm leading-7 text-zinc-600">
                            Your workspace is ready. Connect a Google Sheet, paste a published sheet URL, or add another live source so leads start flowing into the CRM.
                        </p>
                    </div>

                    <form wire:submit="saveSheetSource" class="grid w-full gap-3 xl:max-w-3xl xl:grid-cols-2">
                        <select wire:model="sourceForm.type" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none">
                            <option value="{{ \App\Models\SheetSource::TYPE_LEADS }}">Leads</option>
                            <option value="{{ \App\Models\SheetSource::TYPE_OPPORTUNITIES }}">Opportunities</option>
                            <option value="{{ \App\Models\SheetSource::TYPE_REPORTS }}">Reports</option>
                            <option value="{{ \App\Models\SheetSource::TYPE_GOOGLE_ADS }}">Google Ads</option>
                        </select>
                        <select wire:model="sourceForm.source_kind" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none">
                            @foreach (\App\Models\SheetSource::SOURCE_KINDS as $sourceKind)
                                <option value="{{ $sourceKind }}">{{ str($sourceKind)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                        <input wire:model="sourceForm.name" type="text" placeholder="Source name" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                        <input wire:model="sourceForm.url" type="url" placeholder="Google Sheet or source URL" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                        <input wire:model="sourceForm.description" type="text" placeholder="Short description" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none xl:col-span-2" />
                        <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-medium text-white transition hover:bg-emerald-800 xl:col-span-2">
                            Add first source
                        </button>
                    </form>
                </div>
            </section>
        @endif

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($kpis as $kpi)
                <article class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $kpi['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold text-zinc-950">{{ $kpi['value'] }}</p>
                    <p class="mt-2 text-sm text-zinc-500">{{ $kpi['detail'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-zinc-50 px-4">
                <div class="flex flex-wrap gap-1 py-2">
                    @php
                        $tabs = [
                            'leads' => 'Leads',
                            'opportunities' => 'Opportunities',
                            'contacts' => 'Contacts',
                            'customers' => 'Customers',
                            'sources' => 'Sources',
                            'analytics' => 'Analytics',
                            'manual-lead' => 'Add Lead',
                            'manual-opportunity' => 'Add Opportunity',
                        ];

                        if ($canManageAccess) {
                            $tabs['access'] = 'Access';
                        }
                    @endphp
                    @foreach ($tabs as $tabKey => $label)
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

            @if ($activeTab === 'leads')
                <div class="space-y-4 p-4">
                    <div class="grid gap-3 lg:grid-cols-6">
                        <input
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="Search lead, company, email"
                            class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none"
                        />
                        <select wire:model.live="leadStatusFilter" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="">All statuses</option>
                            @foreach (\App\Models\Lead::STATUSES as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="leadSourceFilter" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="">All sources</option>
                            @foreach ($leadSources as $sourceName)
                                <option value="{{ $sourceName }}">{{ $sourceName }}</option>
                            @endforeach
                        </select>
                        <div class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-zinc-500">
                            {{ $currentWorkspace->name }}
                        </div>
                        <select wire:model.live="leadSort" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="newest">Newest date</option>
                            <option value="oldest">Oldest date</option>
                            <option value="company_asc">Company A-Z</option>
                            <option value="company_desc">Company Z-A</option>
                            <option value="value_desc">Highest value</option>
                            <option value="value_asc">Lowest value</option>
                        </select>
                        <select wire:model.live="leadPerPage" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="10">10 rows</option>
                            <option value="15">15 rows</option>
                            <option value="25">25 rows</option>
                            <option value="50">50 rows</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto rounded-[1.5rem] border border-zinc-200">
                        <table class="min-w-full border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr class="bg-zinc-50 text-left text-zinc-500">
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Lead ID</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Company</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Contact</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Source</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Service</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Status</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Lead Score</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($leads as $lead)
                                    @php
                                        $leadScore = $this->leadScore($lead);
                                    @endphp
                                    <tr
                                        wire:click="selectLead({{ $lead->id }})"
                                        class="cursor-pointer transition odd:bg-white even:bg-zinc-50/60 hover:bg-sky-50/80 {{ $selectedLead?->id === $lead->id ? 'bg-sky-50 ring-1 ring-inset ring-sky-200' : '' }}"
                                    >
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $lead->lead_id ?: $lead->external_key }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 font-medium text-zinc-900">{{ $lead->company_name ?: 'Unknown company' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                            <div>{{ $lead->contact_name ?: 'No contact' }}</div>
                                            <div class="text-xs text-zinc-400">{{ $lead->email ?: 'No email' }}</div>
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $lead->lead_source ?: 'Unknown' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $lead->service ?: 'Not set' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3">
                                            <select
                                                wire:click.stop
                                                wire:change="updateLeadStatus({{ $lead->id }}, $event.target.value)"
                                                class="w-full rounded-lg px-3 py-2 text-sm font-medium outline-none {{ $this->leadStatusClasses($lead->status) }}"
                                            >
                                                @foreach (\App\Models\Lead::STATUSES as $status)
                                                    <option value="{{ $status }}" @selected($lead->status === $status)>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3">
                                            <div class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $this->leadScoreClasses($leadScore['score']) }}">
                                                {{ $leadScore['score'] }}/100
                                            </div>
                                            <div class="mt-1 text-xs text-zinc-400">{{ $leadScore['label'] }}</div>
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                            {{ $lead->submission_date?->format('d M Y') ?: '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-10 text-center text-zinc-500">No leads match the current filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $leads->links() }}
                    </div>
                </div>

                @if ($selectedLead)
                    <div
                        wire:click.self="closeLeadDetails"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/55 px-4 py-8 backdrop-blur-sm"
                    >
                        <div class="max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-[1.75rem] border border-sky-200 bg-white shadow-2xl">
                            <div class="flex items-start justify-between gap-4 border-b border-zinc-200 bg-sky-50/70 px-6 py-5">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.3em] text-sky-700">Lead Details</p>
                                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $selectedLead->company_name ?: 'Unknown company' }}</h2>
                                    <p class="mt-1 text-sm text-zinc-500">{{ $selectedLead->contact_name ?: 'No contact name' }}</p>
                                </div>
                                <button
                                    wire:click="closeLeadDetails"
                                    type="button"
                                    class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50"
                                >
                                    Close
                                </button>
                            </div>

                            <div class="max-h-[calc(90vh-92px)] overflow-y-auto px-6 py-6">
                                <div class="space-y-5">
                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Lead ID</div>
                                            <div class="mt-2 text-base font-semibold text-zinc-950">{{ $selectedLead->lead_id ?: $selectedLead->external_key }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Status</div>
                                            <div class="mt-2">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $this->leadStatusClasses($selectedLead->status) }}">
                                                    {{ $selectedLead->status }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Value</div>
                                            <div class="mt-2 text-base font-semibold text-zinc-950">
                                                {{ $selectedLead->lead_value ? 'AED '.number_format((float) $selectedLead->lead_value, 0) : 'Not set' }}
                                            </div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Linked opportunities</div>
                                            <div class="mt-2 text-base font-semibold text-zinc-950">{{ number_format((int) $selectedLead->opportunities_count) }}</div>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 lg:grid-cols-[0.9fr_1.1fr]">
                                        <div class="rounded-[1.25rem] border border-sky-200 bg-sky-50/70 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-sky-700">Lead Score</div>
                                            <div class="mt-2 flex items-center gap-3">
                                                <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $this->leadScoreClasses((int) ($leadInsights['lead_score'] ?? 0)) }}">
                                                    {{ $leadInsights['lead_score'] ?? 0 }}/100
                                                </span>
                                                <span class="text-sm font-medium text-zinc-700">{{ $leadInsights['lead_score_label'] ?? 'Cold' }}</span>
                                            </div>
                                            <p class="mt-3 text-sm leading-7 text-zinc-600">{{ $leadInsights['lead_score_summary'] ?? 'No score summary available yet.' }}</p>
                                        </div>

                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-sm font-semibold text-zinc-950">Why this lead scored this way</div>
                                            <div class="mt-3 space-y-2">
                                                @forelse (($leadInsights['lead_score_reasons'] ?? []) as $reason)
                                                    <div class="rounded-xl bg-zinc-50 px-3 py-3 text-sm text-zinc-700">{{ $reason }}</div>
                                                @empty
                                                    <div class="text-sm text-zinc-500">No lead score reasons available.</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-zinc-950">{{ $leadInsights['headline'] ?? 'Lead overview' }}</div>
                                        <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $leadInsights['summary'] ?? 'Select a lead to view details.' }}</p>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Email</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedLead->email ?: 'Not provided' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Phone</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedLead->phone ?: 'Not provided' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Service</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedLead->service ?: 'Not set' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Source</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedLead->lead_source ?: 'Unknown' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Created</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedLead->submission_date?->format('d M Y H:i') ?: 'Unknown' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Owner</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedLead->assignedUser?->name ?: 'Unassigned' }}</div>
                                        </div>
                                    </div>

                                    <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-zinc-950">Notes</div>
                                        <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $selectedLead->notes ?: 'No notes saved for this lead yet.' }}</p>
                                    </div>

                                    @if ($selectedLead->disqualification_reason)
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-sm font-semibold text-zinc-950">Disqualification reason</div>
                                            <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $selectedLead->disqualification_reason }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            @if ($activeTab === 'opportunities')
                <div class="space-y-4 p-4">
                    <div class="grid gap-3 lg:grid-cols-5">
                        <input
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="Search opportunity or company"
                            class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none"
                        />
                        <select wire:model.live="opportunityStageFilter" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="">All stages</option>
                            @foreach (\App\Models\Opportunity::STAGES as $stage)
                                <option value="{{ $stage }}">{{ $stage }}</option>
                            @endforeach
                        </select>
                        <div class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-zinc-500">
                            {{ $currentWorkspace->company->name }}
                        </div>
                        <select wire:model.live="opportunitySort" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="newest">Newest date</option>
                            <option value="oldest">Oldest date</option>
                            <option value="company_asc">Company A-Z</option>
                            <option value="company_desc">Company Z-A</option>
                            <option value="revenue_desc">Highest revenue</option>
                            <option value="revenue_asc">Lowest revenue</option>
                        </select>
                        <select wire:model.live="opportunityPerPage" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="10">10 rows</option>
                            <option value="15">15 rows</option>
                            <option value="25">25 rows</option>
                            <option value="50">50 rows</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto rounded-[1.5rem] border border-zinc-200">
                        <table class="min-w-full border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr class="bg-zinc-50 text-left text-zinc-500">
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Company</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Service</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Source</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Revenue</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Timeline</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Stage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($opportunities as $opportunity)
                                    <tr
                                        wire:click="selectOpportunity({{ $opportunity->id }})"
                                        class="cursor-pointer transition odd:bg-white even:bg-zinc-50/60 hover:bg-sky-50/80 {{ $selectedOpportunity?->id === $opportunity->id ? 'bg-sky-50 ring-1 ring-inset ring-sky-200' : '' }}"
                                    >
                                        <td class="border-b border-zinc-100 px-4 py-3 font-medium text-zinc-900">{{ $opportunity->company_name ?: 'Unknown company' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $opportunity->required_service ?: 'Not set' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $opportunity->lead_source ?: 'Unknown' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                            {{ $opportunity->revenue_potential ? 'AED '.number_format((float) $opportunity->revenue_potential, 0) : '-' }}
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                            {{ $opportunity->project_timeline_days ? $opportunity->project_timeline_days.' days' : '-' }}
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3">
                                            <select
                                                wire:click.stop
                                                wire:change="updateOpportunityStage({{ $opportunity->id }}, $event.target.value)"
                                                class="w-full rounded-lg px-3 py-2 text-sm font-medium outline-none {{ $this->opportunityStageClasses($opportunity->sales_stage) }}"
                                            >
                                                @foreach (\App\Models\Opportunity::STAGES as $stage)
                                                    <option value="{{ $stage }}" @selected($opportunity->sales_stage === $stage)>{{ $stage }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-10 text-center text-zinc-500">No opportunities match the current filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $opportunities->links() }}
                    </div>
                </div>

                @if ($selectedOpportunity)
                    <div
                        wire:click.self="closeOpportunityDetails"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/55 px-4 py-8 backdrop-blur-sm"
                    >
                        <div class="max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-[1.75rem] border border-sky-200 bg-white shadow-2xl">
                            <div class="flex items-start justify-between gap-4 border-b border-zinc-200 bg-sky-50/70 px-6 py-5">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.3em] text-sky-700">Opportunity Details</p>
                                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $selectedOpportunity->company_name ?: 'Unknown company' }}</h2>
                                    <p class="mt-1 text-sm text-zinc-500">{{ $selectedOpportunity->contact_email ?: 'No contact email' }}</p>
                                </div>
                                <button
                                    wire:click="closeOpportunityDetails"
                                    type="button"
                                    class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50"
                                >
                                    Close
                                </button>
                            </div>

                            <div class="max-h-[calc(90vh-92px)] overflow-y-auto px-6 py-6">
                                <div class="space-y-5">
                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Stage</div>
                                            <div class="mt-2">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $this->opportunityStageClasses($selectedOpportunity->sales_stage) }}">
                                                    {{ $selectedOpportunity->sales_stage }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Revenue</div>
                                            <div class="mt-2 text-base font-semibold text-zinc-950">
                                                {{ $selectedOpportunity->revenue_potential ? 'AED '.number_format((float) $selectedOpportunity->revenue_potential, 0) : 'Not set' }}
                                            </div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Timeline</div>
                                            <div class="mt-2 text-base font-semibold text-zinc-950">
                                                {{ $selectedOpportunity->project_timeline_days ? $selectedOpportunity->project_timeline_days.' days' : 'Not set' }}
                                            </div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Linked lead</div>
                                            <div class="mt-2 text-base font-semibold text-zinc-950">{{ $selectedOpportunity->lead?->lead_id ?: 'No linked lead' }}</div>
                                        </div>
                                    </div>

                                    <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-zinc-950">{{ $opportunityInsights['headline'] ?? 'Opportunity overview' }}</div>
                                        <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $opportunityInsights['summary'] ?? 'Edit the deal details and keep the pipeline current.' }}</p>
                                    </div>

                                    <form wire:submit="saveOpportunityDetails" class="grid gap-3 md:grid-cols-2">
                                        <input wire:model="opportunityEditForm.company_name" type="text" placeholder="Company name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                        <input wire:model="opportunityEditForm.contact_email" type="email" placeholder="Contact email" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                        <input wire:model="opportunityEditForm.lead_source" type="text" placeholder="Lead source" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                        <input wire:model="opportunityEditForm.required_service" type="text" placeholder="Required service" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                        <input wire:model="opportunityEditForm.revenue_potential" type="number" step="0.01" placeholder="Revenue potential (AED)" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                        <input wire:model="opportunityEditForm.project_timeline_days" type="number" placeholder="Timeline (days)" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                        <select wire:model="opportunityEditForm.sales_stage" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none md:col-span-2">
                                            @foreach (\App\Models\Opportunity::STAGES as $stage)
                                                <option value="{{ $stage }}">{{ $stage }}</option>
                                            @endforeach
                                        </select>
                                        <textarea wire:model="opportunityEditForm.notes" rows="4" placeholder="Notes" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none md:col-span-2"></textarea>
                                        <div class="flex flex-wrap gap-2 md:col-span-2">
                                            <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                                Save Opportunity
                                            </button>
                                            <button wire:click="closeOpportunityDetails" type="button" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            @if ($activeTab === 'contacts')
                <div class="space-y-4 p-4">
                    <div class="grid gap-3 lg:grid-cols-4">
                        <input
                            wire:model.live.debounce.300ms="contactSearch"
                            type="text"
                            placeholder="Search contact, company, email"
                            class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none"
                        />
                        <select wire:model.live="contactSort" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="newest">Newest date</option>
                            <option value="oldest">Oldest date</option>
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                            <option value="company_asc">Company A-Z</option>
                            <option value="company_desc">Company Z-A</option>
                        </select>
                        <select wire:model.live="contactPerPage" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="10">10 rows</option>
                            <option value="12">12 rows</option>
                            <option value="25">25 rows</option>
                            <option value="50">50 rows</option>
                        </select>
                        <div class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-zinc-500">
                            {{ $currentWorkspace->name }}
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-[1.5rem] border border-zinc-200">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-zinc-50 text-left text-zinc-500">
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Contact</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Company</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Status</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Source</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($contacts as $contact)
                                    <tr
                                        wire:click="selectContact({{ $contact->id }})"
                                        class="cursor-pointer transition odd:bg-white even:bg-zinc-50/60 hover:bg-emerald-50/80 {{ $selectedContact?->id === $contact->id ? 'bg-emerald-50 ring-1 ring-inset ring-emerald-200' : '' }}"
                                    >
                                        <td class="border-b border-zinc-100 px-4 py-3">
                                            <div class="font-medium text-zinc-900">{{ $contact->contact_name ?: 'Unnamed contact' }}</div>
                                            <div class="text-xs text-zinc-400">{{ $contact->email ?: ($contact->phone ?: 'No direct contact info') }}</div>
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $contact->company_name ?: 'Unknown company' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $this->leadStatusClasses($contact->status) }}">
                                                {{ $contact->status }}
                                            </span>
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $contact->lead_source ?: 'Unknown' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $contact->submission_date?->format('d M Y') ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-center text-zinc-500">No contacts are available for this workspace yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $contacts->links() }}
                    </div>
                </div>

                @if ($selectedContact)
                    <div
                        wire:click.self="closeContactDetails"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/55 px-4 py-8 backdrop-blur-sm"
                    >
                        <div class="max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-[1.75rem] border border-emerald-200 bg-white shadow-2xl">
                            <div class="flex items-start justify-between gap-4 border-b border-zinc-200 bg-emerald-50/70 px-6 py-5">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.3em] text-emerald-700">AI Contact Brief</p>
                                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $selectedContact->contact_name ?: 'Unnamed contact' }}</h2>
                                    <p class="mt-1 text-sm text-zinc-500">{{ $selectedContact->company_name ?: 'Unknown company' }}</p>
                                </div>
                                <button
                                    wire:click="closeContactDetails"
                                    type="button"
                                    class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50"
                                >
                                    Close
                                </button>
                            </div>

                            <div class="max-h-[calc(90vh-92px)] overflow-y-auto px-6 py-6">
                                <div class="space-y-5">
                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Readiness</div>
                                            <div class="mt-2 text-lg font-semibold text-zinc-950">{{ $contactInsights['readiness'] ?? 'Pending' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Data Coverage</div>
                                            <div class="mt-2 text-lg font-semibold text-zinc-950">{{ $contactInsights['coverage'] ?? '0/6 core fields present' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Email</div>
                                            <div class="mt-2 text-sm font-semibold text-zinc-950">{{ $selectedContact->email ?: 'Not provided' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Phone</div>
                                            <div class="mt-2 text-sm font-semibold text-zinc-950">{{ $selectedContact->phone ?: 'Not provided' }}</div>
                                        </div>
                                    </div>

                                    <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-zinc-950">{{ $contactInsights['headline'] ?? 'No insight yet' }}</div>
                                        <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $contactInsights['summary'] ?? 'Select a contact to view enrichment.' }}</p>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Company</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedContact->company_name ?: 'Unknown company' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Source</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedContact->lead_source ?: 'Unknown' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Service</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedContact->service ?: 'Not set' }}</div>
                                        </div>
                                    </div>

                                    <div class="rounded-[1.25rem] bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-zinc-950">Signals</div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @forelse (($contactInsights['signals'] ?? []) as $signal)
                                                <span class="rounded-full bg-zinc-100 px-3 py-2 text-xs font-medium text-zinc-700">{{ $signal }}</span>
                                            @empty
                                                <span class="text-sm text-zinc-500">No contact signals yet.</span>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="rounded-[1.25rem] bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-zinc-950">Recommended next steps</div>
                                        <div class="mt-3 space-y-2">
                                            @forelse (($contactInsights['recommendations'] ?? []) as $recommendation)
                                                <div class="rounded-xl bg-emerald-50 px-3 py-3 text-sm text-emerald-900">{{ $recommendation }}</div>
                                            @empty
                                                <div class="text-sm text-zinc-500">No recommendations available.</div>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="rounded-[1.25rem] bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-zinc-950">Missing data</div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @forelse (($contactInsights['missing_fields'] ?? []) as $missingField)
                                                <span class="rounded-full bg-amber-100 px-3 py-2 text-xs font-medium text-amber-800">{{ $missingField }}</span>
                                            @empty
                                                <span class="text-sm text-zinc-500">The contact record is well filled out.</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            @if ($activeTab === 'customers')
                <div class="space-y-4 p-4">
                    <div class="grid gap-3 lg:grid-cols-4">
                        <input
                            wire:model.live.debounce.300ms="customerSearch"
                            type="text"
                            placeholder="Search customer company, email, service"
                            class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none"
                        />
                        <select wire:model.live="customerSort" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="newest">Newest opportunity</option>
                            <option value="oldest">Oldest opportunity</option>
                            <option value="company_asc">Company A-Z</option>
                            <option value="company_desc">Company Z-A</option>
                            <option value="value_desc">Highest value</option>
                            <option value="value_asc">Lowest value</option>
                        </select>
                        <select wire:model.live="customerPerPage" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="10">10 rows</option>
                            <option value="12">12 rows</option>
                            <option value="25">25 rows</option>
                            <option value="50">50 rows</option>
                        </select>
                        <div class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-zinc-500">
                            Customers created from opportunities
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-[1.5rem] border border-zinc-200">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-zinc-50 text-left text-zinc-500">
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Customer</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Service</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Value</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Source</th>
                                    <th class="border-b border-zinc-200 px-4 py-3 font-medium">Opportunity Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customers as $customer)
                                    <tr
                                        wire:click="selectCustomer({{ $customer->id }})"
                                        class="cursor-pointer transition odd:bg-white even:bg-zinc-50/60 hover:bg-sky-50/80 {{ $selectedCustomer?->id === $customer->id ? 'bg-sky-50 ring-1 ring-inset ring-sky-200' : '' }}"
                                    >
                                        <td class="border-b border-zinc-100 px-4 py-3">
                                            <div class="font-medium text-zinc-900">{{ $customer->company_name ?: 'Unknown customer' }}</div>
                                            <div class="text-xs text-zinc-400">{{ $customer->contact_email ?: 'No customer email' }}</div>
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $customer->required_service ?: 'Not set' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                            {{ $customer->revenue_potential ? 'AED '.number_format((float) $customer->revenue_potential, 0) : '-' }}
                                        </td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $customer->lead_source ?: 'Unknown' }}</td>
                                        <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ $customer->submission_date?->format('d M Y') ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-center text-zinc-500">No opportunity customers are available yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $customers->links() }}
                    </div>
                </div>

                @if ($selectedCustomer)
                    <div
                        wire:click.self="closeCustomerDetails"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/55 px-4 py-8 backdrop-blur-sm"
                    >
                        <div class="flex h-[min(88vh,760px)] w-full max-w-4xl flex-col overflow-hidden rounded-[1.75rem] border border-sky-200 bg-white shadow-2xl">
                            <div class="flex items-start justify-between gap-4 border-b border-zinc-200 bg-sky-50/70 px-6 py-5">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.3em] text-sky-700">AI Customer Brief</p>
                                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $selectedCustomer->company_name ?: 'Unknown customer' }}</h2>
                                    <p class="mt-1 text-sm text-zinc-500">{{ $selectedCustomer->contact_email ?: 'No customer email' }}</p>
                                </div>
                                <button
                                    wire:click="closeCustomerDetails"
                                    type="button"
                                    class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50"
                                >
                                    Close
                                </button>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
                                <div class="space-y-5">
                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Account Tier</div>
                                            <div class="mt-2 text-lg font-semibold text-zinc-950">{{ $customerInsights['tier'] ?? 'Standard account' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Converted Value</div>
                                            <div class="mt-2 text-lg font-semibold text-zinc-950">
                                                {{ $selectedCustomer->revenue_potential ? 'AED '.number_format((float) $selectedCustomer->revenue_potential, 0) : 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Service</div>
                                            <div class="mt-2 text-sm font-semibold text-zinc-950">{{ $selectedCustomer->required_service ?: 'Not set' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] bg-zinc-50 px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Opportunity Date</div>
                                            <div class="mt-2 text-sm font-semibold text-zinc-950">{{ $selectedCustomer->submission_date?->format('d M Y') ?: 'Unknown' }}</div>
                                        </div>
                                    </div>

                                    <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-zinc-950">{{ $customerInsights['headline'] ?? 'No insight yet' }}</div>
                                        <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $customerInsights['summary'] ?? 'Select a customer to view enrichment.' }}</p>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Source</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedCustomer->lead_source ?: 'Unknown' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Assigned Owner</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedCustomer->assignedUser?->name ?: 'Unassigned' }}</div>
                                        </div>
                                        <div class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Lead Link</div>
                                            <div class="mt-2 text-sm font-medium text-zinc-900">{{ $selectedCustomer->lead?->lead_id ?: 'No linked lead' }}</div>
                                        </div>
                                    </div>

                                    <div class="rounded-[1.25rem] bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-zinc-950">Customer signals</div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @forelse (($customerInsights['signals'] ?? []) as $signal)
                                                <span class="rounded-full bg-zinc-100 px-3 py-2 text-xs font-medium text-zinc-700">{{ $signal }}</span>
                                            @empty
                                                <span class="text-sm text-zinc-500">No customer signals yet.</span>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="rounded-[1.25rem] bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-zinc-950">Recommended account actions</div>
                                        <div class="mt-3 space-y-2">
                                            @forelse (($customerInsights['recommendations'] ?? []) as $recommendation)
                                                <div class="rounded-xl bg-sky-50 px-3 py-3 text-sm text-sky-900">{{ $recommendation }}</div>
                                            @empty
                                                <div class="text-sm text-zinc-500">No recommendations available.</div>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="rounded-[1.25rem] bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-zinc-950">Missing post-win data</div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @forelse (($customerInsights['missing_fields'] ?? []) as $missingField)
                                                <span class="rounded-full bg-amber-100 px-3 py-2 text-xs font-medium text-amber-800">{{ $missingField }}</span>
                                            @empty
                                                <span class="text-sm text-zinc-500">The customer record has the core post-win details.</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            @if ($activeTab === 'sources')
                <div class="space-y-6 p-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-950">Integrations and sources</h2>
                            <p class="mt-1 text-sm text-zinc-500">
                                Check which sources are connected to this workspace, when they last synced, and whether they are active.
                            </p>
                            <p class="mt-2 text-xs text-zinc-400">
                                Google Sheets API sources require an admin to finish Google OAuth setup before users can sync them.
                            </p>
                        </div>

                        @if (auth()->user()->hasRole(['admin', 'manager']))
                            <div class="flex flex-wrap gap-3">
                                <button
                                    wire:click="syncWorkspaceSources"
                                    wire:loading.attr="disabled"
                                    wire:target="syncWorkspaceSources"
                                    type="button"
                                    class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <span wire:loading.remove wire:target="syncWorkspaceSources">Sync all active sources</span>
                                    <span wire:loading wire:target="syncWorkspaceSources">Syncing sources...</span>
                                </button>
                                <a href="{{ route('admin', ['tab' => 'sources']) }}" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                    Open Admin Sources
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-[1.4rem] border border-zinc-200 bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">Connected sources</p>
                            <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ $sheetSources->count() }}</p>
                            <p class="mt-2 text-sm text-zinc-500">Total integrations on this workspace</p>
                        </article>
                        <article class="rounded-[1.4rem] border border-zinc-200 bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">Active</p>
                            <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ $sheetSources->where('is_active', true)->count() }}</p>
                            <p class="mt-2 text-sm text-zinc-500">Currently enabled sources</p>
                        </article>
                        <article class="rounded-[1.4rem] border border-zinc-200 bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">Healthy</p>
                            <p class="mt-3 text-3xl font-semibold text-sky-700">{{ $sheetSources->where('sync_status', 'synced')->count() }}</p>
                            <p class="mt-2 text-sm text-zinc-500">Sources with successful sync status</p>
                        </article>
                        <article class="rounded-[1.4rem] border border-zinc-200 bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">Needs attention</p>
                            <p class="mt-3 text-3xl font-semibold text-rose-700">{{ $sheetSources->where('sync_status', 'failed')->count() }}</p>
                            <p class="mt-2 text-sm text-zinc-500">Sources reporting sync failures</p>
                        </article>
                    </div>

                    @if (auth()->user()->hasRole(['admin', 'manager']))
                        <section class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50/50 p-5">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="max-w-xl">
                                    <h3 class="text-base font-semibold text-zinc-950">Add a source from the dashboard</h3>
                                    <p class="mt-1 text-sm text-zinc-500">
                                        Managers can add a simple source here, or use the admin page for Google authentication and advanced source setup.
                                    </p>
                                </div>

                                <form wire:submit="saveSheetSource" class="grid w-full gap-3 xl:max-w-3xl xl:grid-cols-2">
                                    <select wire:model="sourceForm.type" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none">
                                        <option value="{{ \App\Models\SheetSource::TYPE_LEADS }}">Leads</option>
                                        <option value="{{ \App\Models\SheetSource::TYPE_OPPORTUNITIES }}">Opportunities</option>
                                        <option value="{{ \App\Models\SheetSource::TYPE_REPORTS }}">Reports</option>
                                        <option value="{{ \App\Models\SheetSource::TYPE_GOOGLE_ADS }}">Google Ads</option>
                                    </select>
                                    <select wire:model="sourceForm.source_kind" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none">
                                        @foreach (\App\Models\SheetSource::SOURCE_KINDS as $sourceKind)
                                            <option value="{{ $sourceKind }}">{{ str($sourceKind)->replace('_', ' ')->title() }}</option>
                                        @endforeach
                                    </select>
                                    <input wire:model="sourceForm.name" type="text" placeholder="Source name" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                                    <input wire:model="sourceForm.url" type="url" placeholder="Google Sheet or source URL" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none" />
                                    <input wire:model="sourceForm.description" type="text" placeholder="Short description" class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none xl:col-span-2" />
                                    <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-medium text-white transition hover:bg-emerald-800 xl:col-span-2">
                                        Save source
                                    </button>
                                </form>
                            </div>
                        </section>
                    @endif

                    <section class="overflow-hidden rounded-[1.5rem] border border-zinc-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-zinc-50 text-left text-zinc-500">
                                        <th class="border-b border-zinc-200 px-4 py-3 font-medium">Source</th>
                                        <th class="border-b border-zinc-200 px-4 py-3 font-medium">Table</th>
                                        <th class="border-b border-zinc-200 px-4 py-3 font-medium">Connection</th>
                                        <th class="border-b border-zinc-200 px-4 py-3 font-medium">Status</th>
                                        <th class="border-b border-zinc-200 px-4 py-3 font-medium">Last sync</th>
                                        <th class="border-b border-zinc-200 px-4 py-3 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($sheetSources->isNotEmpty())
                                        @foreach ($sheetSources as $sheetSource)
                                            <tr class="odd:bg-white even:bg-zinc-50/60">
                                                <td class="border-b border-zinc-100 px-4 py-3">
                                                    <div class="font-medium text-zinc-900">{{ $sheetSource->name }}</div>
                                                    <div class="mt-1 text-xs text-zinc-500">{{ $sheetSource->description ?: 'No description' }}</div>
                                                </td>
                                                <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">{{ str($sheetSource->type)->replace('_', ' ')->title() }}</td>
                                                <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                                    <div>{{ str($sheetSource->source_kind)->replace('_', ' ')->title() }}</div>
                                                    <div class="mt-1 text-xs text-zinc-400">{{ $sheetSource->is_active ? 'Active' : 'Inactive' }}</div>
                                                </td>
                                                <td class="border-b border-zinc-100 px-4 py-3">
                                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $this->sourceStatusClasses($sheetSource->sync_status) }}">
                                                        {{ str($sheetSource->sync_status ?: 'idle')->replace('_', ' ')->title() }}
                                                    </span>
                                                    @if ($sheetSource->last_error)
                                                        <div class="mt-2 max-w-xs text-xs text-rose-600">{{ $sheetSource->last_error }}</div>
                                                    @endif
                                                </td>
                                                <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                                    {{ $sheetSource->last_synced_at?->diffForHumans() ?: 'Never synced' }}
                                                </td>
                                                <td class="border-b border-zinc-100 px-4 py-3">
                                                    <div class="flex flex-wrap gap-2">
                                                        @if (auth()->user()->hasRole(['admin', 'manager']))
                                                            <button wire:click="startEditingSource({{ $sheetSource->id }})" type="button" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:bg-white">
                                                                Edit
                                                            </button>
                                                            <button
                                                                wire:click="syncSource({{ $sheetSource->id }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="syncSource({{ $sheetSource->id }})"
                                                                type="button"
                                                                class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-70"
                                                            >
                                                                <span wire:loading.remove wire:target="syncSource({{ $sheetSource->id }})">Sync</span>
                                                                <span wire:loading wire:target="syncSource({{ $sheetSource->id }})">Syncing...</span>
                                                            </button>
                                                        @endif
                                                        <a href="{{ $sheetSource->url }}" target="_blank" rel="noreferrer" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:bg-white">
                                                            Open source
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            @if ($editingSourceId === $sheetSource->id)
                                                <tr wire:key="source-editor-{{ $sheetSource->id }}">
                                                    <td colspan="6" class="border-b border-zinc-100 bg-zinc-50 px-4 py-4">
                                                        <form wire:submit="updateSheetSource" class="grid gap-3 rounded-[1.25rem] border border-zinc-200 bg-white p-4 md:grid-cols-2">
                                                            <select wire:model="editingSourceForm.type" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                                                @foreach (\App\Models\SheetSource::TYPES as $type)
                                                                    <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                                                                @endforeach
                                                            </select>
                                                            <select wire:model="editingSourceForm.source_kind" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                                                @foreach (\App\Models\SheetSource::SOURCE_KINDS as $sourceKind)
                                                                    <option value="{{ $sourceKind }}">{{ ucfirst(str_replace('_', ' ', $sourceKind)) }}</option>
                                                                @endforeach
                                                            </select>
                                                            <input wire:model="editingSourceForm.name" type="text" placeholder="Source name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                                            <input wire:model="editingSourceForm.url" type="text" placeholder="Source URL" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                                            <input wire:model="editingSourceForm.description" type="text" placeholder="Description" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none md:col-span-2" />
                                                            <label class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 md:col-span-2">
                                                                <input wire:model="editingSourceForm.is_active" type="checkbox" class="h-4 w-4 rounded border-zinc-300 text-sky-900 focus:ring-sky-900" />
                                                                Source is active
                                                            </label>
                                                            <div class="flex flex-wrap gap-2 md:col-span-2">
                                                                <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                                                    Save Source
                                                                </button>
                                                                <button wire:click="cancelEditingSource" type="button" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                                                    Cancel
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="6" class="px-4 py-10 text-center text-zinc-500">No sources are connected to this workspace yet.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            @endif

            @if ($activeTab === 'access' && $canManageAccess)
                <div class="space-y-6 p-4">
                    <div class="flex flex-col gap-2">
                        <h2 class="text-lg font-semibold text-zinc-950">Workspace access</h2>
                        <p class="text-sm text-zinc-500">
                            Add users to {{ $currentWorkspace->name }}, define roles, and assign permissions from one place.
                        </p>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-3">
                        <section class="rounded-[1.5rem] border border-zinc-200 bg-white p-5">
                            <h3 class="text-base font-semibold text-zinc-950">Add workspace user</h3>
                            <p class="mt-1 text-sm text-zinc-500">New users are added directly to this workspace.</p>
                            <form wire:submit="createUser" class="mt-4 grid gap-3">
                                <input wire:model="userForm.name" type="text" placeholder="Full name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <input wire:model="userForm.email" type="email" placeholder="Email" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <input wire:model="userForm.password" type="text" placeholder="Temporary password" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <input wire:model="userForm.job_title" type="text" placeholder="Job title" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <select wire:model="userForm.role" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->slug }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <select wire:model="userForm.permission_ids" multiple class="min-h-[150px] rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                    @forelse ($permissions as $permission)
                                        <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                                    @empty
                                        <option value="">Create permissions below first</option>
                                    @endforelse
                                </select>
                                <div class="text-xs text-zinc-400">Optional direct permissions in addition to the selected role.</div>
                                <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                    Add User To Workspace
                                </button>
                            </form>
                        </section>

                        <section class="rounded-[1.5rem] border border-zinc-200 bg-white p-5">
                            <h3 class="text-base font-semibold text-zinc-950">Create role</h3>
                            <p class="mt-1 text-sm text-zinc-500">Define a reusable role and attach permissions to it.</p>
                            <form wire:submit="createRole" class="mt-4 grid gap-3">
                                <input wire:model="roleForm.name" type="text" placeholder="Role name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <input wire:model="roleForm.slug" type="text" placeholder="role-slug" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <input wire:model="roleForm.description" type="text" placeholder="Description" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <input wire:model="roleForm.level" type="number" min="1" max="99" placeholder="Level" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <select wire:model="roleForm.permission_ids" multiple class="min-h-[150px] rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                    @forelse ($permissions as $permission)
                                        <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                                    @empty
                                        <option value="">Create permissions below first</option>
                                    @endforelse
                                </select>
                                <button type="submit" class="rounded-xl bg-sky-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-sky-800">
                                    Create Role
                                </button>
                            </form>
                        </section>

                        <section class="rounded-[1.5rem] border border-zinc-200 bg-white p-5">
                            <h3 class="text-base font-semibold text-zinc-950">Create permission</h3>
                            <p class="mt-1 text-sm text-zinc-500">Permissions can be assigned to roles or directly to users.</p>
                            <form wire:submit="createPermission" class="mt-4 grid gap-3">
                                <input wire:model="permissionForm.name" type="text" placeholder="Permission name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <input wire:model="permissionForm.slug" type="text" placeholder="permission-slug" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <input wire:model="permissionForm.description" type="text" placeholder="Description" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <input wire:model="permissionForm.model" type="text" placeholder="Model scope" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-medium text-white transition hover:bg-emerald-800">
                                    Create Permission
                                </button>
                            </form>
                        </section>
                    </div>

                    <section class="overflow-hidden rounded-[1.5rem] border border-zinc-200 bg-white">
                        <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-3">
                            <h3 class="text-base font-semibold text-zinc-950">Workspace team</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-zinc-50 text-left text-zinc-500">
                                        <th class="border-b border-zinc-200 px-4 py-3 font-medium">User</th>
                                        <th class="border-b border-zinc-200 px-4 py-3 font-medium">Role</th>
                                        <th class="border-b border-zinc-200 px-4 py-3 font-medium">Permissions</th>
                                        <th class="border-b border-zinc-200 px-4 py-3 font-medium">Workspace</th>
                                        <th class="border-b border-zinc-200 px-4 py-3 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($workspaceUsers as $workspaceUser)
                                        <tr class="odd:bg-white even:bg-zinc-50/60">
                                            <td class="border-b border-zinc-100 px-4 py-3">
                                                <div class="font-medium text-zinc-900">{{ $workspaceUser->name }}</div>
                                                <div class="text-xs text-zinc-400">{{ $workspaceUser->email }}</div>
                                            </td>
                                            <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                                @if ($workspaceUser->pivot->is_owner)
                                                    <div class="mb-1 inline-flex rounded-full bg-amber-100 px-2 py-1 text-[11px] font-medium text-amber-800">Owner</div>
                                                @endif
                                                {{ $workspaceUser->roles->pluck('name')->join(', ') ?: 'No role' }}
                                            </td>
                                            <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                                {{ $workspaceUser->getPermissions()->pluck('name')->unique()->join(', ') ?: 'No permissions' }}
                                            </td>
                                            <td class="border-b border-zinc-100 px-4 py-3 text-zinc-600">
                                                {{ $currentWorkspace->name }}
                                            </td>
                                            <td class="border-b border-zinc-100 px-4 py-3">
                                                <button wire:click="startEditingWorkspaceUser({{ $workspaceUser->id }})" type="button" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:bg-white">
                                                    Edit Access
                                                </button>
                                            </td>
                                        </tr>
                                        @if ($editingWorkspaceUserId === $workspaceUser->id)
                                            <tr>
                                                <td colspan="5" class="border-b border-zinc-100 bg-zinc-50 px-4 py-4">
                                                    <form wire:submit="updateWorkspaceUserAccess" class="grid gap-3 rounded-[1.25rem] border border-zinc-200 bg-white p-4 md:grid-cols-2">
                                                        <input wire:model="editingWorkspaceUserForm.job_title" type="text" placeholder="Job title" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                                        <select wire:model="editingWorkspaceUserForm.role" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                                            @foreach ($roles as $role)
                                                                <option value="{{ $role->slug }}">{{ $role->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select wire:model="editingWorkspaceUserForm.permission_ids" multiple class="min-h-[150px] rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none md:col-span-2">
                                                            @forelse ($permissions as $permission)
                                                                <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                                                            @empty
                                                                <option value="">Create permissions first</option>
                                                            @endforelse
                                                        </select>
                                                        <div class="flex flex-wrap gap-2 md:col-span-2">
                                                            <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                                                Save Access
                                                            </button>
                                                            <button wire:click="cancelEditingWorkspaceUser" type="button" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                                                Cancel
                                                            </button>
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-10 text-center text-zinc-500">No users are attached to this workspace yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            @endif

            @if ($activeTab === 'analytics')
                <div class="space-y-6 p-4">
                    <div class="grid gap-3 lg:grid-cols-4">
                        <select wire:model.live="analyticsRange" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="last_month">Last month</option>
                            <option value="30">Last 30 days</option>
                            <option value="60">Last 60 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="month">Specific month</option>
                            <option value="all">All time</option>
                        </select>
                        <select wire:model.live="analyticsBreakdown" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="source">Breakdown by source</option>
                            <option value="service">Breakdown by service</option>
                            <option value="status">Breakdown by lead status</option>
                            <option value="stage">Breakdown by opportunity stage</option>
                        </select>
                        @if ($analyticsRange === 'month')
                            <select wire:model.live="analyticsMonth" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                @forelse ($analyticsAvailableMonths as $monthValue => $monthLabel)
                                    <option value="{{ $monthValue }}">{{ $monthLabel }}</option>
                                @empty
                                    <option value="">No months available</option>
                                @endforelse
                            </select>
                        @else
                            <div class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-zinc-500">
                                {{ $currentWorkspace->company->name }}
                            </div>
                        @endif
                        <div class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-zinc-500">
                            {{ $currentWorkspace->name }}
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @forelse ($analyticsKpis as $kpi)
                            <article class="rounded-[1.5rem] border border-zinc-200 bg-white p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $kpi['label'] }}</p>
                                <p class="mt-3 text-2xl font-semibold text-zinc-950">{{ $kpi['value'] }}</p>
                                <p class="mt-2 text-sm text-zinc-500">{{ $kpi['detail'] }}</p>
                            </article>
                        @empty
                            <article class="rounded-[1.5rem] border border-dashed border-zinc-200 bg-white p-6 text-sm text-zinc-500 md:col-span-2 xl:col-span-4">
                                No analytics are available for this workspace yet.
                            </article>
                        @endforelse
                    </div>

                    <section>
                        <article class="rounded-[1.75rem] border border-emerald-200 bg-white p-5 shadow-sm">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-semibold text-zinc-950">SQL Performance And Deal Closures</h2>
                                    <p class="mt-1 text-sm text-zinc-500">Monthly SQL volume, pipeline value, won deals, and the top converted customers in the selected range.</p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">{{ $analyticsSnapshot['range_label'] ?? 'All time' }}</span>
                            </div>

                            <div class="mt-5 grid gap-5 lg:grid-cols-[0.85fr_1.15fr]">
                                <div class="space-y-4">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="rounded-[1.4rem] bg-emerald-800 px-5 py-6 text-white">
                                            <div class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-100">SQL Generated</div>
                                            <div class="mt-4 text-4xl font-semibold">{{ number_format((int) collect($analyticsSqlChartRows)->sum('sqls')) }}</div>
                                        </div>
                                        <div class="rounded-[1.4rem] border-2 border-emerald-300 bg-white px-5 py-6">
                                            <div class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-700">Potential Revenue</div>
                                            <div class="mt-4 text-3xl font-semibold text-emerald-900">AED {{ number_format((float) data_get($analyticsDealSummary, 'potential_value', 0), 0) }}</div>
                                        </div>
                                    </div>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="rounded-[1.4rem] bg-emerald-700 px-5 py-6 text-white">
                                            <div class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-100">Converted Value</div>
                                            <div class="mt-4 text-3xl font-semibold">AED {{ number_format((float) data_get($analyticsDealSummary, 'converted_value', 0), 0) }}</div>
                                        </div>
                                        <div class="rounded-[1.4rem] border-2 border-emerald-300 bg-white px-5 py-6">
                                            <div class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-700">Total Deals</div>
                                            <div class="mt-4 text-4xl font-semibold text-emerald-900">{{ number_format((int) data_get($analyticsDealSummary, 'total_deals', 0)) }}</div>
                                        </div>
                                    </div>
                                    <div class="rounded-[1.4rem] border-2 border-emerald-300 bg-white px-5 py-6">
                                        <div class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-700">Won Leads</div>
                                        <div class="mt-4 text-4xl font-semibold text-emerald-900">{{ number_format((int) data_get($analyticsDealSummary, 'won_leads', 0)) }}</div>
                                    </div>

                                    <div class="overflow-hidden rounded-[1.25rem] border border-emerald-200">
                                        <div class="grid grid-cols-[1.6fr_0.8fr] bg-emerald-800 text-sm font-semibold text-white">
                                            <div class="px-4 py-3">Customer Names</div>
                                            <div class="px-4 py-3">Value</div>
                                        </div>
                                        @forelse ($analyticsWonCustomers as $customer)
                                            <div class="grid grid-cols-[1.6fr_0.8fr] border-t border-emerald-100 bg-white text-sm">
                                                <div class="px-4 py-3 text-zinc-800">{{ $customer->company_name }}</div>
                                                <div class="px-4 py-3 font-medium text-zinc-900">AED {{ number_format((float) $customer->revenue_potential, 0) }}</div>
                                            </div>
                                        @empty
                                            <div class="px-4 py-6 text-sm text-zinc-500">No closed-won customers in the selected range yet.</div>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="rounded-[1.4rem] border border-zinc-200 bg-zinc-50 px-4 py-5">
                                    @php $maxSql = max(1, (int) $analyticsSqlChartRows->max('sqls')); @endphp
                                    <div class="flex h-72 items-end gap-4">
                                        @forelse ($analyticsSqlChartRows as $row)
                                            <div class="flex flex-1 flex-col items-center gap-3">
                                                <div class="flex h-56 w-full items-end justify-center gap-2">
                                                    <div class="relative flex w-1/2 items-end justify-center rounded-t-xl bg-emerald-800 text-xs font-semibold text-white" style="height: {{ max(14, min(100, ($row['sqls'] / $maxSql) * 100)) }}%;">
                                                        <span class="absolute -top-7 text-emerald-800">{{ $row['sqls'] }}</span>
                                                    </div>
                                                    <div class="relative flex w-1/2 items-end justify-center rounded-t-xl bg-emerald-200 text-xs font-semibold text-emerald-900" style="height: {{ max(10, min(100, ($row['closed_won'] / $maxSql) * 100)) }}%;">
                                                        <span class="absolute -top-7 text-emerald-700">{{ $row['closed_won'] }}</span>
                                                    </div>
                                                </div>
                                                <div class="text-xs font-medium text-zinc-500">{{ $row['label'] }}</div>
                                            </div>
                                        @empty
                                            <div class="flex h-56 items-center justify-center text-sm text-zinc-500">No monthly SQL chart data yet.</div>
                                        @endforelse
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-4 text-xs text-zinc-500">
                                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-emerald-800"></span> SQLs</span>
                                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-emerald-200"></span> Closed Won</span>
                                    </div>
                                    <div class="mt-5 rounded-[1.25rem] border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-900">
                                        In {{ strtolower($analyticsSnapshot['range_label'] ?? 'the selected range') }}, marketing-driven opportunities represent a potential pipeline of
                                        <span class="font-semibold">AED {{ number_format((float) data_get($analyticsDealSummary, 'potential_value', 0), 0) }}</span>,
                                        with converted value of
                                        <span class="font-semibold">AED {{ number_format((float) data_get($analyticsDealSummary, 'converted_value', 0), 0) }}</span>
                                        across
                                        <span class="font-semibold">{{ number_format((int) data_get($analyticsDealSummary, 'won_leads', 0)) }}</span>
                                        won deals.
                                    </div>
                                </div>
                            </div>
                        </article>
                    </section>

                    <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                        <article class="rounded-[1.75rem] border border-emerald-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-zinc-950">Google Ads Performance</h2>
                            <p class="mt-1 text-sm text-zinc-500">Benchmark-style monthly view of Google Ads leads and cost per lead from your monthly report rows.</p>
                            @php
                                $maxAdsLeads = max(1, (int) $analyticsAdsChartRows->max('leads'));
                                $maxAdsCpl = max(1, (float) $analyticsAdsChartRows->max('cpl'));
                            @endphp
                            <div class="mt-5 rounded-[1.4rem] border border-zinc-200 bg-zinc-50 px-4 py-5">
                                <div class="flex h-72 items-end gap-4">
                                    @forelse ($analyticsAdsChartRows as $row)
                                        <div class="flex flex-1 flex-col items-center gap-3">
                                            <div class="flex h-56 w-full items-end justify-center gap-2">
                                                <div class="relative flex w-1/2 items-end justify-center rounded-t-xl bg-emerald-800 text-xs font-semibold text-white" style="height: {{ max(12, min(100, ($row['leads'] / $maxAdsLeads) * 100)) }}%;">
                                                    <span class="absolute -top-7 text-emerald-800">{{ $row['leads'] }}</span>
                                                </div>
                                                <div class="relative flex w-1/2 items-end justify-center rounded-t-xl bg-lime-200 text-xs font-semibold text-emerald-900" style="height: {{ max(12, min(100, ($row['cpl'] / $maxAdsCpl) * 100)) }}%;">
                                                    <span class="absolute -top-7 whitespace-nowrap text-emerald-700">{{ number_format((float) $row['cpl'], 0) }}</span>
                                                </div>
                                            </div>
                                            <div class="text-xs font-medium text-zinc-500">{{ $row['label'] }}</div>
                                        </div>
                                    @empty
                                        <div class="flex h-56 items-center justify-center text-sm text-zinc-500">No Google Ads monthly report data is available yet.</div>
                                    @endforelse
                                </div>
                                <div class="mt-4 flex flex-wrap gap-4 text-xs text-zinc-500">
                                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-emerald-800"></span> Google Ads Leads</span>
                                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-lime-200"></span> CPL (AED)</span>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] border border-emerald-200 bg-white p-5 shadow-sm">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-[1.35rem] bg-emerald-800 px-5 py-6 text-white">
                                    <div class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-100">Ad Spend</div>
                                    <div class="mt-4 text-3xl font-semibold">AED {{ number_format((float) data_get($analyticsEfficiency, 'google_ads_spend', 0), 0) }}</div>
                                </div>
                                <div class="rounded-[1.35rem] bg-emerald-700 px-5 py-6 text-white">
                                    <div class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-100">Google Ads Leads</div>
                                    <div class="mt-4 text-3xl font-semibold">{{ number_format((int) data_get($analyticsEfficiency, 'google_ads_leads', 0)) }}</div>
                                </div>
                                <div class="rounded-[1.35rem] border-2 border-emerald-300 bg-white px-5 py-6">
                                    <div class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-700">Average CPL</div>
                                    <div class="mt-4 text-3xl font-semibold text-emerald-900">
                                        AED
                                        {{ data_get($analyticsEfficiency, 'google_ads_leads', 0) > 0
                                            ? number_format((float) data_get($analyticsEfficiency, 'google_ads_spend', 0) / max(1, (int) data_get($analyticsEfficiency, 'google_ads_leads', 0)), 0)
                                            : '0' }}
                                    </div>
                                </div>
                                <div class="rounded-[1.35rem] border-2 border-emerald-300 bg-white px-5 py-6">
                                    <div class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-700">Won Revenue</div>
                                    <div class="mt-4 text-3xl font-semibold text-emerald-900">AED {{ number_format((float) data_get($analyticsEfficiency, 'revenue', 0), 0) }}</div>
                                </div>
                            </div>
                            <div class="mt-5 rounded-[1.25rem] border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-900">
                                Google Ads contributed
                                <span class="font-semibold">{{ number_format((int) data_get($analyticsEfficiency, 'google_ads_leads', 0)) }}</span>
                                leads with total spend of
                                <span class="font-semibold">AED {{ number_format((float) data_get($analyticsEfficiency, 'google_ads_spend', 0), 0) }}</span>
                                in {{ strtolower($analyticsSnapshot['range_label'] ?? 'the selected range') }}.
                            </div>
                        </article>
                    </section>

                    <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                        <article class="rounded-[1.75rem] border border-emerald-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-zinc-950">ROMI And ROAS</h2>
                            <p class="mt-1 text-sm text-zinc-500">Efficiency view based on total ad spend and won revenue from the synced monthly reports.</p>
                            <div class="mt-5 grid gap-5 md:grid-cols-2">
                                <div class="space-y-4">
                                    <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 px-5 py-5">
                                        <div class="text-sm uppercase tracking-[0.2em] text-zinc-400">Marketing Spend</div>
                                        <div class="mt-3 text-3xl font-semibold text-zinc-950">AED {{ number_format((float) data_get($analyticsEfficiency, 'ads_spend', 0), 0) }}</div>
                                    </div>
                                    <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 px-5 py-5">
                                        <div class="text-sm uppercase tracking-[0.2em] text-zinc-400">Revenue</div>
                                        <div class="mt-3 text-3xl font-semibold text-zinc-950">AED {{ number_format((float) data_get($analyticsEfficiency, 'revenue', 0), 0) }}</div>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="rounded-[1.35rem] bg-sky-600 px-5 py-5 text-white">
                                        <div class="text-sm uppercase tracking-[0.2em] text-sky-100">ROMI</div>
                                        <div class="mt-3 text-4xl font-semibold">
                                            {{ data_get($analyticsEfficiency, 'romi') !== null ? number_format((float) data_get($analyticsEfficiency, 'romi'), 0).'%' : 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="rounded-[1.35rem] bg-sky-500 px-5 py-5 text-white">
                                        <div class="text-sm uppercase tracking-[0.2em] text-sky-100">ROAS</div>
                                        <div class="mt-3 text-4xl font-semibold">
                                            {{ data_get($analyticsEfficiency, 'roas') !== null ? number_format((float) data_get($analyticsEfficiency, 'roas'), 2).'x' : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] border border-emerald-200 bg-white p-5 shadow-sm">
                            <div class="rounded-[1.35rem] bg-emerald-800 px-5 py-6 text-white">
                                <div class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-100">Formula</div>
                                <div class="mt-4 space-y-3 text-lg font-semibold">
                                    <div>ROMI = (Revenue - Spend) / Spend × 100</div>
                                    <div>ROAS = Revenue / Ad Spend</div>
                                </div>
                            </div>
                            <div class="mt-5 space-y-3 text-sm">
                                <div class="rounded-xl bg-rose-100 px-4 py-3 text-rose-700">0% - 100%: Low ROMI, potential for improvement.</div>
                                <div class="rounded-xl bg-amber-100 px-4 py-3 text-amber-700">100% - 300%: Moderate ROMI, indicates decent performance.</div>
                                <div class="rounded-xl bg-emerald-100 px-4 py-3 text-emerald-700">300% - 500%: High ROMI, indicates strong performance.</div>
                                <div class="rounded-xl bg-sky-100 px-4 py-3 text-sky-700">500% and above: Excellent ROMI, very strong performance.</div>
                            </div>
                            <div class="mt-5 rounded-[1.25rem] px-4 py-4 text-sm font-medium {{ data_get($analyticsEfficiency, 'romi_band.classes', 'bg-zinc-100 text-zinc-600') }}">
                                Current band: {{ data_get($analyticsEfficiency, 'romi_band.label', 'No spend data') }}
                            </div>
                        </article>
                    </section>

                    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                        <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                            <h2 class="text-lg font-semibold text-zinc-950">Breakdown</h2>
                            <p class="mt-1 text-sm text-zinc-500">Change the breakdown option above to switch how the report groups your CRM activity.</p>
                            <div class="mt-4 space-y-3">
                                @php $maxBreakdown = max(1, (int) $analyticsBreakdownRows->max('total')); @endphp
                                @forelse ($analyticsBreakdownRows as $row)
                                    <div>
                                        <div class="flex items-center justify-between gap-4 text-sm">
                                            <span class="font-medium text-zinc-800">{{ $row->label }}</span>
                                            <span class="text-zinc-500">
                                                {{ number_format((int) $row->total) }}
                                                @if (isset($row->revenue))
                                                    · AED {{ number_format((float) $row->revenue, 0) }}
                                                @endif
                                            </span>
                                        </div>
                                        <div class="mt-2 h-2 rounded-full bg-zinc-100">
                                            <div class="h-2 rounded-full bg-sky-500" style="width: {{ max(8, min(100, ((int) $row->total / $maxBreakdown) * 100)) }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-zinc-500">No grouped data is available in the selected range.</p>
                                @endforelse
                            </div>
                        </article>

                        <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                            <h2 class="text-lg font-semibold text-zinc-950">Quick Snapshot</h2>
                            <div class="mt-4 space-y-3">
                                <div class="rounded-xl bg-sky-50 px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.2em] text-sky-700">Range</div>
                                    <div class="mt-2 text-lg font-semibold text-sky-950">{{ $analyticsSnapshot['range_label'] ?? 'All time' }}</div>
                                </div>
                                <div class="rounded-xl border border-zinc-200 px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Top Source</div>
                                    <div class="mt-2 text-base font-semibold text-zinc-950">{{ data_get($analyticsSnapshot, 'top_source.label', 'No data') }}</div>
                                    <div class="mt-1 text-sm text-zinc-500">{{ number_format((int) data_get($analyticsSnapshot, 'top_source.total', 0)) }} leads</div>
                                </div>
                                <div class="rounded-xl border border-zinc-200 px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Top Service</div>
                                    <div class="mt-2 text-base font-semibold text-zinc-950">{{ data_get($analyticsSnapshot, 'top_service.label', 'No data') }}</div>
                                    <div class="mt-1 text-sm text-zinc-500">{{ number_format((int) data_get($analyticsSnapshot, 'top_service.total', 0)) }} leads</div>
                                </div>
                                <div class="rounded-xl border border-zinc-200 px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Average Opportunity Value</div>
                                    <div class="mt-2 text-base font-semibold text-zinc-950">AED {{ number_format((float) data_get($analyticsSnapshot, 'avg_revenue', 0), 0) }}</div>
                                    <div class="mt-1 text-sm text-zinc-500">Based on opportunities in the selected range</div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                        <h2 class="text-lg font-semibold text-zinc-950">Monthly Reports</h2>
                        <p class="mt-1 text-sm text-zinc-500">If monthly report rows have been synced, users can review the latest report history here.</p>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-zinc-500">
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Month</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Total Leads</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Opportunities</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Won Revenue</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">MQL to SQL</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">SQL Conversion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($analyticsMonthlyRows as $report)
                                        <tr class="odd:bg-white even:bg-zinc-50/60">
                                            <td class="border-b border-zinc-100 px-3 py-2 font-medium text-zinc-900">{{ $report->year_month }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">{{ number_format((int) $report->total_leads) }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">{{ number_format((int) $report->total_opportunities_count) }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">AED {{ number_format((float) $report->won_revenue_potential, 0) }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">{{ number_format((float) $report->mql_to_sql_rate, 2) }}%</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">{{ number_format((float) $report->sql_conversion_rate, 2) }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-8 text-center text-zinc-500">No monthly report rows are available for this range yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>
                </div>
            @endif

            @if ($activeTab === 'manual-lead')
                <div class="p-4">
                    <form wire:submit="addManualLead" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <input wire:model="manualLeadForm.contact_name" type="text" placeholder="Contact name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="manualLeadForm.company_name" type="text" placeholder="Company name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="manualLeadForm.email" type="email" placeholder="Email" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="manualLeadForm.phone" type="text" placeholder="Phone" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="manualLeadForm.service" type="text" placeholder="Required service" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="manualLeadForm.lead_source" type="text" placeholder="Lead source" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <select wire:model="manualLeadForm.status" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            @foreach (\App\Models\Lead::STATUSES as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                        <input wire:model="manualLeadForm.lead_value" type="number" step="0.01" placeholder="Lead value (AED)" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <div class="hidden xl:block"></div>
                        <textarea wire:model="manualLeadForm.notes" rows="4" placeholder="Notes" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none md:col-span-2 xl:col-span-3"></textarea>
                        <button type="submit" class="rounded-xl bg-sky-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-sky-800 md:col-span-2 xl:col-span-3">
                            Save Manual Lead
                        </button>
                    </form>
                </div>
            @endif

            @if ($activeTab === 'manual-opportunity')
                <div class="p-4">
                    <div class="mb-4 rounded-[1.25rem] border border-zinc-200 bg-zinc-50 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $editingOpportunityId ? 'Opportunity Draft' : 'Manual Opportunity' }}</p>
                        <h2 class="mt-2 text-lg font-semibold text-zinc-950">
                            {{ $editingOpportunityId ? 'Complete qualified lead opportunity' : 'Add a manual opportunity' }}
                        </h2>
                        <p class="mt-1 text-sm text-zinc-500">
                            {{ $editingOpportunityId ? 'This draft was created from a Sales Qualified lead. Fill in the missing details and save it.' : 'Create a new opportunity record and link it to a lead if needed.' }}
                        </p>
                    </div>
                    <form wire:submit="addManualOpportunity" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <select wire:model="manualOpportunityForm.lead_id" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none md:col-span-2 xl:col-span-3">
                            <option value="">Optional linked lead</option>
                            @foreach ($leadOptions as $lead)
                                <option value="{{ $lead->id }}">{{ $lead->company_name }} / {{ $lead->lead_id ?: $lead->external_key }}</option>
                            @endforeach
                        </select>
                        <input wire:model="manualOpportunityForm.company_name" type="text" placeholder="Company name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="manualOpportunityForm.contact_email" type="email" placeholder="Contact email" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="manualOpportunityForm.lead_source" type="text" placeholder="Lead source" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="manualOpportunityForm.required_service" type="text" placeholder="Required service" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="manualOpportunityForm.revenue_potential" type="number" step="0.01" placeholder="Revenue potential (AED)" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <input wire:model="manualOpportunityForm.project_timeline_days" type="number" placeholder="Timeline (days)" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                        <select wire:model="manualOpportunityForm.sales_stage" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none md:col-span-2 xl:col-span-3">
                            @foreach (\App\Models\Opportunity::STAGES as $stage)
                                <option value="{{ $stage }}">{{ $stage }}</option>
                            @endforeach
                        </select>
                        <textarea wire:model="manualOpportunityForm.notes" rows="4" placeholder="Notes" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none md:col-span-2 xl:col-span-3"></textarea>
                        <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800 md:col-span-2 xl:col-span-3">
                            {{ $editingOpportunityId ? 'Save Opportunity Details' : 'Save Manual Opportunity' }}
                        </button>
                    </form>
                </div>
            @endif
        </section>
    @endif
</div>
