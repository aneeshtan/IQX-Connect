<x-layouts.app>
    <div class="mx-auto max-w-7xl px-4 py-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-[1.5rem] border border-zinc-200 bg-white/90 px-5 py-4 shadow-sm">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.25em] text-zinc-400">Workspace Guide</div>
                <div class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950">Product Documentation</div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard') }}" class="rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50" wire:navigate>Back To Dashboard</a>
                <a href="{{ route('product') }}" class="rounded-full bg-zinc-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800" target="_blank" rel="noreferrer">Public Product Guide</a>
                <a href="{{ asset('marketing/IQX-Connect-Marketing-Presentation.pdf') }}" class="rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50" target="_blank" rel="noreferrer">Download Presentation PDF</a>
            </div>
        </div>

        @include('partials.product-documentation-content', ['docMode' => 'app'])
    </div>
</x-layouts.app>
