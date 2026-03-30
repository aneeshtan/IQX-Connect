@php
    $docMode = $docMode ?? 'app';
    $isMarketing = $docMode === 'marketing';

    $templateModuleMeta = [
        'leads' => ['label' => 'Leads', 'description' => 'Capture enquiries from forms, sheets, uploads, and live sources.'],
        'opportunities' => ['label' => 'Opportunities', 'description' => 'Convert qualified demand into active pipeline with revenue and timing context.'],
        'contacts' => ['label' => 'Contacts', 'description' => 'Keep customer-side people, history, and signals easy to access.'],
        'customers' => ['label' => 'Customers', 'description' => 'Track won accounts, linked records, and account health in one place.'],
        'rates' => ['label' => 'Rates', 'description' => 'Maintain lane-based buy and sell rates, transit days, and validity windows.'],
        'quotes' => ['label' => 'Quotes', 'description' => 'Build commercial offers, compare buy and sell, and manage revisions.'],
        'shipments' => ['label' => 'Shipments', 'description' => 'Track shipment jobs, milestones, dates, documents, and linked commercial context.'],
        'carriers' => ['label' => 'Carriers', 'description' => 'Manage carrier relationships, service lanes, and booking dependencies.'],
        'bookings' => ['label' => 'Bookings', 'description' => 'Move accepted work into booking requests, confirmations, and execution.'],
        'costings' => ['label' => 'Job Costing', 'description' => 'Track buy, sell, and margin against each operational job.'],
        'invoices' => ['label' => 'Invoices', 'description' => 'Manage AR and AP invoices tied back to shipments, bookings, and costings.'],
        'projects' => ['label' => 'Projects', 'description' => 'Run container conversion and delivery work from scope through completion.'],
        'drawings' => ['label' => 'Drawings', 'description' => 'Coordinate drawing revisions, technical review, and approvals.'],
        'delivery_tracking' => ['label' => 'Delivery Tracking', 'description' => 'Monitor fabrication, delivery milestones, and installation readiness.'],
        'vessel_calls' => ['label' => 'Vessel Calls', 'description' => 'Manage ETA, ETD, and vessel-linked demand for chandling teams.'],
        'supply_orders' => ['label' => 'Supply Orders', 'description' => 'Organize requisitions, supply lists, and urgent chandling fulfilment.'],
        'delivery_tasks' => ['label' => 'Delivery Tasks', 'description' => 'Coordinate final-port delivery work and completion checks.'],
        'sailings' => ['label' => 'Sailings', 'description' => 'Keep liner schedules and service coverage close to the commercial flow.'],
        'customer_accounts' => ['label' => 'Customer Accounts', 'description' => 'Track account structures, contract rates, and booking activity.'],
        'fleet' => ['label' => 'Fleet', 'description' => 'Organize managed vessels, owners, and service relationships.'],
        'technical_management' => ['label' => 'Technical Management', 'description' => 'Track management proposals, technical reviews, and vessel handover work.'],
        'crewing' => ['label' => 'Crewing', 'description' => 'Manage owner requirements and crewing-related commercial workflows.'],
        'inventory' => ['label' => 'Inventory', 'description' => 'Track stock, grades, availability, and container allocation.'],
        'leasing' => ['label' => 'Leasing', 'description' => 'Handle lease enquiries, terms, and contract progression.'],
        'depots' => ['label' => 'Depots', 'description' => 'Monitor depot locations, partners, and depot-linked container activity.'],
    ];

    $featureFlagLabels = [
        'advanced_integrations' => 'Advanced integrations',
        'premium_support' => 'Priority support',
        'custom_branding' => 'Custom branding',
        'enterprise_security' => 'Enterprise security',
    ];

    $workspaceTemplates = collect(config('workspace_templates.templates', []));
    $usageMetrics = config('pricing.usage_metrics', []);
    $pricingDefaultPlan = config('pricing.default_plan');

    $workspaceModes = $workspaceTemplates
        ->map(function (array $template, string $key) use ($templateModuleMeta, $usageMetrics) {
            $moduleLabels = collect($template['modules'] ?? [])
                ->reject(fn (string $module) => in_array($module, ['sources', 'analytics', 'access', 'settings', 'exports'], true))
                ->map(fn (string $module) => $templateModuleMeta[$module]['label'] ?? ucwords(str_replace('_', ' ', $module)))
                ->take(4)
                ->values()
                ->all();

            return [
                'key' => $key,
                'name' => $template['name'],
                'desc' => $template['description'],
                'usage_label' => data_get($usageMetrics, "{$key}.label", 'Operational records'),
                'usage_description' => data_get($usageMetrics, "{$key}.description", 'Usage tracked inside the workspace.'),
                'modules' => $moduleLabels,
            ];
        })
        ->values()
        ->all();

    $workspaceModeGuideMeta = [
        'freight_forwarding' => [
            'best_for' => 'Air, ocean, road, customs, and project cargo forwarding teams.',
            'team' => 'Sales, pricing, customer service, and operations.',
            'use_cases' => ['Lead to quote', 'Quote to booking', 'Shipment execution', 'Costing and invoicing'],
        ],
        'container_conversion' => [
            'best_for' => 'Fabrication-led projects with drawings, delivery milestones, and customer approvals.',
            'team' => 'Commercial, technical, production, and delivery teams.',
            'use_cases' => ['Project qualification', 'Drawing revisions', 'Delivery tracking', 'Installation readiness'],
        ],
        'ship_chandling' => [
            'best_for' => 'Port-call supply operations with urgent requisitions and final-mile fulfillment.',
            'team' => 'Commercial desk, sourcing, and delivery teams.',
            'use_cases' => ['Vessel enquiry capture', 'Supply orders', 'Urgent delivery coordination', 'Port completion'],
        ],
        'shipping_liner' => [
            'best_for' => 'Liner teams managing customer accounts, sailings, and slot-driven bookings.',
            'team' => 'Sales, customer service, and booking control.',
            'use_cases' => ['Booking requests', 'Schedule checks', 'Account visibility', 'Contract-rate workflows'],
        ],
        'ship_management' => [
            'best_for' => 'Owner-facing management companies with technical, crewing, and procurement workflows.',
            'team' => 'Commercial, technical management, and crewing teams.',
            'use_cases' => ['Owner acquisition', 'Technical review', 'Management handover', 'Contract tracking'],
        ],
        'container_trading_leasing' => [
            'best_for' => 'Stock-led container sales and leasing teams working with depots and allocation decisions.',
            'team' => 'Sales, inventory control, and depot coordination.',
            'use_cases' => ['Stock qualification', 'Leasing deals', 'Depot coordination', 'Asset allocation'],
        ],
        'general_maritime' => [
            'best_for' => 'Maritime businesses that need a lighter CRM setup before enabling deeper execution workflows.',
            'team' => 'Founders, sales teams, and cross-functional operators.',
            'use_cases' => ['CRM rollout', 'Pipeline visibility', 'Account tracking', 'Reporting-first adoption'],
        ],
    ];

    $workspaceModeGuides = $workspaceTemplates
        ->map(function (array $template, string $key) use ($templateModuleMeta, $usageMetrics, $workspaceModeGuideMeta) {
            $guideMeta = $workspaceModeGuideMeta[$key] ?? [
                'best_for' => $template['description'],
                'team' => 'Commercial and operations teams.',
                'use_cases' => [],
            ];

            return [
                'key' => $key,
                'name' => $template['name'],
                'desc' => $template['description'],
                'best_for' => $guideMeta['best_for'],
                'team' => $guideMeta['team'],
                'usage_label' => data_get($usageMetrics, "{$key}.label", 'Operational records'),
                'usage_description' => data_get($usageMetrics, "{$key}.description", 'Usage tracked inside the workspace.'),
                'modules' => collect($template['modules'] ?? [])
                    ->reject(fn (string $module) => in_array($module, ['sources', 'analytics', 'access', 'settings', 'exports'], true))
                    ->map(fn (string $module) => [
                        'label' => $templateModuleMeta[$module]['label'] ?? ucwords(str_replace('_', ' ', $module)),
                        'description' => $templateModuleMeta[$module]['description'] ?? 'Mode-specific workflow support.',
                    ])
                    ->take(6)
                    ->values()
                    ->all(),
                'services' => collect(data_get($template, 'vocabulary.lead_services', []))->take(4)->values()->all(),
                'sources' => collect(data_get($template, 'vocabulary.lead_sources', []))->take(4)->values()->all(),
                'use_cases' => $guideMeta['use_cases'],
            ];
        })
        ->values()
        ->all();

    $pricingPlans = collect(config('pricing.plans', []))
        ->map(function (array $plan, string $key) use ($featureFlagLabels, $pricingDefaultPlan) {
            return [
                'key' => $key,
                'name' => $plan['name'],
                'price_label' => $plan['price_label'],
                'included_users' => $plan['included_users'],
                'included_operational_records' => $plan['included_operational_records'],
                'highlights' => $plan['highlights'] ?? [],
                'extras' => collect($featureFlagLabels)
                    ->filter(fn (string $label, string $flag) => (bool) data_get($plan, "feature_flags.{$flag}", false))
                    ->values()
                    ->all(),
                'is_default' => $pricingDefaultPlan === $key,
            ];
        })
        ->values()
        ->all();

    $flowSteps = [
        ['title' => 'Capture demand', 'desc' => 'Bring in leads, opportunities, and reports from Google Sheets, CSV uploads, APIs, or manual entry.'],
        ['title' => 'Run the commercial workflow', 'desc' => 'Qualify demand, score leads, manage opportunities, and create quotes or project proposals.'],
        ['title' => 'Execute delivery work', 'desc' => 'Convert accepted work into bookings, shipment jobs, projects, milestones, drawings, or delivery tasks based on the workspace mode.'],
        ['title' => 'Close the financial loop', 'desc' => 'Track costing, invoices, account activity, and reporting windows without breaking the record trail.'],
    ];

    $capabilityGroups = [
        [
            'title' => 'Commercial CRM',
            'desc' => 'The core dashboard keeps lead, opportunity, contact, and customer work in one searchable screen.',
            'items' => ['Leads', 'Opportunities', 'Contacts', 'Customers', 'Assignments', 'Lead scoring'],
        ],
        [
            'title' => 'Execution and finance',
            'desc' => 'Freight teams can move from pricing into bookings, shipment jobs, costing, and invoicing on linked records.',
            'items' => ['Rates', 'Quotes', 'Bookings', 'Shipment Jobs', 'Job Costing', 'Invoices'],
        ],
        [
            'title' => 'Projects and delivery',
            'desc' => 'Project-led workspaces expose project records, drawings, and delivery milestones instead of forcing a freight workflow everywhere.',
            'items' => ['Projects', 'Drawings', 'Delivery tracking', 'Milestones', 'Documents', 'Assigned owners'],
        ],
        [
            'title' => 'Collaboration and control',
            'desc' => 'Teams can keep notes, activity, notifications, segmentation, and workspace settings close to the records they work on.',
            'items' => ['Collaboration entries', 'Workspace notifications', 'Customer segments', 'Roles and access', 'Editable labels', 'Exports'],
        ],
    ];

    $integrationCards = [
        ['title' => 'Google Sheets API', 'desc' => 'Connect authenticated spreadsheets, sync records in, and support write-back where mapped.'],
        ['title' => 'Public or uploaded CSV', 'desc' => 'Import leads, opportunities, reports, or operational records without a full integration project.'],
        ['title' => 'CargoWise-style APIs', 'desc' => 'Bring in external operational data through configurable API-based sources.'],
        ['title' => 'Migration-first onboarding', 'desc' => 'Start with live sources and manual work, then deepen the operating model as adoption grows.'],
    ];

    $reportingCards = [
        ['title' => 'Flexible reporting windows', 'desc' => 'Use last month, 30, 60, or 90 days, all time, or a specific month.'],
        ['title' => 'Benchmark-style KPIs', 'desc' => 'Track SQL, won deals, revenue, ROMI, ROAS, and funnel performance from one workspace.'],
        ['title' => 'Customer segmentation', 'desc' => 'Define account segments and keep health, opportunity volume, and account activity visible.'],
        ['title' => 'Operator-ready visibility', 'desc' => 'Keep activity, notes, milestones, documents, and account context accessible inside each record.'],
    ];

    $benefits = [
        ['title' => 'One record trail', 'desc' => 'Commercial, operational, and financial work stays connected instead of breaking across spreadsheets and inboxes.'],
        ['title' => 'Mode-specific setup', 'desc' => 'Each maritime business model gets the modules and labels it actually needs.'],
        ['title' => 'Faster adoption', 'desc' => 'List-first screens, inline actions, and mobile-friendly views reduce CRM overhead for operators.'],
        ['title' => 'Clear upgrade path', 'desc' => 'Teams can start free, validate the workflow, and move into paid plans when usage and headcount increase.'],
    ];

    $gettingStartedSteps = [
        'Create a company and workspace, then choose the operating template that matches the business.',
        'Connect Google Sheets, upload CSV files, or create records manually so the workspace starts with real data.',
        'Run the commercial process, then convert accepted work into bookings, shipment jobs, projects, costing, or invoices as required.',
        'Set roles, notification preferences, customer segments, and exports once the team is live.',
    ];

    $helpCenterTopics = [
        [
            'href' => '#getting-started',
            'title' => 'Getting started',
            'desc' => 'Launch the workspace, invite users, connect sources, and understand the first operational workflow.',
            'articles' => ['Workspace setup', 'Imports and source sync', 'First records'],
        ],
        [
            'href' => '#workspace-modes',
            'title' => 'Workspace modes',
            'desc' => 'Compare every business-mode template, its modules, usage metric, and common rollout fit.',
            'articles' => ['Freight Forwarder', 'Container Conversion', 'Ship Chandling'],
        ],
        [
            'href' => '#modules',
            'title' => 'Feature directory',
            'desc' => 'Browse CRM, execution, project, finance, collaboration, and admin capabilities by category.',
            'articles' => ['CRM', 'Execution', 'Projects and delivery'],
        ],
        [
            'href' => '#use-cases',
            'title' => 'Use-case playbooks',
            'desc' => 'See how teams handle lead-to-job, migrations, reporting, and cross-team handoffs inside IQX Connect.',
            'articles' => ['Sales to ops', 'Migration rollout', 'Management visibility'],
        ],
        [
            'href' => '#integrations',
            'title' => 'Integrations and migration',
            'desc' => 'Understand CSV, Google Sheets, API-based imports, and phased migration patterns.',
            'articles' => ['Google Sheets', 'CSV imports', 'CargoWise-style APIs'],
        ],
        [
            'href' => '#reporting',
            'title' => 'Reporting and control',
            'desc' => 'Review the dashboard, KPIs, account health, segmentation, and management reporting surface.',
            'articles' => ['Benchmarks', 'Account segmentation', 'Monthly reporting'],
        ],
        [
            'href' => '#pricing',
            'title' => 'Pricing and packaging',
            'desc' => 'See exactly how freemium, paid plans, and usage packaging map to workspace growth.',
            'articles' => ['Freemium', 'Growth', 'Enterprise'],
        ],
        [
            'href' => '#resources',
            'title' => 'Resources and FAQs',
            'desc' => 'Jump to presentation material, buyer FAQs, and the supporting resources around the product guide.',
            'articles' => ['Presentation', 'FAQs', 'Guide map'],
        ],
    ];

    $useCasePlaybooks = [
        [
            'title' => 'Launch a new maritime workspace',
            'audience' => 'Owners and admins',
            'desc' => 'Stand up the company, pick the right operating mode, bring in source data, and start the first team workflow.',
            'steps' => ['Choose the workspace mode', 'Import or sync live data', 'Configure roles and notifications'],
        ],
        [
            'title' => 'Move demand into execution',
            'audience' => 'Sales and operations teams',
            'desc' => 'Keep enquiry, quote, booking, shipment, project, and invoice context on one record trail.',
            'steps' => ['Qualify the lead', 'Convert into quote or proposal', 'Hand off to bookings, projects, or costing'],
        ],
        [
            'title' => 'Run migration without stopping work',
            'audience' => 'Implementation leads',
            'desc' => 'Start with spreadsheets and manual workflows, then deepen the operating model with better sources and structure.',
            'steps' => ['Begin with CSV or sheets', 'Map live operational records', 'Expand modules after adoption'],
        ],
        [
            'title' => 'Give management visibility fast',
            'audience' => 'Leaders and finance',
            'desc' => 'Use time windows, KPI tracking, account segmentation, and operational context to understand performance.',
            'steps' => ['Pick reporting windows', 'Review pipeline and revenue', 'Monitor account health and activity'],
        ],
    ];

    $resourceHubCards = [
        ['title' => 'Workspace mode catalog', 'desc' => 'Browse all maritime templates, their modules, and the usage metric tied to each mode.', 'href' => '#workspace-modes', 'cta' => 'Browse modes'],
        ['title' => 'Use-case playbooks', 'desc' => 'Read rollout and workflow guidance by team objective, not just by module name.', 'href' => '#use-cases', 'cta' => 'See playbooks'],
        ['title' => 'Pricing and packaging', 'desc' => 'Review the current plans, included users, and operational record packaging.', 'href' => '#pricing', 'cta' => 'Review pricing'],
        ['title' => 'Marketing presentation', 'desc' => 'Share the deck version of the story with internal stakeholders and buyers.', 'href' => route('presentation'), 'cta' => 'Open presentation'],
    ];

    $faqItems = [
        [
            'question' => 'What does IQX Connect cover in one app?',
            'answer' => 'It combines CRM, operational execution, project delivery, costing, invoicing, collaboration, and reporting inside one workspace so teams can move from enquiry to execution without switching systems.',
        ],
        [
            'question' => 'Which business models can use it?',
            'answer' => 'The product ships with workspace templates for Freight Forwarder, Container Conversion Company, Ship Chandling, Shipping Liner, Ship Management Company, Container Trading / Leasing, and General Maritime teams.',
        ],
        [
            'question' => 'What is included in the Freemium plan?',
            'answer' => 'Freemium includes one workspace, three users, the first 100 operational records, and the core CRM, collaboration, and reporting workflow so teams can validate the process before paying.',
        ],
        [
            'question' => 'How do the paid plans scale?',
            'answer' => 'Growth adds five users, 500 operational records, and standard integrations. Professional increases that to ten users, 1,500 operational records, and adds advanced workflow, finance, segmentation, support, and branding options. Enterprise is custom for rollouts, security, and integration-heavy teams.',
        ],
        [
            'question' => 'What counts as an operational record?',
            'answer' => 'The usage metric follows the workspace mode. Freight Forwarder counts shipment jobs, Container Conversion counts projects, Ship Chandling counts operational orders, Shipping Liner counts bookings, Ship Management counts managed contracts, Container Trading / Leasing counts commercial deals, and General Maritime counts operational benchmark records.',
        ],
        [
            'question' => 'Can we import from Google Sheets or CSV before a full migration?',
            'answer' => 'Yes. IQX Connect supports public CSV links, uploaded CSV files, authenticated Google Sheets, and API-based source integrations so teams can start with the data they already have.',
        ],
        [
            'question' => 'Does the product support both sales and operations teams?',
            'answer' => 'Yes. Leads and opportunities stay linked to quotes, bookings, shipment jobs, projects, milestones, costings, and invoices so handoffs do not lose context.',
        ],
        [
            'question' => 'How are collaboration and notifications handled?',
            'answer' => 'The app includes collaboration entries on records, assignment workflows, and workspace notifications so teams can coordinate directly inside the CRM instead of relying on scattered email threads.',
        ],
        [
            'question' => 'What reporting is available?',
            'answer' => 'Managers can review time-bound dashboards, monthly reports, funnel metrics, revenue visibility, source performance, and customer segmentation from the same workspace.',
        ],
        [
            'question' => 'When should a team consider Enterprise?',
            'answer' => 'Enterprise is the right fit when the rollout needs custom user volume, SSO or enterprise controls, SLA-backed support, multi-workspace onboarding, or custom integrations beyond the standard setup.',
        ],
    ];
@endphp

@if ($isMarketing)
    <section class="overflow-hidden rounded-[2.25rem] border border-emerald-100 bg-white shadow-2xl shadow-emerald-950/10">
        <div class="border-b border-emerald-100 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.16),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,130,246,0.10),_transparent_32%),linear-gradient(180deg,_#fcfffe,_#f7fbff)] px-6 py-8 lg:px-8 lg:py-10">
            <div class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
                <div class="space-y-6">
                    <div class="inline-flex rounded-full border border-emerald-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-emerald-800 shadow-sm">
                        IQX Connect Help Center
                    </div>

                    <div class="space-y-4">
                        <h1 class="max-w-4xl text-4xl font-semibold tracking-tight text-zinc-950 sm:text-5xl">
                            A comprehensive help center for IQX Connect features, workspace modes, and rollout use cases.
                        </h1>
                        <p class="max-w-3xl text-base leading-8 text-zinc-600 sm:text-lg">
                            Use this guide like a product help center: start with setup, browse workspace modes, compare features by category, review migration paths, and understand how each use case fits different maritime teams.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-2xl bg-[linear-gradient(135deg,_#0f766e,_#16a34a)] px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-950/15 transition hover:scale-[1.01]">
                            Start your journey
                        </a>
                        <a href="#help-topics" class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-6 py-3.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50">
                            Browse help topics
                        </a>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-[1.35rem] border border-zinc-200 bg-white p-4 shadow-sm">
                            <div class="text-xs uppercase tracking-[0.24em] text-zinc-400">Workspace Modes</div>
                            <div class="mt-3 text-xl font-semibold text-zinc-950">{{ count($workspaceModes) }}</div>
                            <p class="mt-2 text-sm leading-6 text-zinc-500">Freight, projects, liner, chandling, ship management, leasing, and general maritime workflows.</p>
                        </div>
                        <div class="rounded-[1.35rem] border border-zinc-200 bg-white p-4 shadow-sm">
                            <div class="text-xs uppercase tracking-[0.24em] text-zinc-400">Feature Footprint</div>
                            <div class="mt-3 text-xl font-semibold text-zinc-950">CRM + Ops + Finance</div>
                            <p class="mt-2 text-sm leading-6 text-zinc-500">Leads through invoices stay linked on one record chain.</p>
                        </div>
                        <div class="rounded-[1.35rem] border border-zinc-200 bg-white p-4 shadow-sm">
                            <div class="text-xs uppercase tracking-[0.24em] text-zinc-400">Pricing Plans</div>
                            <div class="mt-3 text-xl font-semibold text-zinc-950">{{ count($pricingPlans) }}</div>
                            <p class="mt-2 text-sm leading-6 text-zinc-500">Freemium, Growth, Professional, and Enterprise.</p>
                        </div>
                        <div class="rounded-[1.35rem] border border-zinc-200 bg-white p-4 shadow-sm">
                            <div class="text-xs uppercase tracking-[0.24em] text-zinc-400">Source Options</div>
                            <div class="mt-3 text-xl font-semibold text-zinc-950">Sheets + CSV + APIs</div>
                            <p class="mt-2 text-sm leading-6 text-zinc-500">Migration can start with the data tools teams already use.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.8rem] border border-zinc-200 bg-zinc-950 p-5 text-white shadow-[0_20px_60px_rgba(15,23,42,0.18)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xs uppercase tracking-[0.26em] text-emerald-100/70">Help Center Map</div>
                            <div class="mt-2 text-2xl font-semibold">What you can find here</div>
                        </div>
                        <div class="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-medium text-white/80">Comprehensive</div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ([
                            ['step' => '01', 'title' => 'Help topics', 'desc' => 'A topic directory for setup, workspace modes, features, reporting, and pricing.'],
                            ['step' => '02', 'title' => 'Workspace modes', 'desc' => 'Every maritime operating template, its modules, usage metric, and team fit.'],
                            ['step' => '03', 'title' => 'Use-case playbooks', 'desc' => 'Role-based examples for rollout, migration, commercial handoff, and management visibility.'],
                            ['step' => '04', 'title' => 'Feature directory', 'desc' => 'Grouped coverage for CRM, execution, delivery, collaboration, admin, and reporting.'],
                            ['step' => '05', 'title' => 'Resources and FAQs', 'desc' => 'Pricing, presentation assets, and direct buyer or operator answers.'],
                        ] as $item)
                            <div class="rounded-[1.25rem] border border-white/10 bg-white/6 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-400/15 text-xs font-semibold text-emerald-100">{{ $item['step'] }}</div>
                                    <div>
                                        <div class="text-sm font-semibold text-white">{{ $item['title'] }}</div>
                                        <div class="mt-1 text-sm leading-6 text-white/70">{{ $item['desc'] }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 rounded-[1.2rem] border border-white/10 bg-white/6 p-4">
                        <div class="text-xs uppercase tracking-[0.22em] text-white/55">Default entry plan</div>
                        <div class="mt-2 text-lg font-semibold text-white">Freemium</div>
                        <p class="mt-2 text-sm leading-6 text-white/70">Start with one workspace, three users, and the first 100 operational records before moving up to paid tiers.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@else
    <section class="rounded-[2rem] border border-emerald-100 bg-[linear-gradient(135deg,_rgba(15,23,42,0.96),_rgba(15,118,110,0.95)_55%,_rgba(22,163,74,0.92))] px-6 py-8 text-white shadow-2xl shadow-emerald-950/20 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[1.25fr_0.75fr] lg:items-start">
            <div class="space-y-5">
                <div class="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-emerald-50">
                    In-app documentation
                </div>

                <div class="space-y-4">
                    <h1 class="max-w-4xl text-4xl font-semibold tracking-tight sm:text-5xl">
                        A living guide to the current IQX Connect feature set, workspace templates, and pricing model.
                    </h1>
                    <p class="max-w-3xl text-base leading-8 text-emerald-50/90 sm:text-lg">
                        Use this page as the operational reference for workspace modes, feature coverage, integrations, reporting, and how the product packages usage across plans.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-6 py-3.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-50">
                        Back To Dashboard
                    </a>
                    <a href="{{ route('presentation') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/25 bg-white/10 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-white/15">
                        View presentation
                    </a>
                </div>

                <div class="flex flex-wrap gap-2 pt-2">
                    @foreach (['Lead To Job', '7 workspace modes', 'Rates and quotes', 'Projects and shipments', 'Job costing', 'Invoices', 'Notifications'] as $pill)
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
                        <div class="mt-3 text-3xl font-semibold">{{ count($workspaceModes) }}</div>
                        <p class="mt-2 text-sm leading-6 text-emerald-50/85">Freight, conversion, chandling, liner, ship management, leasing, and general maritime templates.</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-white/10 bg-white/10 p-4">
                        <div class="text-xs uppercase tracking-[0.24em] text-emerald-50/70">Workflow</div>
                        <div class="mt-3 text-3xl font-semibold">Lead To Job</div>
                        <p class="mt-2 text-sm leading-6 text-emerald-50/85">CRM, quotes, bookings, shipment jobs, projects, costing, and invoices can stay linked.</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-white/10 bg-white/10 p-4">
                        <div class="text-xs uppercase tracking-[0.24em] text-emerald-50/70">Pricing Models</div>
                        <div class="mt-3 text-3xl font-semibold">{{ count($pricingPlans) }}</div>
                        <p class="mt-2 text-sm leading-6 text-emerald-50/85">Freemium through Enterprise with workspace-based usage packaging.</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-white/10 bg-white/10 p-4">
                        <div class="text-xs uppercase tracking-[0.24em] text-emerald-50/70">Control</div>
                        <div class="mt-3 text-3xl font-semibold">Owner-led</div>
                        <p class="mt-2 text-sm leading-6 text-emerald-50/85">Workspace owners control labels, roles, sources, notifications, and exports.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

@if ($isMarketing)
    <div class="mt-10 grid gap-8 lg:grid-cols-[240px_minmax(0,1fr)]">
        <aside class="self-start lg:sticky lg:top-6">
            <div class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.3em] text-zinc-400">On this page</div>
                <nav class="mt-4 space-y-2 text-sm">
                    @foreach ([
                        ['href' => '#help-topics', 'label' => 'Help topics'],
                        ['href' => '#overview', 'label' => 'Platform overview'],
                        ['href' => '#workspace-modes', 'label' => 'Workspace modes'],
                        ['href' => '#use-cases', 'label' => 'Use cases'],
                        ['href' => '#modules', 'label' => 'Feature directory'],
                        ['href' => '#integrations', 'label' => 'Integrations'],
                        ['href' => '#reporting', 'label' => 'Reporting'],
                        ['href' => '#benefits', 'label' => 'Benefits'],
                        ['href' => '#resources', 'label' => 'Resources'],
                        ['href' => '#pricing', 'label' => 'Pricing'],
                        ['href' => '#faqs', 'label' => 'FAQs'],
                        ['href' => '#getting-started', 'label' => 'Getting started'],
                    ] as $link)
                        <a href="{{ $link['href'] }}" class="flex items-center justify-between rounded-2xl px-3 py-2.5 text-zinc-600 transition hover:bg-zinc-50 hover:text-zinc-950">
                            <span>{{ $link['label'] }}</span>
                            <span class="text-xs text-zinc-300">→</span>
                        </a>
                    @endforeach
                </nav>

                <div class="mt-5 rounded-[1.25rem] border border-emerald-100 bg-emerald-50 p-4">
                    <div class="text-xs uppercase tracking-[0.24em] text-emerald-700">Freemium</div>
                    <div class="mt-2 text-lg font-semibold text-zinc-950">First 100 operational records</div>
                    <p class="mt-2 text-sm leading-6 text-zinc-600">Start with real usage, then move into Growth, Professional, or Enterprise when rollout size increases.</p>
                </div>
            </div>
        </aside>

        <div class="space-y-6">
@endif

@if ($isMarketing)
<section id="help-topics" class="scroll-mt-28 rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Help topics</div>
            <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Start from the question you need answered.</h2>
        </div>
        <p class="max-w-2xl text-base leading-7 text-zinc-600">
            Use this page as a structured help center. Teams can jump directly into setup, workspace-mode fit, feature categories, migration paths, reporting, pricing, and rollout resources.
        </p>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($helpCenterTopics as $topic)
            <a href="{{ $topic['href'] }}" class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-5 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-sm">
                <div class="text-lg font-semibold tracking-tight text-zinc-950">{{ $topic['title'] }}</div>
                <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $topic['desc'] }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($topic['articles'] as $article)
                        <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-[11px] font-medium text-zinc-600">{{ $article }}</span>
                    @endforeach
                </div>
                <div class="mt-5 text-sm font-medium text-emerald-700">Open section →</div>
            </a>
        @endforeach
    </div>
</section>

<section id="workspace-modes" class="scroll-mt-28 rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-2xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800">Workspace mode catalog</div>
            <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Compare every maritime operating mode in one place.</h2>
        </div>
        <p class="max-w-2xl text-base leading-7 text-zinc-600">
            Each workspace mode adapts the workflow, labels, and usage packaging to the business model. This helps buyers understand whether the product fits forwarding, projects, liner, chandling, ship management, leasing, or a lighter general mode.
        </p>
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-2">
        @foreach ($workspaceModeGuides as $mode)
            <article class="rounded-[1.55rem] border border-zinc-200 bg-zinc-50 p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="text-lg font-semibold tracking-tight text-zinc-950">{{ $mode['name'] }}</div>
                        <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $mode['desc'] }}</p>
                    </div>
                    <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-[11px] font-medium text-zinc-600">{{ $mode['usage_label'] }}</span>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-[1.2rem] border border-zinc-200 bg-white p-4">
                        <div class="text-xs uppercase tracking-[0.22em] text-zinc-400">Best for</div>
                        <div class="mt-2 text-sm leading-7 text-zinc-600">{{ $mode['best_for'] }}</div>
                    </div>
                    <div class="rounded-[1.2rem] border border-zinc-200 bg-white p-4">
                        <div class="text-xs uppercase tracking-[0.22em] text-zinc-400">Primary team</div>
                        <div class="mt-2 text-sm leading-7 text-zinc-600">{{ $mode['team'] }}</div>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="text-sm font-semibold text-zinc-950">Core modules</div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($mode['modules'] as $module)
                            <div class="rounded-[1.15rem] border border-zinc-200 bg-white p-3">
                                <div class="text-sm font-semibold text-zinc-950">{{ $module['label'] }}</div>
                                <div class="mt-1 text-xs leading-6 text-zinc-500">{{ $module['description'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div>
                        <div class="text-sm font-semibold text-zinc-950">Typical services</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($mode['services'] as $service)
                                <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-[11px] font-medium text-zinc-600">{{ $service }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-zinc-950">Common sources</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($mode['sources'] as $source)
                                <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-[11px] font-medium text-zinc-600">{{ $source }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-5 rounded-[1.2rem] border border-emerald-100 bg-emerald-50 p-4">
                    <div class="text-sm font-semibold text-zinc-950">Common use cases</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($mode['use_cases'] as $useCase)
                            <span class="rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-[11px] font-medium text-emerald-700">{{ $useCase }}</span>
                        @endforeach
                    </div>
                    <p class="mt-3 text-sm leading-7 text-zinc-600">{{ $mode['usage_description'] }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>

<section id="use-cases" class="scroll-mt-28 rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-2xl bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-800">Use-case playbooks</div>
            <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Guide teams by workflow and business objective.</h2>
        </div>
        <p class="max-w-2xl text-base leading-7 text-zinc-600">
            Buyers rarely think in module names. These playbooks show how IQX Connect supports real launch, migration, delivery, and management objectives across maritime teams.
        </p>
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-2">
        @foreach ($useCasePlaybooks as $playbook)
            <article class="rounded-[1.45rem] border border-zinc-200 bg-zinc-50 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-lg font-semibold tracking-tight text-zinc-950">{{ $playbook['title'] }}</div>
                        <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $playbook['desc'] }}</p>
                    </div>
                    <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-[11px] font-medium text-zinc-600">{{ $playbook['audience'] }}</span>
                </div>

                <ol class="mt-5 space-y-3">
                    @foreach ($playbook['steps'] as $index => $step)
                        <li class="rounded-[1.15rem] border border-zinc-200 bg-white p-3">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-950 text-[11px] font-semibold text-white">{{ $index + 1 }}</div>
                                <div class="text-sm leading-6 text-zinc-600">{{ $step }}</div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </article>
        @endforeach
    </div>
</section>
@endif

<section id="overview" class="scroll-mt-28 grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
    <article class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">How the platform works</div>
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Built to keep commercial, operational, and financial work on one timeline.</h2>
        <p class="mt-3 text-base leading-7 text-zinc-600">
            IQX Connect keeps the record chain intact from first enquiry through shipment, project, costing, and invoicing, so teams do not lose context during handoff.
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
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Use the operating model that matches the business.</h2>
        <p class="mt-3 text-base leading-7 text-zinc-600">
            Each workspace template changes the modules, labels, and usage metric so different maritime companies can run the same platform without forcing the same process.
        </p>
        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            @foreach ($workspaceModes as $mode)
                <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-sm font-semibold text-zinc-950">{{ $mode['name'] }}</div>
                        <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-medium text-zinc-500">{{ $mode['usage_label'] }}</span>
                    </div>
                    <div class="mt-2 text-sm leading-6 text-zinc-500">{{ $mode['desc'] }}</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($mode['modules'] as $module)
                            <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-[11px] font-medium text-zinc-600">{{ $module }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </article>
</section>

<section id="modules" class="scroll-mt-28 rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-2xl bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-800">Feature map</div>
            <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">The public guide now reflects the actual app surface.</h2>
        </div>
        <p class="max-w-2xl text-base leading-7 text-zinc-600">
            IQX Connect is not only a lead tracker. The product spans CRM, execution, delivery, finance, collaboration, and admin controls depending on the workspace template.
        </p>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @foreach ($capabilityGroups as $group)
            <article class="rounded-[1.45rem] border border-zinc-200 bg-zinc-50 p-5">
                <div class="text-lg font-semibold tracking-tight text-zinc-950">{{ $group['title'] }}</div>
                <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $group['desc'] }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($group['items'] as $item)
                        <span class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600">{{ $item }}</span>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
</section>

<section id="integrations" class="scroll-mt-28 grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
    <article class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Integrations and rollout</div>
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Bring data in before replacing every legacy workflow.</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($integrationCards as $card)
                <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                    <div class="text-sm font-semibold text-zinc-950">{{ $card['title'] }}</div>
                    <div class="mt-2 text-sm leading-6 text-zinc-500">{{ $card['desc'] }}</div>
                </div>
            @endforeach
        </div>
    </article>

    <article id="reporting" class="scroll-mt-28 rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="inline-flex rounded-2xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800">Reporting and visibility</div>
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Give management a faster read on demand, delivery, and account health.</h2>
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
    <article id="benefits" class="scroll-mt-28 rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="inline-flex rounded-2xl bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-800">Benefits</div>
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Why maritime teams adopt the platform.</h2>
        <div class="mt-6 space-y-4">
            @foreach ($benefits as $benefit)
                <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                    <div class="text-sm font-semibold text-zinc-950">{{ $benefit['title'] }}</div>
                    <div class="mt-2 text-sm leading-6 text-zinc-500">{{ $benefit['desc'] }}</div>
                </div>
            @endforeach
        </div>
    </article>

    <article id="getting-started" class="scroll-mt-28 rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Getting started</div>
        <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">How teams usually roll it out.</h2>
        <ol class="mt-6 space-y-4">
            @foreach ($gettingStartedSteps as $index => $item)
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
    <section id="resources" class="scroll-mt-28 rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Resources hub</div>
                <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Everything around the product guide in one place.</h2>
            </div>
            <p class="max-w-2xl text-base leading-7 text-zinc-600">
                Keep buyer-facing material, deeper guide sections, and supporting rollout references easy to access without forcing visitors to scan the whole page from top to bottom.
            </p>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($resourceHubCards as $resource)
                <a href="{{ $resource['href'] }}" class="rounded-[1.45rem] border border-zinc-200 bg-zinc-50 p-5 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-sm">
                    <div class="text-lg font-semibold tracking-tight text-zinc-950">{{ $resource['title'] }}</div>
                    <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $resource['desc'] }}</p>
                    <div class="mt-5 text-sm font-medium text-emerald-700">{{ $resource['cta'] }} →</div>
                </a>
            @endforeach
        </div>
    </section>
@endif

<section id="pricing" class="scroll-mt-28 rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Pricing models</div>
            <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">All current plans are shown here, not just a freemium teaser.</h2>
        </div>
        <p class="max-w-2xl text-base leading-7 text-zinc-600">
            Pricing is workspace-based. Plans scale through user count, operational record volume, integrations, support, branding, and enterprise controls.
        </p>
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-4">
        @foreach ($pricingPlans as $plan)
            <article class="rounded-[1.5rem] border {{ $plan['is_default'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-zinc-200 bg-zinc-50' }} p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs uppercase tracking-[0.24em] {{ $plan['is_default'] ? 'text-emerald-700' : 'text-zinc-400' }}">{{ $plan['name'] }}</div>
                        <div class="mt-3 text-3xl font-semibold text-zinc-950">{{ $plan['price_label'] }}</div>
                    </div>
                    @if ($plan['is_default'])
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-emerald-700 shadow-sm">Default</span>
                    @endif
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    <div class="rounded-[1.1rem] border border-zinc-200 bg-white px-4 py-3">
                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Included users</div>
                        <div class="mt-2 text-lg font-semibold text-zinc-950">{{ $plan['included_users'] ? number_format($plan['included_users']) : 'Custom' }}</div>
                    </div>
                    <div class="rounded-[1.1rem] border border-zinc-200 bg-white px-4 py-3">
                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Operational records</div>
                        <div class="mt-2 text-lg font-semibold text-zinc-950">{{ $plan['included_operational_records'] ? number_format($plan['included_operational_records']) : 'Custom' }}</div>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    @foreach ($plan['highlights'] as $highlight)
                        <div class="rounded-2xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-600">{{ $highlight }}</div>
                    @endforeach
                </div>

                @if ($plan['extras'] !== [])
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($plan['extras'] as $extra)
                            <span class="rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-[11px] font-medium text-emerald-700">{{ $extra }}</span>
                        @endforeach
                    </div>
                @endif
            </article>
        @endforeach
    </div>

    <div class="mt-8 rounded-[1.6rem] border border-zinc-200 bg-zinc-50 p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="text-sm font-semibold text-zinc-950">What counts toward usage depends on the workspace mode.</div>
                <p class="mt-2 max-w-3xl text-sm leading-7 text-zinc-600">The pricing model follows the primary operational object for each business type so teams are measured on the work that matters in their mode.</p>
            </div>
            <div class="rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-600 shadow-sm">Mode-aware packaging</div>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($workspaceModes as $mode)
                <div class="rounded-[1.2rem] border border-zinc-200 bg-white p-4">
                    <div class="text-sm font-semibold text-zinc-950">{{ $mode['name'] }}</div>
                    <div class="mt-2 text-sm font-medium text-emerald-700">{{ $mode['usage_label'] }}</div>
                    <p class="mt-2 text-sm leading-6 text-zinc-500">{{ $mode['usage_description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

@if ($isMarketing)
    <section id="faqs" class="scroll-mt-28 rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="max-w-3xl">
            <div class="inline-flex rounded-2xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800">Frequently asked questions</div>
            <h2 class="mt-5 text-3xl font-semibold tracking-tight text-zinc-950">Buyer questions answered directly.</h2>
            <p class="mt-3 text-base leading-7 text-zinc-600">
                These FAQs reflect the current app, workspace templates, and plan structure so teams can evaluate rollout fit without reading between the lines.
            </p>
        </div>

        <div class="mt-8 space-y-3">
            @foreach ($faqItems as $faq)
                <details class="group rounded-[1.4rem] border border-zinc-200 bg-zinc-50 p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-base font-semibold text-zinc-950">
                        <span>{{ $faq['question'] }}</span>
                        <span class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-500 transition group-open:rotate-45">+</span>
                    </summary>
                    <p class="mt-4 max-w-4xl text-sm leading-7 text-zinc-600">{{ $faq['answer'] }}</p>
                </details>
            @endforeach
        </div>
    </section>
@endif

@if ($isMarketing)
        </div>
    </div>
@endif

@if ($isMarketing)
    <section class="rounded-[2rem] border border-emerald-200 bg-[linear-gradient(135deg,_#06281f,_#0f766e_58%,_#16a34a)] px-8 py-10 text-white shadow-2xl shadow-emerald-950/15">
        <div class="grid gap-8 lg:grid-cols-[1.35fr_0.65fr] lg:items-center">
            <div>
                <div class="text-sm uppercase tracking-[0.3em] text-emerald-100">Try it live</div>
                <h2 class="mt-4 text-4xl font-semibold tracking-tight">See the workflow before you commit to the rollout.</h2>
                <p class="mt-4 max-w-2xl text-lg leading-8 text-emerald-50">
                    Start with the live workspace, validate the operating model against your team, and move into the right pricing tier when usage and complexity justify it.
                </p>
            </div>
            <div class="flex flex-col gap-3">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-6 py-4 text-base font-medium text-zinc-950 transition hover:bg-emerald-50">
                    Start your journey
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/30 px-6 py-4 text-base font-medium text-white transition hover:bg-white/10">
                    Log In To Existing Workspace
                </a>
            </div>
        </div>
    </section>
@endif
