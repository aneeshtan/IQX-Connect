@php
    $docMode = $docMode ?? 'app';
    $isMarketing = $docMode === 'marketing';

    $workspaceModes = [
        ['name' => 'Freight Forwarder', 'desc' => 'Quotes, bookings, shipment jobs, rates, carriers, costing, and invoices.', 'tone' => 'Full freight execution'],
        ['name' => 'Ship Chandling', 'desc' => 'Vessel call requests, supply orders, urgent fulfillment, and delivery tracking.', 'tone' => 'Port-call driven'],
        ['name' => 'Container Conversion', 'desc' => 'Project-led sales, repeat follow-up, and simplified pipeline management.', 'tone' => 'Project focused'],
        ['name' => 'Shipping Liner', 'desc' => 'Schedules, bookings, vessel movement, and service visibility.', 'tone' => 'Route aware'],
        ['name' => 'Ship Management', 'desc' => 'Fleet activity, maintenance, crewing, and technical oversight.', 'tone' => 'Fleet control'],
        ['name' => 'Container Trading / Leasing', 'desc' => 'Inventory, leasing cycles, depots, and asset availability.', 'tone' => 'Asset driven'],
        ['name' => 'General Maritime', 'desc' => 'A lighter mode with CRM, collaboration, and reporting first.', 'tone' => 'Flexible start'],
    ];

    $moduleGroups = [
        [
            'title' => 'CRM Core',
            'items' => ['Leads', 'Opportunities', 'Contacts', 'Customers', 'Notes', 'Assignments', 'Messages'],
        ],
        [
            'title' => 'Commercial',
            'items' => ['Rates', 'Quotes', 'Carriers', 'Bookings', 'Revenue', 'Margins'],
        ],
        [
            'title' => 'Operations',
            'items' => ['Shipment Jobs', 'Milestones', 'Documents', 'Timeline', 'Notifications'],
        ],
        [
            'title' => 'Finance',
            'items' => ['Job Costing', 'Invoice Lines', 'Invoices', 'AP / AR Posting'],
        ],
        [
            'title' => 'Admin',
            'items' => ['Workspace Modes', 'Sources', 'Roles', 'Notification Preferences', 'Editable Labels'],
        ],
    ];

    $flowSteps = [
        ['title' => 'Capture demand', 'desc' => 'Import from Google Sheets, CSV uploads, CargoWise-style APIs, or manual entry.'],
        ['title' => 'Qualify and quote', 'desc' => 'Convert leads into opportunities, attach rates, and build lane-based quotes.'],
        ['title' => 'Move to operations', 'desc' => 'Turn accepted work into bookings, shipment jobs, milestones, and documents.'],
        ['title' => 'Cost and invoice', 'desc' => 'Track margin, post invoices, and keep AP/AR linked to the shipment job.'],
    ];

    $benefits = [
        ['title' => 'Faster qualification', 'desc' => 'Sales can sort real opportunities from noise and move faster into pricing.'],
        ['title' => 'Cleaner handoffs', 'desc' => 'Commercial and operations teams share the same record trail.'],
        ['title' => 'Better customer memory', 'desc' => 'Accounts and contacts keep the full relationship history visible.'],
        ['title' => 'Decision-ready reporting', 'desc' => 'Management sees revenue, margin, deal flow, and operational activity in one place.'],
    ];

    $integrationCards = [
        ['title' => 'Google Sheets', 'desc' => 'Public CSV links or Google Sheets API for live source sync and write-back.'],
        ['title' => 'CSV Uploads', 'desc' => 'Quick imports for leads, opportunities, quotes, shipments, and reports.'],
        ['title' => 'CargoWise Style APIs', 'desc' => 'Configurable API sources for freight systems and external operational feeds.'],
        ['title' => 'Google Ads', 'desc' => 'Marketing reporting-ready connection path for future ad performance sync.'],
    ];

    $reportingCards = [
        ['title' => 'Time-bound analytics', 'desc' => 'Default last month, then 30 / 60 / 90 days, all time, or a specific month.'],
        ['title' => 'Benchmark-style views', 'desc' => 'SQL, closed won, revenue, ROMI, ROAS, and funnel performance cards.'],
        ['title' => 'Workspace segmentation', 'desc' => 'Customer health and churn-risk style segmentation by behavior.'],
    ];
@endphp

<section class="rounded-[2rem] border border-emerald-100 bg-[linear-gradient(135deg,_rgba(15,23,42,0.96),_rgba(15,118,110,0.95)_55%,_rgba(22,163,74,0.92))] px-6 py-8 text-white shadow-2xl shadow-emerald-950/20 lg:px-8">
    <div class="grid gap-8 lg:grid-cols-[1.25fr_0.75fr] lg:items-start">
        <div class="space-y-5">
            <div class="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-emerald-50">
                {{ $isMarketing ? 'Public product guide' : 'In-app documentation' }}
            </div>

            <div class="space-y-4">
                <h1 class="max-w-4xl text-4xl font-semibold tracking-tight sm:text-5xl">
                    {{ $isMarketing ? 'Everything a maritime team needs to understand IQX Connect.' : 'A living guide to the freight and maritime workflows inside IQX Connect.' }}
                </h1>
                <p class="max-w-3xl text-base leading-8 text-emerald-50/90 sm:text-lg">
                    {{ $isMarketing ? 'Use this guide to evaluate the product, the modules, and the business value before trying the workspace.' : 'Use this page as the operational reference for workspace modes, CRM modules, integrations, reporting, and the freight workflow.' }}
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($isMarketing)
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-6 py-3.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-50">
                        Try For Free
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/25 bg-white/10 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-white/15">
                        Log In
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-6 py-3.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-50">
                        Back To Dashboard
                    </a>
                    <a href="{{ route('product') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/25 bg-white/10 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-white/15">
                        Public Product Guide
                    </a>
                @endif
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                @foreach (['Lead-to-job workflow', '7 workspace modes', 'Rates and quotes', 'Shipment jobs', 'Job costing', 'Invoices', 'Mobile friendly'] as $pill)
                    <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-emerald-50/90">
                        {{ $pill }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="rounded-[1.75rem] border border-white/15 bg-white/10 p-5 shadow-inner shadow-black/10 backdrop-blur">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-[1.35rem] border border-white/10 bg-white/10 p-4">
                    <div class="text-xs uppercase tracking-[0.24em] text-emerald-50/70">Workspace Modes</div>
                    <div class="mt-3 text-3xl font-semibold">7</div>
                    <p class="mt-2 text-sm leading-6 text-emerald-50/85">Freight, chandling, conversion, liner, ship management, leasing, and general maritime.</p>
                </div>
                <div class="rounded-[1.35rem] border border-white/10 bg-white/10 p-4">
                    <div class="text-xs uppercase tracking-[0.24em] text-emerald-50/70">Workflow</div>
                    <div class="mt-3 text-3xl font-semibold">Lead To Job</div>
                    <p class="mt-2 text-sm leading-6 text-emerald-50/85">CRM, quote, booking, shipment, costing, and invoice in one flow.</p>
                </div>
                <div class="rounded-[1.35rem] border border-white/10 bg-white/10 p-4">
                    <div class="text-xs uppercase tracking-[0.24em] text-emerald-50/70">Visibility</div>
                    <div class="mt-3 text-3xl font-semibold">Live</div>
                    <p class="mt-2 text-sm leading-6 text-emerald-50/85">Track activity, documents, financials, and margin signals from one workspace.</p>
                </div>
                <div class="rounded-[1.35rem] border border-white/10 bg-white/10 p-4">
                    <div class="text-xs uppercase tracking-[0.24em] text-emerald-50/70">Control</div>
                    <div class="mt-3 text-3xl font-semibold">Owner-led</div>
                    <p class="mt-2 text-sm leading-6 text-emerald-50/85">Workspace owners control labels, roles, sources, and notifications.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="overview" class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
    <article class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">How the platform works</div>
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Built around the freight-forwarder lifecycle.</h2>
        <p class="mt-3 text-base leading-7 text-zinc-600">
            IQX Connect keeps the commercial and operational chain in one workspace so teams do not lose context between sales, pricing, execution, and finance.
        </p>
        <div class="mt-6 space-y-4">
            @foreach ($flowSteps as $step)
                <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                    <div class="text-sm font-semibold text-zinc-950">{{ $step['title'] }}</div>
                    <div class="mt-2 text-sm leading-6 text-zinc-500">{{ $step['desc'] }}</div>
                </div>
            @endforeach
        </div>
    </article>

    <article class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="inline-flex rounded-2xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800">Workspace Modes</div>
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Use the right operating model for the business.</h2>
        <p class="mt-3 text-base leading-7 text-zinc-600">
            Each workspace can activate a different maritime template. That keeps the UI simple while giving different company types the modules they actually need.
        </p>
        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            @foreach ($workspaceModes as $mode)
                <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-sm font-semibold text-zinc-950">{{ $mode['name'] }}</div>
                        <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-medium text-zinc-500">{{ $mode['tone'] }}</span>
                    </div>
                    <div class="mt-2 text-sm leading-6 text-zinc-500">{{ $mode['desc'] }}</div>
                </div>
            @endforeach
        </div>
    </article>
</section>

<section id="modules" class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-2xl bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-800">Core modules</div>
            <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">The product stays simple at the top, but deep underneath.</h2>
        </div>
        <p class="max-w-2xl text-base leading-7 text-zinc-600">
            The workspace exposes only the modules that matter to the selected maritime company, while the data model remains strong enough for freight execution and reporting.
        </p>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-5">
        @foreach ($moduleGroups as $group)
            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                <div class="text-sm font-semibold text-zinc-950">{{ $group['title'] }}</div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($group['items'] as $item)
                        <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600">{{ $item }}</span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
    <article class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Integrations</div>
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Bring data in from the systems your team already uses.</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($integrationCards as $card)
                <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                    <div class="text-sm font-semibold text-zinc-950">{{ $card['title'] }}</div>
                    <div class="mt-2 text-sm leading-6 text-zinc-500">{{ $card['desc'] }}</div>
                </div>
            @endforeach
        </div>
    </article>

    <article id="reporting" class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="inline-flex rounded-2xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800">Reporting and segmentation</div>
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Give managers a faster read on pipeline and account health.</h2>
        <div class="mt-6 space-y-4">
            @foreach ($reportingCards as $card)
                <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                    <div class="text-sm font-semibold text-zinc-950">{{ $card['title'] }}</div>
                    <div class="mt-2 text-sm leading-6 text-zinc-500">{{ $card['desc'] }}</div>
                </div>
            @endforeach
        </div>
    </article>
</section>

<section class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
    <article class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="inline-flex rounded-2xl bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-800">Benefits</div>
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">What teams gain from the platform.</h2>
        <div class="mt-6 space-y-4">
            @foreach ($benefits as $benefit)
                <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                    <div class="text-sm font-semibold text-zinc-950">{{ $benefit['title'] }}</div>
                    <div class="mt-2 text-sm leading-6 text-zinc-500">{{ $benefit['desc'] }}</div>
                </div>
            @endforeach
        </div>
    </article>

    <article id="getting-started" class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Getting started</div>
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">How to start using IQX Connect.</h2>
        <ol class="mt-6 space-y-4">
            @foreach ([
                'Create a company and workspace, then choose the correct maritime template.',
                'Connect a source: Google Sheets, CSV, manual data, or a CargoWise-style API.',
                'Move demand from leads into quotes, bookings, shipment jobs, costing, and invoices.',
                'Turn on notifications, assign users, and review analytics by time window.',
            ] as $index => $item)
                <li class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-950 text-xs font-semibold text-white">{{ $index + 1 }}</div>
                        <div class="text-sm leading-6 text-zinc-600">{{ $item }}</div>
                    </div>
                </li>
            @endforeach
        </ol>
    </article>
</section>

@if ($isMarketing)
    <section class="rounded-[2rem] border border-emerald-200 bg-[linear-gradient(135deg,_#06281f,_#0f766e_58%,_#16a34a)] px-8 py-10 text-white shadow-2xl shadow-emerald-950/15">
        <div class="grid gap-8 lg:grid-cols-[1.35fr_0.65fr] lg:items-center">
            <div>
                <div class="text-sm uppercase tracking-[0.3em] text-emerald-100">Try it live</div>
                <h2 class="mt-4 text-4xl font-semibold tracking-tight">Sell the process, not the complexity.</h2>
                <p class="mt-4 max-w-2xl text-lg leading-8 text-emerald-50">
                    Give maritime teams a workspace they can understand on the first day, with enough depth for freight execution and reporting as they grow.
                </p>
            </div>
            <div class="flex flex-col gap-3">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-6 py-4 text-base font-medium text-zinc-950 transition hover:bg-emerald-50">
                    Try IQX Connect For Free
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/30 px-6 py-4 text-base font-medium text-white transition hover:bg-white/10">
                    Log In To Existing Workspace
                </a>
            </div>
        </div>
    </section>
@endif
