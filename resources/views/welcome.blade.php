<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'IQX Connect | Simple CRM For Maritime Teams'])
    </head>
    <body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(13,148,136,0.16),_transparent_26%),radial-gradient(circle_at_top_right,_rgba(34,197,94,0.14),_transparent_24%),linear-gradient(180deg,_#f3fbf8,_#f7fafc_38%,_#ffffff)] text-zinc-950">
        <div class="relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 -z-10 h-[34rem] bg-[radial-gradient(circle_at_center,_rgba(15,118,110,0.14),_transparent_52%)]"></div>
            <div class="mx-auto max-w-7xl px-6 pb-20 pt-6 lg:px-8">
                <header class="flex flex-col gap-5 rounded-[2rem] border border-emerald-100 bg-white/80 px-6 py-5 shadow-sm backdrop-blur lg:flex-row lg:items-center lg:justify-between">
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,_#0f766e,_#16a34a)] text-white shadow-lg shadow-emerald-950/15">
                            <x-app-logo-icon class="size-7" />
                        </div>
                        <div>
                            <div class="text-lg font-semibold tracking-tight">IQX Connect</div>
                            <div class="text-sm text-zinc-500">Maritime CRM and reporting</div>
                        </div>
                    </a>

                    <nav class="flex flex-wrap items-center gap-3 text-sm">
                        <a href="#features" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">Features</a>
                        <a href="#reporting" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">Reporting</a>
                        <a href="{{ route('presentation') }}" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">Presentation</a>
                        <a href="{{ route('product') }}" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">Product Guide</a>
                        <a href="{{ asset('marketing/IQX-Connect-Marketing-Presentation.pdf') }}" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950" target="_blank" rel="noreferrer">Download PDF</a>
                        <a href="{{ route('login') }}" class="rounded-full border border-zinc-200 px-4 py-2 font-medium text-zinc-700 transition hover:bg-zinc-50">Log In</a>
                        <a href="{{ route('register') }}" class="rounded-full bg-zinc-950 px-5 py-2 font-medium text-white transition hover:bg-zinc-800">Try For Free</a>
                    </nav>
                </header>

                <main class="mt-8 grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                    <section class="space-y-8">
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800">
                            Built for freight forwarders, shipping teams, and maritime operators
                        </div>

                        <div class="space-y-5">
                            <h1 class="max-w-4xl text-5xl font-semibold tracking-tight text-zinc-950 sm:text-6xl">
                                Maritime CRM, quoting, shipment jobs, and reporting in one workspace.
                            </h1>
                            <p class="max-w-2xl text-lg leading-8 text-zinc-600">
                                IQX Connect gives maritime companies one operating surface for inbound leads, customer accounts, quotes, bookings, shipment jobs, costing, invoices, and benchmark-style reporting without forcing teams into a bloated ERP workflow.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-2xl bg-[linear-gradient(135deg,_#0f766e,_#16a34a)] px-6 py-4 text-base font-medium text-white shadow-lg shadow-emerald-950/20 transition hover:scale-[1.01]">
                                Start Free Trial
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-6 py-4 text-base font-medium text-zinc-700 transition hover:bg-zinc-50">
                                See The CRM
                            </a>
                            <a href="{{ route('presentation') }}" class="inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 px-6 py-4 text-base font-medium text-sky-800 transition hover:bg-sky-100">
                                Open Presentation
                            </a>
                            <a href="{{ asset('marketing/IQX-Connect-Marketing-Presentation.pdf') }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-6 py-4 text-base font-medium text-zinc-700 transition hover:bg-zinc-50">
                                Download PDF
                            </a>
                            <a href="{{ route('product') }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-4 text-base font-medium text-emerald-800 transition hover:bg-emerald-100">
                                Read Product Guide
                            </a>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <article class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-sm">
                                <div class="text-xs uppercase tracking-[0.25em] text-zinc-400">Product Features</div>
                                <div class="mt-3 text-2xl font-semibold text-zinc-950">Lead To Job</div>
                                <p class="mt-2 text-sm text-zinc-500">Move from lead to opportunity, quote, booking, shipment, costing, and invoice inside one connected workflow.</p>
                            </article>
                            <article class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-sm">
                                <div class="text-xs uppercase tracking-[0.25em] text-zinc-400">Workspace Modes</div>
                                <div class="mt-3 text-2xl font-semibold text-zinc-950">7</div>
                                <p class="mt-2 text-sm text-zinc-500">Freight forwarding, ship chandling, container conversion, liner, ship management, leasing, and general maritime.</p>
                            </article>
                            <article class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-sm">
                                <div class="text-xs uppercase tracking-[0.25em] text-zinc-400">Business Benefits</div>
                                <div class="mt-3 text-2xl font-semibold text-zinc-950">Live</div>
                                <p class="mt-2 text-sm text-zinc-500">Track pipeline, shipment activity, booking-linked invoices, and margin signals from one mobile-friendly dashboard.</p>
                            </article>
                        </div>
                    </section>

                    <section class="relative">
                        <div class="absolute -left-8 top-8 h-28 w-28 rounded-full bg-emerald-200/50 blur-3xl"></div>
                        <div class="absolute -right-6 bottom-8 h-32 w-32 rounded-full bg-sky-200/60 blur-3xl"></div>
                        <div class="relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-5 shadow-xl shadow-emerald-950/10">
                            <div class="aspect-video overflow-hidden rounded-[1.7rem] border border-zinc-200 bg-zinc-950 shadow-inner">
                                <iframe
                                    class="h-full w-full"
                                    src="https://www.youtube.com/embed/Edj_zj4U_9s"
                                    title="IQX Connect demo video"
                                    loading="lazy"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen
                                ></iframe>
                            </div>
                        </div>
                    </section>
                </main>

                <section class="mt-24 grid gap-6 lg:grid-cols-[1fr_1fr]">
                    <article class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="inline-flex rounded-2xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800">Product Features</div>
                        <h2 class="mt-5 text-3xl font-semibold tracking-tight">Everything maritime teams need to work from one simple surface.</h2>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            @foreach ([
                                ['title' => 'CRM And Pipeline', 'desc' => 'Leads, opportunities, contacts, customers, notes, assignments, and messages.'],
                                ['title' => 'Quotes And Rates', 'desc' => 'Lane-based freight quoting, carrier rates, revisions, and auto-fill from sources.'],
                                ['title' => 'Shipment Jobs', 'desc' => 'Bookings, milestones, documents, shipment tracking, and operational timelines.'],
                                ['title' => 'Job Finance', 'desc' => 'Costing, AP/AR posting, invoice lines, and booking-linked billing.'],
                            ] as $card)
                                <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                    <div class="text-sm font-semibold text-zinc-950">{{ $card['title'] }}</div>
                                    <p class="mt-2 text-sm leading-6 text-zinc-500">{{ $card['desc'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <article class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Product Benefits</div>
                        <h2 class="mt-5 text-3xl font-semibold tracking-tight">Benefits that help the team move faster and manage better.</h2>
                        <div class="mt-6 space-y-4">
                            @foreach ([
                                ['title' => 'Faster qualification', 'desc' => 'Your team can see which leads are worth pursuing and which ones should be disqualified early.'],
                                ['title' => 'Cleaner handoffs', 'desc' => 'Sales, operations, and finance all work off the same customer and shipment record.'],
                                ['title' => 'Better customer memory', 'desc' => 'Account history, notes, and linked work stay visible across every deal.'],
                                ['title' => 'Decision-ready reporting', 'desc' => 'Managers can read revenue, margin, funnel, and customer health from one dashboard.'],
                            ] as $benefit)
                                <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                    <div class="text-sm font-semibold text-zinc-950">{{ $benefit['title'] }}</div>
                                    <p class="mt-2 text-sm leading-6 text-zinc-500">{{ $benefit['desc'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </article>
                </section>

                <section id="features" class="mt-24 grid gap-6 lg:grid-cols-[0.92fr_1.08fr]">
                    <article class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Workspace Templates</div>
                        <h2 class="mt-5 text-3xl font-semibold tracking-tight">Choose the maritime mode that fits the business.</h2>
                        <p class="mt-3 text-base leading-7 text-zinc-600">
                            IQX Connect is not a one-template CRM. Workspace owners can activate the operating mode that matches the company model, then expose the right modules, labels, and workflows for that team.
                        </p>
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Freight Forwarder</div>
                                <div class="mt-2 text-sm text-zinc-500">Quotes, bookings, shipment jobs, costing, invoices, carriers, and rates.</div>
                            </div>
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Ship Chandling</div>
                                <div class="mt-2 text-sm text-zinc-500">Customer demand tracking, supply orders, and vessel-call style workflows.</div>
                            </div>
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Container Conversion</div>
                                <div class="mt-2 text-sm text-zinc-500">Simpler pipeline for project-led sales and repeat commercial follow-up.</div>
                            </div>
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">General Maritime</div>
                                <div class="mt-2 text-sm text-zinc-500">Start with core CRM, then expand only the modules the team really needs.</div>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="inline-flex rounded-2xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800">Core Platform</div>
                        <h2 class="mt-5 text-3xl font-semibold tracking-tight">Built around the real freight and maritime workflow.</h2>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Lead And Opportunity CRM</div>
                                <p class="mt-2 text-sm leading-6 text-zinc-500">Spreadsheet-simple lead handling, opportunity conversion, inline stage updates, and owner-based workspace access.</p>
                            </div>
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Accounts And Contacts</div>
                                <p class="mt-2 text-sm leading-6 text-zinc-500">First-class customer accounts and contacts with linked history across leads, quotes, shipments, bookings, and invoices.</p>
                            </div>
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Quotes, Rates, And Carriers</div>
                                <p class="mt-2 text-sm leading-6 text-zinc-500">Build lane-based freight quotes, store sell and buy rates, manage carrier options, and prepare deals for execution.</p>
                            </div>
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Bookings And Shipment Jobs</div>
                                <p class="mt-2 text-sm leading-6 text-zinc-500">Move accepted work into bookings and operational shipment jobs with milestones, documents, and timeline tracking.</p>
                            </div>
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Job Costing And Invoicing</div>
                                <p class="mt-2 text-sm leading-6 text-zinc-500">Track margin, costing lines, AP/AR invoice types, posting status, and booking-linked or shipment-linked finance records.</p>
                            </div>
                            <div id="reporting" class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Time-Bound Analytics</div>
                                <p class="mt-2 text-sm leading-6 text-zinc-500">Review last month, rolling windows, or specific months with benchmark-style cards and charts for SQL, revenue, ROMI, and conversions.</p>
                            </div>
                        </div>
                    </article>
                </section>
                
                <section class="mt-24 grid gap-6 lg:grid-cols-3">
                    <article class="rounded-[1.9rem] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Live Integrations</div>
                        <h2 class="mt-5 text-2xl font-semibold tracking-tight">Connect operational data without waiting on IT.</h2>
                        <p class="mt-3 text-base leading-7 text-zinc-600">Use Google Sheets, public CSV-style sheet links, uploaded CSV files, manual entry, and configurable API sources including CargoWise-style endpoints.</p>
                    </article>
                    <article class="rounded-[1.9rem] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="inline-flex rounded-2xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800">Mobile Friendly</div>
                        <h2 class="mt-5 text-2xl font-semibold tracking-tight">Work on the move without losing context.</h2>
                        <p class="mt-3 text-base leading-7 text-zinc-600">The CRM is now shaped for mobile web-app use with responsive cards, bottom navigation, safe-area support, and quick popup-driven editing.</p>
                    </article>
                    <article class="rounded-[1.9rem] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="inline-flex rounded-2xl bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-800">Admin Control</div>
                        <h2 class="mt-5 text-2xl font-semibold tracking-tight">Let each workspace speak the company’s language.</h2>
                        <p class="mt-3 text-base leading-7 text-zinc-600">Workspace owners can control roles, source connections, templates, editable CRM labels, statuses, reasons, and maritime-specific vocabulary.</p>
                    </article>
                </section>

                <section class="mt-24 rounded-[2rem] border border-zinc-200 bg-white p-7 shadow-sm">
                    <div class="grid gap-8 lg:grid-cols-[0.92fr_1.08fr] lg:items-start">
                        <div>
                            <div class="inline-flex rounded-2xl bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-800">Benefits</div>
                            <h2 class="mt-5 text-3xl font-semibold tracking-tight">What maritime teams gain from using IQX Connect.</h2>
                            <p class="mt-3 max-w-xl text-base leading-7 text-zinc-600">
                                The platform is designed to reduce handoff friction between marketing, sales, and execution without dropping your team into a full CargoWise-scale operating system on day one.
                            </p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Faster Qualification</div>
                                <div class="mt-2 text-sm leading-6 text-zinc-500">Teams can see which leads are worth pursuing, convert qualified demand into opportunities, and move quickly into pricing.</div>
                            </div>
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Cleaner Handoffs</div>
                                <div class="mt-2 text-sm leading-6 text-zinc-500">Quotes, bookings, shipment jobs, costing, and invoicing all stay connected to the same customer and commercial record.</div>
                            </div>
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Better Customer Memory</div>
                                <div class="mt-2 text-sm leading-6 text-zinc-500">Accounts and contacts keep the relationship history visible instead of scattering it across leads, inboxes, and spreadsheets.</div>
                            </div>
                            <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                                <div class="text-sm font-semibold text-zinc-950">Decision-Ready Reporting</div>
                                <div class="mt-2 text-sm leading-6 text-zinc-500">Managers can read benchmark-style analytics across deal value, won business, spend efficiency, and operational follow-through.</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-24 rounded-[2.2rem] border border-emerald-200 bg-[linear-gradient(135deg,_#06281f,_#0f766e_58%,_#16a34a)] px-8 py-10 text-white shadow-2xl shadow-emerald-950/15">
                    <div class="grid gap-8 lg:grid-cols-[1.3fr_0.7fr] lg:items-center">
                        <div>
                            <div class="text-sm uppercase tracking-[0.3em] text-emerald-100">Start Free</div>
                            <h2 class="mt-4 text-4xl font-semibold tracking-tight">Sell the process, not the complexity.</h2>
                            <p class="mt-4 max-w-2xl text-lg leading-8 text-emerald-50">
                                Give your maritime team a CRM they can understand on the first day. IQX Connect is designed to feel familiar to operators while still giving leadership the reporting depth they expect.
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

                @include('partials.site-footer', [
                    'class' => 'mt-10 border-t border-zinc-200/80 px-2 py-6 text-center',
                ])
            </div>
        </div>
    </body>
</html>
