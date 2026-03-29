<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['forceLight' => true])
    </head>
    <body class="min-h-screen bg-white antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.15),_transparent_35%),linear-gradient(180deg,_#eff6ff,_#f8fafc)] p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="mb-1 flex h-11 w-11 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,_#0f766e,_#16a34a)] text-white shadow-lg shadow-emerald-950/20">
                        <x-app-logo-icon class="size-7 fill-current" />
                    </span>
                    <span class="text-sm font-semibold tracking-[0.18em] text-zinc-700">{{ config('app.name', 'IQX Connect') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>

                @include('partials.site-footer', [
                    'class' => 'pt-2 text-center',
                ])
            </div>
        </div>
        @fluxScripts
    </body>
</html>
