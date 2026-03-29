<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'IQX Connect | Marketing Presentation'])
        <style>
            @page {
                size: A4 landscape;
                margin: 0;
            }

            @media print {
                body {
                    background: #ffffff !important;
                }

                .presentation-shell {
                    padding: 0 !important;
                }

                .presentation-slide {
                    break-after: page;
                    box-shadow: none !important;
                    border-radius: 0 !important;
                }

                .print-hide {
                    display: none !important;
                }
            }
        </style>
    </head>
    <body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(13,148,136,0.14),_transparent_26%),radial-gradient(circle_at_top_right,_rgba(59,130,246,0.10),_transparent_24%),linear-gradient(180deg,_#eefbf6,_#f8fafc_35%,_#ffffff)] text-zinc-950">
        <div class="presentation-shell mx-auto max-w-[1600px] px-6 py-6 lg:px-8">
            <div class="mb-4 flex items-center justify-between print-hide">
                <a href="{{ route('home') }}" class="text-sm font-medium text-zinc-500 transition hover:text-zinc-950">Back to site</a>
                <div class="flex items-center gap-2">
                    <a href="{{ asset('marketing/IQX-Connect-Marketing-Presentation.pdf') }}" class="rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50" target="_blank" rel="noreferrer">Download PDF</a>
                    <a href="{{ route('product') }}" class="rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">Product Guide</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-zinc-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800">Try For Free</a>
                </div>
            </div>

            @php
                $slides = [
                    [
                        'eyebrow' => 'Maritime CRM and execution',
                        'title' => 'IQX Connect helps maritime teams move from lead to job in one workspace.',
                        'copy' => 'A single operating surface for freight forwarding, ship chandling, shipping liners, ship management, container trading, conversion projects, and general maritime sales.',
                        'stats' => [
                            ['label' => 'Workspace modes', 'value' => '7'],
                            ['label' => 'Core flow', 'value' => 'Lead → Quote → Job'],
                            ['label' => 'Reporting', 'value' => 'Live'],
                            ['label' => 'Integrations', 'value' => 'Sheets + APIs'],
                        ],
                    ],
                    [
                        'eyebrow' => 'Commercial snapshot',
                        'title' => 'Lead management, quotes, rates, bookings, and shipments stay connected.',
                        'copy' => 'Sales works from a spreadsheet-simple CRM while operations get the deeper freight objects they need to execute. The same account can carry every related record.',
                    ],
                    [
                        'eyebrow' => 'Operations snapshot',
                        'title' => 'Shipment jobs, milestones, documents, costing, and invoices live on the same timeline.',
                        'copy' => 'CargoWise-like operational depth without forcing a heavy ERP experience on the whole team.',
                    ],
                    [
                        'eyebrow' => 'Reporting snapshot',
                        'title' => 'Benchmark-style analytics show performance by time window, segment, and source.',
                        'copy' => 'Managers can read last month, rolling windows, and specific months, then compare deal flow, revenue, and customer health.',
                    ],
                    [
                        'eyebrow' => 'Benefits snapshot',
                        'title' => 'The platform is built to simplify the work while keeping the business in control.',
                        'copy' => 'Owners keep access, vocabulary, templates, and notifications under control. Teams get a CRM that is fast to understand and easy to adopt.',
                    ],
                ];
            @endphp

            @foreach ($slides as $index => $slide)
                <section class="presentation-slide mb-6 overflow-hidden rounded-[2.2rem] border border-zinc-200 bg-white shadow-[0_20px_70px_rgba(15,23,42,0.08)]">
                    <div class="grid min-h-[calc(100vh-7rem)] gap-0 lg:grid-cols-[1.05fr_0.95fr]">
                        <div class="flex flex-col justify-between p-8 lg:p-12">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-700">{{ $slide['eyebrow'] }}</div>
                                <h1 class="mt-4 max-w-3xl text-4xl font-semibold tracking-tight text-zinc-950 lg:text-5xl">
                                    {{ $slide['title'] }}
                                </h1>
                                <p class="mt-5 max-w-2xl text-lg leading-8 text-zinc-600">
                                    {{ $slide['copy'] }}
                                </p>
                            </div>

                            @if ($index === 0)
                                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                    @foreach ($slide['stats'] as $stat)
                                        <div class="rounded-[1.4rem] border border-zinc-200 bg-zinc-50 p-4">
                                            <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">{{ $stat['label'] }}</div>
                                            <div class="mt-3 text-3xl font-semibold text-zinc-950">{{ $stat['value'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex flex-wrap gap-2 text-xs font-medium text-zinc-500">
                                @foreach (['Leads', 'Opportunities', 'Contacts', 'Customers', 'Quotes', 'Shipments', 'Bookings', 'Job Costing', 'Invoices'] as $tag)
                                    <span class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="border-l border-zinc-200 bg-[linear-gradient(180deg,_#07111f,_#0f172a_52%,_#102235)] p-5 lg:p-8">
                            <div class="h-full rounded-[1.8rem] border border-white/10 bg-white/5 p-4 shadow-inner shadow-black/20">
                                @if ($index === 0)
                                    <div class="mb-4 rounded-[1.4rem] border border-emerald-100 bg-[linear-gradient(135deg,_#0f766e,_#16a34a)] p-5 text-white">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-xs uppercase tracking-[0.3em] text-emerald-50/80">Dashboard Snapshot</div>
                                                <div class="mt-2 text-2xl font-semibold">Wefreight Dashboard</div>
                                            </div>
                                            <div class="rounded-full bg-white/15 px-3 py-1 text-xs font-medium">Live</div>
                                        </div>
                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-[1.1rem] bg-white/10 p-4">
                                                <div class="text-xs uppercase tracking-[0.2em] text-emerald-50/70">Sales Qualified</div>
                                                <div class="mt-2 text-3xl font-semibold">5</div>
                                            </div>
                                            <div class="rounded-[1.1rem] bg-white/10 p-4">
                                                <div class="text-xs uppercase tracking-[0.2em] text-emerald-50/70">Open Opportunities</div>
                                                <div class="mt-2 text-3xl font-semibold">3</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        @foreach ([
                                            ['label' => 'Leads', 'value' => '1,670', 'tone' => 'from sheets and live sync'],
                                            ['label' => 'Closed Won Revenue', 'value' => 'AED 0', 'tone' => 'tracked by jobs and invoices'],
                                            ['label' => 'Quotes', 'value' => '3', 'tone' => 'lane-based pricing'],
                                            ['label' => 'Shipments', 'value' => '3', 'tone' => 'milestones and docs'],
                                        ] as $card)
                                            <div class="rounded-[1.2rem] border border-white/10 bg-white/7 p-4 text-white">
                                                <div class="text-xs uppercase tracking-[0.2em] text-white/60">{{ $card['label'] }}</div>
                                                <div class="mt-2 text-3xl font-semibold">{{ $card['value'] }}</div>
                                                <div class="mt-1 text-sm text-white/70">{{ $card['tone'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif ($index === 1)
                                    <div class="rounded-[1.4rem] border border-white/10 bg-white/6 p-4 text-white">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/60">Lead pipeline</div>
                                                <div class="mt-2 text-xl font-semibold">Quote Ready · In-progress · Qualified</div>
                                            </div>
                                            <div class="rounded-full bg-sky-400/15 px-3 py-1 text-xs font-medium text-sky-100">Sorted by newest</div>
                                        </div>
                                        <div class="mt-4 space-y-3">
                                            @foreach ([
                                                ['company' => 'Matteo Carria', 'service' => 'Freight Services', 'status' => 'Quote Ready'],
                                                ['company' => 'SERO SUPPLY', 'service' => 'Air Freight', 'status' => 'In-progress'],
                                                ['company' => 'Cana Bridal', 'service' => 'Road Freight', 'status' => 'Sales Qualified'],
                                            ] as $row)
                                                <div class="grid grid-cols-[1.2fr_0.9fr_auto] items-center gap-3 rounded-[1rem] border border-white/10 bg-white/6 px-4 py-3">
                                                    <div>
                                                        <div class="font-medium">{{ $row['company'] }}</div>
                                                        <div class="text-sm text-white/65">{{ $row['service'] }}</div>
                                                    </div>
                                                    <div class="text-sm text-white/75">Lead → Opportunity</div>
                                                    <div class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-medium text-emerald-100">{{ $row['status'] }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @elseif ($index === 2)
                                    <div class="rounded-[1.4rem] border border-white/10 bg-white/6 p-4 text-white">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/60">Shipment job</div>
                                                <div class="mt-2 text-xl font-semibold">Milestones, documents, and posting</div>
                                            </div>
                                            <div class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium">CargoWise-like flow</div>
                                        </div>
                                        <div class="mt-4 grid gap-3">
                                            @foreach ([
                                                'Booking Requested',
                                                'Booked With Carrier',
                                                'Departed Origin',
                                                'Arrived Destination',
                                                'Delivered',
                                            ] as $milestone)
                                                <div class="rounded-[1rem] border border-white/10 bg-white/6 px-4 py-3 text-sm text-white/85">{{ $milestone }}</div>
                                            @endforeach
                                        </div>
                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            @foreach (['House Bill', 'Master Bill', 'Commercial Invoice', 'Delivery Order'] as $doc)
                                                <div class="rounded-[1rem] border border-white/10 bg-white/6 px-4 py-3 text-sm text-white/85">{{ $doc }}</div>
                                            @endforeach
                                        </div>
                                    </div>
                                @elseif ($index === 3)
                                    <div class="rounded-[1.4rem] border border-white/10 bg-white/6 p-4 text-white">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/60">Reporting</div>
                                                <div class="mt-2 text-xl font-semibold">Benchmark-style dashboards</div>
                                            </div>
                                            <div class="rounded-full bg-sky-400/15 px-3 py-1 text-xs font-medium text-sky-100">Last month</div>
                                        </div>
                                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                            @foreach ([
                                                ['label' => 'SQLs', 'value' => '12'],
                                                ['label' => 'Won Deals', 'value' => '4'],
                                                ['label' => 'ROMI', 'value' => '12000%'],
                                            ] as $card)
                                                <div class="rounded-[1rem] border border-white/10 bg-white/6 p-4">
                                                    <div class="text-xs uppercase tracking-[0.2em] text-white/60">{{ $card['label'] }}</div>
                                                    <div class="mt-3 text-3xl font-semibold">{{ $card['value'] }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="mt-4 rounded-[1rem] border border-white/10 bg-white/6 p-4">
                                            <div class="h-40 rounded-[0.9rem] bg-[linear-gradient(180deg,_rgba(34,197,94,0.4),_rgba(15,23,42,0.15))]"></div>
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-[1.4rem] border border-white/10 bg-white/6 p-4 text-white">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/60">Benefits</div>
                                                <div class="mt-2 text-xl font-semibold">Simple for users, deep for operators</div>
                                            </div>
                                            <div class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium">Owner controls</div>
                                        </div>
                                        <div class="mt-4 grid gap-3">
                                            @foreach ([
                                                'Faster qualification',
                                                'Cleaner handoffs',
                                                'Better customer memory',
                                                'Decision-ready reporting',
                                            ] as $item)
                                                <div class="rounded-[1rem] border border-white/10 bg-white/6 px-4 py-3 text-sm text-white/85">{{ $item }}</div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>
            @endforeach

            <section class="presentation-slide overflow-hidden rounded-[2.2rem] border border-emerald-200 bg-[linear-gradient(135deg,_#06281f,_#0f766e_58%,_#16a34a)] px-10 py-10 text-white shadow-2xl shadow-emerald-950/15">
                <div class="grid gap-8 lg:grid-cols-[1.25fr_0.75fr] lg:items-center">
                    <div>
                        <div class="text-sm uppercase tracking-[0.3em] text-emerald-100">Try it live</div>
                        <h2 class="mt-4 text-4xl font-semibold tracking-tight">Sell the process, not the complexity.</h2>
                        <p class="mt-4 max-w-2xl text-lg leading-8 text-emerald-50">
                            Give maritime teams a workspace they can understand on day one, with the freight execution depth to grow into real operations.
                        </p>
                    </div>
                    <div class="rounded-[1.8rem] border border-white/15 bg-white/10 p-5">
                        <div class="text-sm uppercase tracking-[0.25em] text-emerald-50/75">Next steps</div>
                        <div class="mt-4 space-y-3">
                            <a href="{{ route('register') }}" class="block rounded-[1rem] bg-white px-4 py-3 text-center font-medium text-zinc-950">Try IQX Connect For Free</a>
                            <a href="{{ route('login') }}" class="block rounded-[1rem] border border-white/25 bg-white/10 px-4 py-3 text-center font-medium text-white">Log In To Demo Workspace</a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </body>
</html>
