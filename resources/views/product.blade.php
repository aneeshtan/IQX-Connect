<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'IQX Connect | Product Guide'])
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
                            <div class="text-sm text-zinc-500">Product guide for maritime teams</div>
                        </div>
                    </a>

                    <nav class="flex flex-wrap items-center gap-3 text-sm">
                        <a href="#overview" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">Overview</a>
                        <a href="#modules" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">Modules</a>
                        <a href="#benefits" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">Benefits</a>
                        <a href="#resources" class="rounded-full px-4 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">Resources</a>
                        <a href="{{ route('login') }}" class="rounded-full border border-zinc-200 px-4 py-2 font-medium text-zinc-700 transition hover:bg-zinc-50">Log In</a>
                        <a href="{{ route('register') }}" class="rounded-full bg-zinc-950 px-5 py-2 font-medium text-white transition hover:bg-zinc-800">Start your journey</a>
                    </nav>
                </header>

                <main class="mt-8">
                    @include('partials.product-documentation-content', ['docMode' => 'marketing'])
                </main>

                @include('partials.site-footer', [
                    'class' => 'mt-10 border-t border-zinc-200/80 px-2 py-6 text-center',
                ])
            </div>
        </div>
    </body>
</html>
