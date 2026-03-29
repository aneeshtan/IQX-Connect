<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'IQX Connect | Maritime CRM For Freight And Project Teams', 'forceLight' => true])
    </head>
    <body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.12),_transparent_24%),radial-gradient(circle_at_top_right,_rgba(14,165,233,0.12),_transparent_24%),linear-gradient(180deg,_#f4fbf8,_#f7fafc_42%,_#ffffff)] text-zinc-950">
        <div class="relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 -z-10 h-[32rem] bg-[radial-gradient(circle_at_center,_rgba(15,118,110,0.14),_transparent_58%)]"></div>

            <div class="mx-auto max-w-7xl px-6 pb-20 pt-6 lg:px-8">
                <header class="flex flex-col gap-5 rounded-[2rem] border border-emerald-100 bg-white/90 px-6 py-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,_#0f766e,_#16a34a)] text-white shadow-lg shadow-emerald-950/15">
                            <x-app-logo-icon class="size-7" />
                        </div>
                        <div>
                            <div class="text-lg font-semibold tracking-tight">IQX Connect</div>
                            <div class="text-sm text-zinc-500">Maritime CRM for operators, sales, and management</div>
                        </div>
                    </a>

                    <nav class="flex flex-wrap items-center gap-3 text-sm">
                        <a href="#features" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">Features</a>
                        <a href="#pricing" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">Pricing</a>
                        <a href="{{ route('product') }}" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">Resources</a>
                        <a href="{{ route('login') }}" class="rounded-full border border-zinc-200 px-4 py-2 font-medium text-zinc-700 transition hover:bg-zinc-50">Log In</a>
                    </nav>
                </header>

                <main class="mt-10 grid gap-8 lg:grid-cols-[1.08fr_0.92fr] lg:items-start">
                    <section class="space-y-8">
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800">
                            Freemium plan: free for the first 100 operational records
                        </div>

                        <div class="space-y-5">
                            <h1 class="max-w-4xl text-5xl font-semibold tracking-tight text-zinc-950 sm:text-6xl lg:text-7xl">
                                Maritime CRM built for freight, projects, customers, and execution.
                            </h1>
                            <p class="max-w-2xl text-lg leading-8 text-zinc-600">
                                IQX Connect gives maritime businesses one clean workspace for leads, accounts, quotes, bookings, shipment jobs, project delivery, costing, invoices, collaboration, and reporting.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <article class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-sm">
                                <div class="text-xs uppercase tracking-[0.25em] text-zinc-400">Workspace Modes</div>
                                <div class="mt-3 text-3xl font-semibold text-zinc-950">7</div>
                                <p class="mt-2 text-sm leading-6 text-zinc-500">Freight forwarding, container conversion, ship chandling, liner, ship management, leasing, and general maritime.</p>
                            </article>
                            <article class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-sm">
                                <div class="text-xs uppercase tracking-[0.25em] text-zinc-400">Operating Flow</div>
                                <div class="mt-3 text-3xl font-semibold text-zinc-950">Lead To Job</div>
                                <p class="mt-2 text-sm leading-6 text-zinc-500">Move from lead to opportunity, quote, booking, shipment, project, costing, and invoice without leaving the workspace.</p>
                            </article>
                            <article class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-sm">
                                <div class="text-xs uppercase tracking-[0.25em] text-zinc-400">Visibility</div>
                                <div class="mt-3 text-3xl font-semibold text-zinc-950">Live</div>
                                <p class="mt-2 text-sm leading-6 text-zinc-500">See customer history, team notes, costs, delivery status, and analytics from one mobile-friendly CRM surface.</p>
                            </article>
                        </div>
                    </section>

                    <section class="rounded-[2rem] border border-emerald-100 bg-white p-6 shadow-xl shadow-emerald-950/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs uppercase tracking-[0.25em] text-zinc-400">Get Started</div>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Start your journey</h2>
                            </div>
                            <div class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800">First 100 operational records free</div>
                        </div>

                        <form action="{{ route('register') }}" method="GET" class="mt-6 space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block">
                                    <span class="text-sm font-medium text-zinc-700">Full name</span>
                                    <input type="text" name="name" placeholder="Ava Marine Team" class="mt-2 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100">
                                </label>
                                <label class="block">
                                    <span class="text-sm font-medium text-zinc-700">Work email</span>
                                    <input type="email" name="email" placeholder="ops@company.com" class="mt-2 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100">
                                </label>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block">
                                    <span class="text-sm font-medium text-zinc-700">Company</span>
                                    <input type="text" name="company" placeholder="Bluewater Logistics" class="mt-2 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100">
                                </label>
                                <label class="block">
                                    <span class="text-sm font-medium text-zinc-700">Workspace mode</span>
                                    <select name="mode" class="mt-2 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100">
                                        <option>Freight Forwarder</option>
                                        <option>Container Conversion</option>
                                        <option>Ship Chandling</option>
                                        <option>Shipping Liner</option>
                                        <option>Ship Management</option>
                                        <option>Container Trading / Leasing</option>
                                        <option>General Maritime</option>
                                    </select>
                                </label>
                            </div>

                            <div class="rounded-[1.4rem] border border-zinc-200 bg-zinc-50 px-4 py-4">
                                <div class="text-sm font-semibold text-zinc-900">What you get in the free start</div>
                                <ul class="mt-3 grid gap-2 text-sm text-zinc-600 sm:grid-cols-2">
                                    <li>1 workspace and 3 users included</li>
                                    <li>First 100 operational records included</li>
                                    <li>Leads, opportunities, contacts, and customers</li>
                                    <li>Quotes, shipment jobs, or projects by mode</li>
                                    <li>CSV, Google Sheets, and API source options</li>
                                    <li>Reporting, notes, assignments, and notifications</li>
                                </ul>
                            </div>

                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-[linear-gradient(135deg,_#0f766e,_#16a34a)] px-6 py-4 text-base font-medium text-white shadow-lg shadow-emerald-950/20 transition hover:scale-[1.01]">
                                Start your journey
                            </button>
                        </form>
                    </section>
                </main>

                <section class="mt-20">
                    <div class="text-center text-xs font-medium uppercase tracking-[0.32em] text-zinc-400">Trusted by modern maritime businesses</div>
                    <div class="mt-8 grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        @foreach (['HarborGrid', 'OceanAxis', 'Portline Co.', 'Nauticore', 'BlueRidge Marine', 'ContainerCraft'] as $brand)
                            <div class="flex h-20 items-center justify-center rounded-[1.5rem] border border-zinc-200 bg-white/90 px-4 text-sm font-semibold text-zinc-500 shadow-sm">
                                {{ $brand }}
                            </div>
                        @endforeach
                    </div>
                </section>

                <section id="features" class="mt-24 rounded-[2rem] border border-zinc-200 bg-white p-7 shadow-sm">
                    <div class="max-w-3xl">
                        <div class="inline-flex rounded-2xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800">Why teams switch</div>
                        <h2 class="mt-5 text-4xl font-semibold tracking-tight text-zinc-950">Three things that matter most in a maritime CRM.</h2>
                        <p class="mt-3 text-base leading-7 text-zinc-600">
                            IQX Connect is designed around the problems maritime teams actually face: different business models, heavy list work, and painful migrations from spreadsheets or fragmented tools.
                        </p>
                    </div>

                    <div class="mt-8">
                        <div class="ios-tab-strip">
                            <button type="button" class="ios-tab-pill ios-tab-pill-active" data-guide-tab="modes">Workspace Modes</button>
                            <button type="button" class="ios-tab-pill" data-guide-tab="lists">Easy Listing Views</button>
                            <button type="button" class="ios-tab-pill" data-guide-tab="migrations">Full Migrations and Integrations</button>
                        </div>

                        <div class="mt-8 grid gap-6 lg:grid-cols-[0.92fr_1.08fr]">
                            <div class="space-y-5" data-guide-panel="modes">
                                <div class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-5">
                                    <div class="text-sm font-semibold text-zinc-950">Run one CRM across different maritime business models.</div>
                                    <p class="mt-3 text-sm leading-7 text-zinc-600">Choose a workspace template at setup and the CRM activates the right modules, labels, and workflows for freight forwarding, container conversion, ship chandling, shipping liner, ship management, leasing, or general maritime.</p>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-[1.35rem] border border-zinc-200 bg-white p-4">
                                        <div class="text-sm font-semibold text-zinc-950">Freight Forwarder</div>
                                        <p class="mt-2 text-sm text-zinc-500">Rates, quotes, bookings, shipments, costing, and invoices.</p>
                                    </div>
                                    <div class="rounded-[1.35rem] border border-zinc-200 bg-white p-4">
                                        <div class="text-sm font-semibold text-zinc-950">Container Conversion</div>
                                        <p class="mt-2 text-sm text-zinc-500">Projects, drawings, delivery milestones, and customer-led project flow.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[1.8rem] border border-zinc-200 bg-[linear-gradient(180deg,_#0b1720,_#101826_55%,_#0f1f1a)] p-6 text-white shadow-inner" data-guide-panel-card="modes">
                                <div class="text-xs uppercase tracking-[0.28em] text-emerald-200/70">Template-driven setup</div>
                                <div class="mt-4 text-3xl font-semibold">One platform. Multiple maritime operating models.</div>
                                <div class="mt-5 grid gap-3 text-sm text-zinc-200/90 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Mode-specific modules appear automatically for each workspace.</div>
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Owners keep consistent CRM behavior while each team sees the right workflow.</div>
                                </div>
                            </div>

                            <div class="hidden space-y-5" data-guide-panel="lists">
                                <div class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-5">
                                    <div class="text-sm font-semibold text-zinc-950">Built for teams that live in lists all day.</div>
                                    <p class="mt-3 text-sm leading-7 text-zinc-600">The CRM keeps the main work on one page with searchable, sortable, paginated list views, quick popups, inline status changes, exports, and clean mobile cards when teams work on the move.</p>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-[1.35rem] border border-zinc-200 bg-white p-4">
                                        <div class="text-sm font-semibold text-zinc-950">Inline actions</div>
                                        <p class="mt-2 text-sm text-zinc-500">Update statuses, assign sales, add notes, and open full record context from the same list view.</p>
                                    </div>
                                    <div class="rounded-[1.35rem] border border-zinc-200 bg-white p-4">
                                        <div class="text-sm font-semibold text-zinc-950">Popup detail model</div>
                                        <p class="mt-2 text-sm text-zinc-500">Keep the spreadsheet-style workflow without forcing users into separate pages for every small action.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="hidden rounded-[1.8rem] border border-zinc-200 bg-[linear-gradient(180deg,_#0c1728,_#111827_58%,_#14213a)] p-6 text-white shadow-inner" data-guide-panel-card="lists">
                                <div class="text-xs uppercase tracking-[0.28em] text-sky-200/70">Operator-first UX</div>
                                <div class="mt-4 text-3xl font-semibold">Easy listing views that stay fast under real work.</div>
                                <div class="mt-5 grid gap-3 text-sm text-zinc-200/90 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Quick list switching across leads, opportunities, customers, quotes, shipments, or projects.</div>
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">One workspace surface for sales, ops, and finance context.</div>
                                </div>
                            </div>

                            <div class="hidden space-y-5" data-guide-panel="migrations">
                                <div class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-5">
                                    <div class="text-sm font-semibold text-zinc-950">Move from spreadsheets and disconnected tools without replatforming the whole business.</div>
                                    <p class="mt-3 text-sm leading-7 text-zinc-600">Import from CSV, connect public or authenticated Google Sheets, or configure API-based sources including CargoWise-style endpoints. Workspace admins can bring records in gradually while keeping teams live.</p>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-[1.35rem] border border-zinc-200 bg-white p-4">
                                        <div class="text-sm font-semibold text-zinc-950">Migration-ready</div>
                                        <p class="mt-2 text-sm text-zinc-500">Support for list imports, source mapping, and workspace-specific vocabularies.</p>
                                    </div>
                                    <div class="rounded-[1.35rem] border border-zinc-200 bg-white p-4">
                                        <div class="text-sm font-semibold text-zinc-950">Integration-ready</div>
                                        <p class="mt-2 text-sm text-zinc-500">Google Sheets, CSV uploads, and configurable APIs for live operational feeds.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="hidden rounded-[1.8rem] border border-zinc-200 bg-[linear-gradient(180deg,_#1b1426,_#111827_52%,_#0f1f1a)] p-6 text-white shadow-inner" data-guide-panel-card="migrations">
                                <div class="text-xs uppercase tracking-[0.28em] text-violet-200/70">Migration + integration</div>
                                <div class="mt-4 text-3xl font-semibold">Bring the data in before you replace everything else.</div>
                                <div class="mt-5 grid gap-3 text-sm text-zinc-200/90 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Use live sources now and expand the operating model over time.</div>
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">A practical migration path for maritime companies coming from Google Sheets and email-driven workflows.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-24">
                    <div class="max-w-3xl">
                        <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Testimonials</div>
                        <h2 class="mt-5 text-4xl font-semibold tracking-tight text-zinc-950">Built for teams that need clarity, not CRM overhead.</h2>
                    </div>

                    <div class="mt-8 grid gap-6 lg:grid-cols-3">
                        @foreach ([
                            ['quote' => 'We moved from scattered Google Sheets into one workspace and immediately cleaned up handoffs between marketing, sales, and operations.', 'name' => 'Maya D.', 'role' => 'Commercial Director, Portline Projects'],
                            ['quote' => 'The list views feel simple enough for operators, but the linked costing and booking flow gives management the control they actually need.', 'name' => 'Omar R.', 'role' => 'Managing Partner, HarborAxis Logistics'],
                            ['quote' => 'The workspace mode concept is the difference. Freight and conversion teams do not have to force the same CRM process onto completely different businesses.', 'name' => 'Nadia S.', 'role' => 'CEO, BlueDock Marine Services'],
                        ] as $testimonial)
                            <article class="rounded-[1.8rem] border border-zinc-200 bg-white p-6 shadow-sm">
                                <div class="text-lg leading-8 text-zinc-700">“{{ $testimonial['quote'] }}”</div>
                                <div class="mt-6 border-t border-zinc-200 pt-4">
                                    <div class="text-sm font-semibold text-zinc-950">{{ $testimonial['name'] }}</div>
                                    <div class="text-sm text-zinc-500">{{ $testimonial['role'] }}</div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="pricing" class="mt-24 rounded-[2rem] border border-zinc-200 bg-white p-7 shadow-sm">
                    <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
                        <div>
                            <div class="inline-flex rounded-2xl bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-800">Pricing and features</div>
                            <h2 class="mt-5 text-4xl font-semibold tracking-tight text-zinc-950">Start free. Upgrade only when the workflow is proven.</h2>
                            <p class="mt-3 text-base leading-7 text-zinc-600">
                                IQX Connect uses a hybrid pricing model: workspace plan, included users, and included operational volume. Teams can start free, then scale by seat count and template-aware operational records as the workspace becomes part of daily execution.
                            </p>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <article class="rounded-[1.6rem] border border-emerald-200 bg-emerald-50/70 p-5">
                                <div class="text-xs uppercase tracking-[0.24em] text-emerald-700">Freemium</div>
                                <div class="mt-3 text-4xl font-semibold text-zinc-950">Free</div>
                                <div class="mt-2 text-sm text-zinc-600">1 workspace, 3 users, and first 100 operational records included</div>
                                <ul class="mt-5 space-y-2 text-sm leading-6 text-zinc-700">
                                    <li>Core CRM and collaboration</li>
                                    <li>Template-based workspace setup</li>
                                    <li>Basic integrations and reports</li>
                                    <li>Best for early adoption and trials</li>
                                </ul>
                            </article>

                            <article class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50 p-5">
                                <div class="text-xs uppercase tracking-[0.24em] text-zinc-500">Growth</div>
                                <div class="mt-3 text-4xl font-semibold text-zinc-950">$149</div>
                                <div class="mt-2 text-sm text-zinc-600">Per workspace / month with 5 users and 500 operational records included</div>
                                <ul class="mt-5 space-y-2 text-sm leading-6 text-zinc-700">
                                    <li>Google Sheets, CSV, and standard APIs</li>
                                    <li>Exports, collaboration, notifications</li>
                                    <li>Extra users and volume can scale</li>
                                    <li>Built for active operating teams</li>
                                </ul>
                            </article>

                            <article class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50 p-5">
                                <div class="text-xs uppercase tracking-[0.24em] text-zinc-500">Professional</div>
                                <div class="mt-3 text-4xl font-semibold text-zinc-950">$399</div>
                                <div class="mt-2 text-sm text-zinc-600">Per workspace / month with 10 users and 1,500 operational records included</div>
                                <ul class="mt-5 space-y-2 text-sm leading-6 text-zinc-700">
                                    <li>Advanced workflow and finance modules</li>
                                    <li>Segmentation and deeper controls</li>
                                    <li>Premium support and migration help</li>
                                    <li>For teams running IQX daily</li>
                                </ul>
                            </article>

                            <article class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50 p-5">
                                <div class="text-xs uppercase tracking-[0.24em] text-zinc-500">Enterprise</div>
                                <div class="mt-3 text-4xl font-semibold text-zinc-950">Custom</div>
                                <div class="mt-2 text-sm text-zinc-600">Custom seats, volume, rollout, and security requirements</div>
                                <ul class="mt-5 space-y-2 text-sm leading-6 text-zinc-700">
                                    <li>SSO, SLA, and enterprise governance</li>
                                    <li>Custom integrations and onboarding</li>
                                    <li>Multi-workspace rollout support</li>
                                    <li>For larger maritime groups</li>
                                </ul>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="mt-24 rounded-[2rem] border border-zinc-200 bg-white p-7 shadow-sm">
                    <div class="max-w-3xl">
                        <div class="inline-flex rounded-2xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800">Frequently asked questions</div>
                        <h2 class="mt-5 text-4xl font-semibold tracking-tight text-zinc-950">Questions teams ask before they switch.</h2>
                    </div>

                    <div class="mt-8 space-y-4">
                        @foreach ([
                            ['q' => 'Can IQX Connect work for different maritime business models?', 'a' => 'Yes. Workspace owners choose the business mode when creating a workspace. That activates the right modules and language for freight forwarding, container conversion, ship chandling, shipping liner, ship management, leasing, or general maritime.'],
                            ['q' => 'Can we migrate from spreadsheets or Google Sheets?', 'a' => 'Yes. The platform supports CSV uploads, Google Sheets sources, and configurable APIs so teams can migrate gradually instead of stopping operations for a full replacement project.'],
                            ['q' => 'Does the CRM support both commercial and operational workflows?', 'a' => 'Yes. IQX Connect links customer-facing CRM workflows with quotes, bookings, shipment jobs, projects, costing, invoices, collaboration, and analytics in the same workspace.'],
                            ['q' => 'Is there a free plan?', 'a' => 'Yes. IQX Connect is freemium. Teams can use one workspace with 3 users and the first 100 operational records for free before moving to a paid plan.'],
                        ] as $faq)
                            <details class="group rounded-[1.4rem] border border-zinc-200 bg-zinc-50 px-5 py-4">
                                <summary class="cursor-pointer list-none text-base font-semibold text-zinc-950">{{ $faq['q'] }}</summary>
                                <p class="mt-3 max-w-4xl text-sm leading-7 text-zinc-600">{{ $faq['a'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </section>

                @include('partials.site-footer', [
                    'class' => 'mt-10 border-t border-zinc-200/80 px-2 py-6 text-center',
                ])
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const tabs = document.querySelectorAll('[data-guide-tab]');
                const contentPanels = document.querySelectorAll('[data-guide-panel]');
                const cardPanels = document.querySelectorAll('[data-guide-panel-card]');

                tabs.forEach((tab) => {
                    tab.addEventListener('click', () => {
                        const key = tab.dataset.guideTab;

                        tabs.forEach((button) => {
                            button.classList.remove('ios-tab-pill-active');
                        });

                        contentPanels.forEach((panel) => {
                            panel.classList.toggle('hidden', panel.dataset.guidePanel !== key);
                        });

                        cardPanels.forEach((panel) => {
                            panel.classList.toggle('hidden', panel.dataset.guidePanelCard !== key);
                        });

                        tab.classList.add('ios-tab-pill-active');
                    });
                });
            });
        </script>
    </body>
</html>
