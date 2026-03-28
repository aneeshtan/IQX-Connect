<div class="space-y-6">
    <section class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-zinc-500">Admin</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Analytics and platform setup</h1>
                <p class="mt-2 max-w-2xl text-sm text-zinc-500">
                    Reporting, sheet connections, user access, and workspace structure now live on a dedicated admin page.
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
                    'analytics' => 'Analytics',
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
                    @foreach ($kpis as $kpi)
                        <article class="rounded-[1.5rem] border border-zinc-200 bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $kpi['label'] }}</p>
                            <p class="mt-3 text-2xl font-semibold text-zinc-950">{{ $kpi['value'] }}</p>
                            <p class="mt-2 text-sm text-zinc-500">{{ $kpi['detail'] }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
                    <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                        <h2 class="text-lg font-semibold text-zinc-950">Monthly reports</h2>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-zinc-500">
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Month</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Leads</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Opportunities</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Won Revenue</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">ROMI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($monthlyReports as $report)
                                        <tr class="odd:bg-white even:bg-zinc-50/60">
                                            <td class="border-b border-zinc-100 px-3 py-2 font-medium text-zinc-900">{{ $report->year_month }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">{{ $report->total_leads }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">{{ $report->total_opportunities_count }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">AED {{ number_format((float) $report->won_revenue_potential, 0) }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">{{ number_format((float) $report->romi_2025, 2) }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-3 py-8 text-center text-zinc-500">No monthly reports synced yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <div class="space-y-4">
                        <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                            <h2 class="text-lg font-semibold text-zinc-950">Latest snapshot</h2>
                            @if ($latestReport)
                                <div class="mt-4 grid gap-3">
                                    <div class="rounded-xl bg-sky-50 px-4 py-3">
                                        <div class="text-sm text-sky-700">{{ $latestReport->year_month }}</div>
                                        <div class="mt-1 text-xl font-semibold text-sky-950">AED {{ number_format((float) $latestReport->won_revenue_potential, 0) }}</div>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-xl border border-zinc-200 px-4 py-3">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">MQL to SQL</div>
                                            <div class="mt-2 text-lg font-semibold text-zinc-950">{{ number_format((float) $latestReport->mql_to_sql_rate, 2) }}%</div>
                                        </div>
                                        <div class="rounded-xl border border-zinc-200 px-4 py-3">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">SQL Conversion</div>
                                            <div class="mt-2 text-lg font-semibold text-zinc-950">{{ number_format((float) $latestReport->sql_conversion_rate, 2) }}%</div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p class="mt-4 text-sm text-zinc-500">No analytics rows available yet.</p>
                            @endif
                        </article>

                        <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                            <h2 class="text-lg font-semibold text-zinc-950">Lead source mix</h2>
                            <div class="mt-4 space-y-3">
                                @forelse ($sourceBreakdown as $source)
                                    <div>
                                        <div class="flex items-center justify-between gap-4 text-sm">
                                            <span class="font-medium text-zinc-700">{{ $source->lead_source ?: 'Unknown' }}</span>
                                            <span class="text-zinc-500">{{ $source->total }}</span>
                                        </div>
                                        <div class="mt-2 h-2 rounded-full bg-zinc-100">
                                            <div class="h-2 rounded-full bg-sky-500" style="width: {{ max(8, min(100, $source->total * 8)) }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-zinc-500">No source mix available yet.</p>
                                @endforelse
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        @endif

        @if ($activeTab === 'sources')
            <div class="grid gap-6 p-4 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="space-y-3">
                    <div class="grid gap-3 rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-4 md:grid-cols-2">
                        <select wire:model.live="sourceSort" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="newest">Newest date</option>
                            <option value="oldest">Oldest date</option>
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                            <option value="synced_desc">Last synced</option>
                        </select>
                        <select wire:model.live="sourcePerPage" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                            <option value="5">5 rows</option>
                            <option value="10">10 rows</option>
                            <option value="25">25 rows</option>
                            <option value="50">50 rows</option>
                        </select>
                    </div>

                    @forelse ($sheetSources as $source)
                        <div wire:key="sheet-source-{{ $source->id }}" class="rounded-[1.5rem] border border-zinc-200 p-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <h3 class="font-semibold text-zinc-950">{{ $source->name }}</h3>
                                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $source->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600' }}">
                                            {{ $source->is_active ? 'Active' : 'Paused' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-zinc-500">{{ ucfirst(str_replace('_', ' ', $source->type)) }} · {{ $source->sync_status }}</p>
                                    <p class="mt-2 break-all text-xs text-zinc-400">{{ $source->url }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button wire:click="startEditingSource({{ $source->id }})" type="button" class="rounded-xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                        Edit
                                    </button>
                                    <button wire:click="syncSource({{ $source->id }})" type="button" class="rounded-xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                        Sync
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

                            @if ($source->last_error)
                                <p class="mt-3 rounded-xl bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ $source->last_error }}</p>
                            @endif

                            @if ($editingSourceId === $source->id)
                                <form wire:submit="updateSheetSource" class="mt-4 grid gap-3 rounded-[1.25rem] bg-zinc-50 p-4">
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
                                    <input wire:model="editingSourceForm.name" type="text" placeholder="Source name" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                    <input wire:model="editingSourceForm.url" type="text" placeholder="{{ ($editingSourceForm['source_kind'] ?? '') === \App\Models\SheetSource::SOURCE_KIND_CARGOWISE_API ? 'CargoWise endpoint URL' : 'Source URL or upload reference' }}" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                    <select wire:model.live="editingSourceForm.source_kind" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                        @foreach (\App\Models\SheetSource::SOURCE_KINDS as $sourceKind)
                                            <option value="{{ $sourceKind }}">{{ \App\Models\SheetSource::sourceKindLabel($sourceKind) }}</option>
                                        @endforeach
                                    </select>
                                    @if (($editingSourceForm['source_kind'] ?? '') === \App\Models\SheetSource::SOURCE_KIND_CARGOWISE_API)
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
                                        @if (($editingSourceForm['cargo_auth_mode'] ?? 'basic') === 'basic')
                                            <input wire:model="editingSourceForm.cargo_username" type="text" placeholder="CargoWise username" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                            <input wire:model="editingSourceForm.cargo_password" type="password" placeholder="CargoWise password" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                        @elseif (($editingSourceForm['cargo_auth_mode'] ?? '') === 'bearer')
                                            <input wire:model="editingSourceForm.cargo_token" type="password" placeholder="CargoWise bearer token" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none md:col-span-2" />
                                        @endif
                                        <input wire:model="editingSourceForm.cargo_data_path" type="text" placeholder="Response data path, e.g. data.rows" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none md:col-span-2" />
                                    @endif
                                    <input wire:model="editingSourceForm.description" type="text" placeholder="Description" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none" />
                                    <label class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700">
                                        <input wire:model="editingSourceForm.is_active" type="checkbox" class="h-4 w-4 rounded border-zinc-300 text-sky-900 focus:ring-sky-900" />
                                        Source is active
                                    </label>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                                            Save Source
                                        </button>
                                        <button wire:click="cancelEditingSource" type="button" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-white">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-zinc-200 p-6 text-sm text-zinc-500">
                            No sources configured for this workspace.
                        </div>
                    @endforelse

                    <div>
                        {{ $sheetSources->links() }}
                    </div>
                </div>

                <div class="space-y-4">
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
            <div class="grid gap-6 p-4 xl:grid-cols-2">
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

                <div class="space-y-6">
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

                    <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                        <h2 class="text-lg font-semibold text-zinc-950">Workspace users</h2>
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <select wire:model.live="workspaceUserSort" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                <option value="newest">Newest date</option>
                                <option value="oldest">Oldest date</option>
                                <option value="name_asc">Name A-Z</option>
                                <option value="name_desc">Name Z-A</option>
                            </select>
                            <select wire:model.live="workspaceUserPerPage" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                                <option value="5">5 rows</option>
                                <option value="10">10 rows</option>
                                <option value="25">25 rows</option>
                                <option value="50">50 rows</option>
                            </select>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse ($workspaceUsers as $workspaceUser)
                                <div class="flex items-center justify-between gap-4 rounded-xl bg-zinc-50 px-4 py-3">
                                    <div>
                                        <div class="font-medium text-zinc-900">{{ $workspaceUser->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $workspaceUser->email }}</div>
                                    </div>
                                    <div class="text-right text-sm text-zinc-500">
                                        <div>{{ $workspaceUser->roles->pluck('name')->join(', ') ?: 'No role' }}</div>
                                        <div>{{ $workspaceUser->pivot->job_title ?: $workspaceUser->job_title ?: 'No title' }}</div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500">No users in the selected workspace.</p>
                            @endforelse
                        </div>
                        <div class="mt-4">
                            {{ $workspaceUsers->links() }}
                        </div>
                    </article>
                </div>
            </div>
        @endif

        @if ($activeTab === 'structure')
            <div class="grid gap-6 p-4 xl:grid-cols-2">
                <div class="space-y-6">
                    @if ($currentWorkspace)
                        <form wire:submit="updateWorkspace" class="grid gap-3 rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-semibold text-zinc-950">Workspace settings</h2>
                                    <p class="mt-1 text-sm text-zinc-500">
                                        Edit the selected workspace name, company, description, and JSON settings.
                                    </p>
                                </div>

                                <button
                                    wire:click="deleteWorkspace({{ $currentWorkspace->id }})"
                                    wire:confirm="Delete workspace {{ $currentWorkspace->name }}? This removes its leads, opportunities, and user assignments."
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
                        <button type="submit" class="rounded-xl bg-sky-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-sky-800">
                            Create Workspace
                        </button>
                    </form>
                </div>

                <div class="space-y-6">
                    <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                        <h2 class="text-lg font-semibold text-zinc-950">Companies</h2>
                        <div class="mt-4 space-y-3">
                            @foreach ($companies as $company)
                                <div class="flex items-center justify-between gap-4 rounded-xl bg-zinc-50 px-4 py-3">
                                    <div>
                                        <div class="font-medium text-zinc-900">{{ $company->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $company->industry }} · {{ $company->timezone }}</div>
                                    </div>
                                    <div class="text-sm text-zinc-500">{{ $company->workspaces_count }} workspaces</div>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <article class="rounded-[1.5rem] border border-zinc-200 p-4">
                        <h2 class="text-lg font-semibold text-zinc-950">Workspaces</h2>
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
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
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-zinc-500">
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Workspace</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Company</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Users</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Leads</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Opps</th>
                                        <th class="border-b border-zinc-200 px-3 py-2 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($workspaceRows as $workspaceRow)
                                        <tr wire:key="workspace-row-{{ $workspaceRow->id }}" class="odd:bg-white even:bg-zinc-50/60">
                                            <td class="border-b border-zinc-100 px-3 py-2 font-medium text-zinc-900">{{ $workspaceRow->name }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">{{ $workspaceRow->company->name }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">{{ $workspaceRow->users_count }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">{{ $workspaceRow->leads_count }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">{{ $workspaceRow->opportunities_count }}</td>
                                            <td class="border-b border-zinc-100 px-3 py-2 text-zinc-600">
                                                <div class="flex flex-wrap gap-2">
                                                    <button wire:click="startEditingWorkspace({{ $workspaceRow->id }})" type="button" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:bg-white">
                                                        Edit
                                                    </button>
                                                    <button
                                                        wire:click="deleteWorkspace({{ $workspaceRow->id }})"
                                                        wire:confirm="Delete workspace {{ $workspaceRow->name }}? This removes its leads, opportunities, and user assignments."
                                                        type="button"
                                                        class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-50"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        @if ($editingWorkspaceId === $workspaceRow->id)
                                            <tr wire:key="workspace-editor-{{ $workspaceRow->id }}">
                                                <td colspan="6" class="border-b border-zinc-100 bg-zinc-50 px-3 py-3">
                                                    <form wire:submit="updateWorkspace" class="grid gap-3 rounded-[1.25rem] border border-zinc-200 bg-white p-4">
                                                        <div>
                                                            <h3 class="text-sm font-semibold text-zinc-950">Edit workspace</h3>
                                                            <p class="mt-1 text-xs text-zinc-500">Update the workspace name, company, description, and settings directly from the list.</p>
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
                                                                Save Workspace
                                                            </button>
                                                            <button wire:click="cancelEditingWorkspace" type="button" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                                                                Cancel
                                                            </button>
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $workspaceRows->links() }}
                        </div>
                    </article>
                </div>
            </div>
        @endif
    </section>
</div>
