<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'IQX Connect | Maritime CRM For Freight And Project Teams', 'forceLight' => true])
    </head>
    <body class="min-h-screen bg-[linear-gradient(180deg,_#040816_0,_#0b1432_34rem,_#f7fafc_34rem,_#ffffff_100%)] text-zinc-950">
        <div class="relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 -z-10 h-[44rem] bg-[radial-gradient(circle_at_top,_rgba(96,165,250,0.2),_transparent_34%),radial-gradient(circle_at_18%_18%,_rgba(16,185,129,0.16),_transparent_22%),radial-gradient(circle_at_82%_10%,_rgba(251,191,36,0.12),_transparent_18%),linear-gradient(180deg,_#040816,_rgba(11,20,50,0.92)_75%,_transparent)]"></div>

            <div class="w-full pt-0">
                <section class="iqx-hero-shell relative isolate overflow-hidden px-5 pt-5 shadow-[0_30px_120px_rgba(2,6,23,0.52)] sm:px-8 lg:px-10">
                    <div class="absolute inset-x-12 top-0 h-44 rounded-b-full bg-[radial-gradient(circle_at_center,_rgba(255,255,255,0.18),_transparent_70%)] blur-3xl"></div>
                    <div class="absolute -left-16 top-24 h-48 w-48 rounded-full bg-emerald-400/14 blur-3xl"></div>
                    <div class="absolute -right-10 top-16 h-56 w-56 rounded-full bg-sky-400/18 blur-3xl"></div>

                    <header class="relative z-10 mx-auto flex max-w-7xl flex-col gap-5 rounded-[1.85rem] border border-white/10 bg-white/6 px-5 py-4 backdrop-blur-xl lg:flex-row lg:items-center lg:justify-between">
                        <a href="{{ route('home') }}" class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,_#34d399,_#60a5fa)] text-slate-950 shadow-lg shadow-sky-950/30">
                                <x-app-logo-icon class="size-7" />
                            </div>
                            <div>
                                <div class="text-lg font-semibold tracking-tight text-white">IQX Connect</div>
                                <div class="text-sm text-slate-300">Maritime CRM for operators, sales, and management</div>
                            </div>
                        </a>

                        <nav class="flex flex-wrap items-center gap-3 text-sm">
                            <a href="#features" class="rounded-full px-4 py-2 text-slate-300 transition hover:bg-white/10 hover:text-white">Features</a>
                            <a href="#pricing" class="rounded-full px-4 py-2 text-slate-300 transition hover:bg-white/10 hover:text-white">Pricing</a>
                            <a href="{{ route('product') }}" class="rounded-full px-4 py-2 text-slate-300 transition hover:bg-white/10 hover:text-white">Resources</a>
                            <a href="{{ route('login') }}" class="rounded-full border border-white/12 bg-white/6 px-4 py-2 font-medium text-white transition hover:bg-white/12">Log In</a>
                        </nav>
                    </header>

                    <main class="relative z-10 mx-auto max-w-7xl pt-14">
                        <section class="mx-auto max-w-4xl text-center">
                            <div class="inline-flex items-center gap-2 rounded-full border border-sky-200/20 bg-white/8 px-4 py-2 text-sm font-medium text-sky-100 shadow-[0_0_0_1px_rgba(255,255,255,0.04)]">
                                <span class="text-base">✨</span>
                                Free for your first 100 operational records
                            </div>

                            <div class="mt-8 space-y-6">
                                <h1 class="text-5xl font-semibold tracking-tight text-white sm:text-6xl lg:text-7xl">
                                    One maritime CRM where sales, ops, and delivery move together.
                                </h1>
                                <p class="mx-auto max-w-2xl text-lg leading-8 text-slate-300">
                                    Keep leads, quotes, bookings, shipment jobs, project delivery, costing, invoices, and team updates in one clear workflow built for modern maritime teams.
                                </p>
                            </div>

                            <form action="{{ route('register') }}" method="GET" class="mx-auto mt-8 max-w-4xl">
                                <div class="flex flex-col gap-3 rounded-[1.8rem] border border-white/10 bg-white/8 p-3 shadow-[0_18px_60px_rgba(15,23,42,0.35)] backdrop-blur-xl lg:flex-row">
                                    <label class="block flex-1">
                                        <span class="sr-only">Work email</span>
                                        <div class="flex min-h-[3.75rem] items-center gap-3 rounded-[1.2rem] border border-white/10 bg-white/92 px-4 py-4 text-left shadow-inner shadow-white/10">
                                            <span class="text-xl">📬</span>
                                            <input type="email" name="email" placeholder="Enter your work email" class="w-full bg-transparent text-base text-slate-950 outline-none placeholder:text-slate-500">
                                        </div>
                                    </label>

                                    <button type="submit" class="inline-flex min-h-[3.75rem] items-center justify-center rounded-[1.2rem] bg-[linear-gradient(135deg,_#34d399,_#60a5fa)] px-6 text-base font-semibold text-slate-950 shadow-lg shadow-emerald-500/20 transition hover:-translate-y-0.5">
                                        Start free
                                    </button>

                                    <a href="#features" class="inline-flex min-h-[3.75rem] items-center justify-center rounded-[1.2rem] border border-white/15 bg-slate-950/25 px-6 text-base font-semibold text-white transition hover:bg-white/10">
                                        Explore features
                                    </a>
                                </div>
                            </form>

                            <div class="mt-4 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm text-slate-300">
                                <span>3 users included</span>
                                <span class="hidden text-white/25 sm:inline">•</span>
                                <span>CRM + operations in one workspace</span>
                                <span class="hidden text-white/25 sm:inline">•</span>
                                <span>No long setup required</span>
                            </div>
                        </section>

                        <section class="relative mx-auto mt-12 max-w-5xl">
                            <div class="relative h-[14rem] overflow-hidden sm:h-[16rem]">
                                <div class="iqx-hero-bottom-glow absolute bottom-[-4rem] left-1/2 h-[15rem] w-[24rem] -translate-x-1/2 rounded-full"></div>
                                <div class="iqx-hero-bottom-glow absolute bottom-[-6rem] left-1/2 h-[10rem] w-[36rem] -translate-x-1/2 rounded-full opacity-70"></div>

                                @foreach ([
                                    ['emoji' => '🚢', 'classes' => 'left-[33%] top-[34%] sm:left-[39%] sm:top-[28%]', 'delay' => '-1s', 'duration' => '8.5s'],
                                    ['emoji' => '✨', 'classes' => 'left-1/2 top-[20%] -translate-x-1/2 sm:top-[16%]', 'delay' => '-3s', 'duration' => '10.5s'],
                                    ['emoji' => '⚓', 'classes' => 'right-[33%] top-[36%] sm:right-[39%] sm:top-[30%]', 'delay' => '-2s', 'duration' => '9.25s'],
                                ] as $orbitEmoji)
                                    <span
                                        class="iqx-hero-emoji iqx-hero-float absolute {{ $orbitEmoji['classes'] }}"
                                        style="--float-delay: {{ $orbitEmoji['delay'] }}; --float-duration: {{ $orbitEmoji['duration'] }};"
                                    >
                                        {{ $orbitEmoji['emoji'] }}
                                    </span>
                                @endforeach
                            </div>
                        </section>
                    </main>
                </section>

                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <section class="mt-0 pt-12 sm:pt-14">
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
