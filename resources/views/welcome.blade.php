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
                        <a href="{{ route('login') }}" class="rounded-full border border-zinc-200 px-4 py-2 font-medium text-zinc-700 transition hover:bg-zinc-50">Log In</a>
                        <a href="{{ route('register') }}" class="rounded-full bg-zinc-950 px-5 py-2 font-medium text-white transition hover:bg-zinc-800">Try For Free</a>
                    </nav>
                </header>

                <main class="mt-8 grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                    <section class="space-y-8">
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800">
                            Built for maritime sales, marketing, and operations teams
                        </div>

                        <div class="space-y-5">
                            <h1 class="max-w-4xl text-5xl font-semibold tracking-tight text-zinc-950 sm:text-6xl">
                                One-page CRM clarity for leads, pipeline, and reporting.
                            </h1>
                            <p class="max-w-2xl text-lg leading-8 text-zinc-600">
                                IQX Connect gives maritime teams a simple workspace to sync lead sources, update statuses inline, track conversions, and review benchmark-style reports without bouncing between tools.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-2xl bg-[linear-gradient(135deg,_#0f766e,_#16a34a)] px-6 py-4 text-base font-medium text-white shadow-lg shadow-emerald-950/20 transition hover:scale-[1.01]">
                                Start Free Trial
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-6 py-4 text-base font-medium text-zinc-700 transition hover:bg-zinc-50">
                                See The CRM
                            </a>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <article class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-sm">
                                <div class="text-xs uppercase tracking-[0.25em] text-zinc-400">Setup</div>
                                <div class="mt-3 text-2xl font-semibold text-zinc-950">Minutes</div>
                                <p class="mt-2 text-sm text-zinc-500">Connect Google Sheets, upload CSVs, or add leads manually from day one.</p>
                            </article>
                            <article class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-sm">
                                <div class="text-xs uppercase tracking-[0.25em] text-zinc-400">Workflow</div>
                                <div class="mt-3 text-2xl font-semibold text-zinc-950">One Page</div>
                                <p class="mt-2 text-sm text-zinc-500">Users switch tabs instead of changing pages to manage the pipeline.</p>
                            </article>
                            <article class="rounded-[1.5rem] border border-zinc-200 bg-white/90 p-4 shadow-sm">
                                <div class="text-xs uppercase tracking-[0.25em] text-zinc-400">Visibility</div>
                                <div class="mt-3 text-2xl font-semibold text-zinc-950">Live</div>
                                <p class="mt-2 text-sm text-zinc-500">Admins and users see real reporting windows, trend blocks, and revenue signals.</p>
                            </article>
                        </div>
                    </section>

                    <section class="relative">
                        <div class="absolute -left-8 top-8 h-28 w-28 rounded-full bg-emerald-200/50 blur-3xl"></div>
                        <div class="absolute -right-6 bottom-8 h-32 w-32 rounded-full bg-sky-200/60 blur-3xl"></div>
                        <div class="relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-5 shadow-xl shadow-emerald-950/10">
                            <div class="rounded-[1.7rem] bg-[linear-gradient(135deg,_#06281f,_#0f766e_58%,_#16a34a)] p-5 text-white">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.3em] text-emerald-100">Demo Preview</div>
                                        <div class="mt-2 text-2xl font-semibold">See IQX Connect in under two minutes</div>
                                    </div>
                                    <div class="rounded-2xl bg-white/10 px-3 py-2 text-sm">Placeholder Video</div>
                                </div>
                                <p class="mt-4 max-w-xl text-sm leading-7 text-emerald-50">
                                    Replace this block with your product demo later. For now it frames the CRM as a guided walkthrough of lead updates, opportunity movement, workspace setup, and reporting windows.
                                </p>
                            </div>

                            <div class="mt-5 rounded-[1.7rem] border border-zinc-200 bg-[linear-gradient(180deg,_#f8fafc,_#ffffff)] p-4">
                                <div class="relative overflow-hidden rounded-[1.45rem] border border-zinc-200 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_26%),linear-gradient(160deg,_#061b15,_#0f172a_48%,_#134e4a)] p-5 text-white shadow-inner">
                                    <div class="absolute inset-x-0 top-0 h-20 bg-[linear-gradient(180deg,_rgba(255,255,255,0.12),_transparent)]"></div>
                                    <div class="relative flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="h-3 w-3 rounded-full bg-rose-300/80"></span>
                                            <span class="h-3 w-3 rounded-full bg-amber-300/80"></span>
                                            <span class="h-3 w-3 rounded-full bg-emerald-300/80"></span>
                                        </div>
                                        <div class="rounded-full bg-white/10 px-3 py-1 text-xs uppercase tracking-[0.25em] text-emerald-100">
                                            Product Tour
                                        </div>
                                    </div>

                                    <div class="mt-8 grid gap-5 lg:grid-cols-[0.82fr_1.18fr] lg:items-center">
                                        <div class="space-y-4">
                                            <div class="rounded-[1.3rem] border border-white/10 bg-white/10 p-4 backdrop-blur">
                                                <div class="text-xs uppercase tracking-[0.28em] text-emerald-100">What the demo covers</div>
                                                <div class="mt-4 space-y-3 text-sm text-emerald-50">
                                                    <div class="rounded-xl bg-black/15 px-3 py-2">Lead and opportunity tabs in one workspace</div>
                                                    <div class="rounded-xl bg-black/15 px-3 py-2">Google Sheets sync and CSV imports</div>
                                                    <div class="rounded-xl bg-black/15 px-3 py-2">Time-bound reporting for users and admins</div>
                                                </div>
                                            </div>
                                            <div class="grid gap-3 sm:grid-cols-3">
                                                <div class="rounded-[1.2rem] border border-white/10 bg-white/10 p-3">
                                                    <div class="text-[11px] uppercase tracking-[0.25em] text-emerald-100">Format</div>
                                                    <div class="mt-2 text-lg font-semibold">Walkthrough</div>
                                                </div>
                                                <div class="rounded-[1.2rem] border border-white/10 bg-white/10 p-3">
                                                    <div class="text-[11px] uppercase tracking-[0.25em] text-emerald-100">Length</div>
                                                    <div class="mt-2 text-lg font-semibold">1:48</div>
                                                </div>
                                                <div class="rounded-[1.2rem] border border-white/10 bg-white/10 p-3">
                                                    <div class="text-[11px] uppercase tracking-[0.25em] text-emerald-100">CTA</div>
                                                    <div class="mt-2 text-lg font-semibold">Try Free</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-[1.5rem] border border-white/10 bg-black/15 p-4 backdrop-blur">
                                            <div class="relative aspect-video overflow-hidden rounded-[1.35rem] border border-white/10 bg-[radial-gradient(circle_at_center,_rgba(255,255,255,0.12),_transparent_22%),linear-gradient(145deg,_#111827,_#0b3b32_52%,_#065f46)]">
                                                <div class="absolute inset-0 bg-[linear-gradient(180deg,_rgba(255,255,255,0.04),_transparent_26%,_rgba(255,255,255,0.03))]"></div>
                                                <div class="absolute inset-x-5 top-5 flex items-center justify-between text-xs uppercase tracking-[0.28em] text-emerald-100">
                                                    <span>IQX Connect Demo</span>
                                                    <span>Placeholder</span>
                                                </div>
                                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-5 px-8 text-center">
                                                    <div class="flex h-20 w-20 items-center justify-center rounded-full border border-white/20 bg-white/10 shadow-lg shadow-black/20">
                                                        <div class="ml-1 h-0 w-0 border-y-[14px] border-l-[24px] border-y-transparent border-l-white"></div>
                                                    </div>
                                                    <div>
                                                        <div class="text-2xl font-semibold tracking-tight">Demo Video Placeholder</div>
                                                        <p class="mt-3 max-w-md text-sm leading-7 text-emerald-50/90">
                                                            Swap this frame with your hosted video or embedded player when the product demo is ready.
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="absolute inset-x-5 bottom-5 flex items-center justify-between text-sm text-emerald-50/90">
                                                    <span>01:48 product tour</span>
                                                    <span>Lead sync, pipeline, analytics</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <section id="features" class="mt-24 grid gap-6 lg:grid-cols-3">
                    <article class="rounded-[1.9rem] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="inline-flex rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Live Sources</div>
                        <h2 class="mt-5 text-2xl font-semibold tracking-tight">Connect lead sources without engineering overhead.</h2>
                        <p class="mt-3 text-base leading-7 text-zinc-600">Use authenticated Google Sheets, public sheet links, uploaded CSVs, and manual entries in the same CRM. Keep your team in one workspace while the data keeps moving.</p>
                    </article>
                    <article class="rounded-[1.9rem] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="inline-flex rounded-2xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800">Simple Workflow</div>
                        <h2 class="mt-5 text-2xl font-semibold tracking-tight">Spreadsheet-simple for users, structured enough for management.</h2>
                        <p class="mt-3 text-base leading-7 text-zinc-600">Switch between tabs, update lead and opportunity status inline, and avoid sending teams into a maze of forms and pages just to move a deal.</p>
                    </article>
                    <article id="reporting" class="rounded-[1.9rem] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="inline-flex rounded-2xl bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-800">Benchmark Reporting</div>
                        <h2 class="mt-5 text-2xl font-semibold tracking-tight">Show pipeline, conversions, ads efficiency, and ROMI in one story.</h2>
                        <p class="mt-3 text-base leading-7 text-zinc-600">Benchmark-style panels turn raw lead tables into decision-ready summaries for users and admins, with time windows that actually match the business conversation.</p>
                    </article>
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
