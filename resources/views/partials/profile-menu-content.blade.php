@php
    $accentThemes = [
        ['key' => 'emerald', 'label' => 'Emerald', 'color' => '#10b981'],
        ['key' => 'sky', 'label' => 'Sky', 'color' => '#0ea5e9'],
        ['key' => 'lime', 'label' => 'Lime', 'color' => '#84cc16'],
        ['key' => 'amber', 'label' => 'Amber', 'color' => '#f59e0b'],
        ['key' => 'coral', 'label' => 'Coral', 'color' => '#f97316'],
        ['key' => 'rose', 'label' => 'Rose', 'color' => '#f43f5e'],
        ['key' => 'violet', 'label' => 'Violet', 'color' => '#8b5cf6'],
        ['key' => 'indigo', 'label' => 'Indigo', 'color' => '#6366f1'],
    ];
@endphp

<div class="w-[320px] max-w-[calc(100vw-2rem)] p-3">
    <div class="rounded-[1.4rem] border border-zinc-200/90 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:shadow-black/30">
        <div class="flex items-start gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-[rgb(var(--iqx-accent-soft-rgb))] text-base font-semibold text-[rgb(var(--iqx-accent-ink-rgb))]">
                {{ $user->initials() }}
            </span>

            <div class="min-w-0 flex-1">
                <div class="truncate text-base font-semibold text-zinc-950 dark:text-zinc-100">{{ $user->name }}</div>
                <div class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $user->email }}</div>
                @if (filled($companyName))
                    <div class="mt-1 truncate text-xs font-medium uppercase tracking-[0.2em] text-zinc-400 dark:text-zinc-500">{{ $companyName }}</div>
                @endif
            </div>
        </div>

        <div class="mt-4 grid gap-2 sm:grid-cols-2">
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/80 px-3 py-3 dark:border-zinc-800 dark:bg-zinc-900/80">
                <div class="text-[11px] font-medium uppercase tracking-[0.18em] text-zinc-400 dark:text-zinc-500">Role</div>
                <div class="mt-1 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $roleLabel }}</div>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/80 px-3 py-3 dark:border-zinc-800 dark:bg-zinc-900/80">
                <div class="text-[11px] font-medium uppercase tracking-[0.18em] text-zinc-400 dark:text-zinc-500">Plan</div>
                <div class="mt-1 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $planLabel }}</div>
            </div>
        </div>

        <div class="mt-4 rounded-[1.4rem] border border-zinc-200 bg-zinc-50/80 p-3 dark:border-zinc-800 dark:bg-zinc-900/80">
            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Mode</div>

            <div class="mt-3 grid grid-cols-3 gap-2 rounded-2xl border border-zinc-200 bg-white p-1.5 dark:border-zinc-800 dark:bg-zinc-950">
                <button
                    type="button"
                    class="iqx-appearance-option rounded-xl px-3 py-2 text-sm font-medium transition-colors duration-150"
                    data-iqx-appearance="light"
                    data-appearance-option="light"
                >
                    Day
                </button>
                <button
                    type="button"
                    class="iqx-appearance-option rounded-xl px-3 py-2 text-sm font-medium transition-colors duration-150"
                    data-iqx-appearance="dark"
                    data-appearance-option="dark"
                >
                    Night
                </button>
                <button
                    type="button"
                    class="iqx-appearance-option rounded-xl px-3 py-2 text-sm font-medium transition-colors duration-150"
                    data-iqx-appearance="system"
                    data-appearance-option="system"
                >
                    Auto
                </button>
            </div>
        </div>

        <div class="mt-4 rounded-[1.4rem] border border-zinc-200 bg-zinc-50/80 p-3 dark:border-zinc-800 dark:bg-zinc-900/80">
            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Themes</div>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($accentThemes as $theme)
                    <button
                        type="button"
                        class="iqx-accent-swatch flex h-8 w-8 items-center justify-center rounded-full border transition-colors duration-150"
                        data-iqx-accent="{{ $theme['key'] }}"
                        data-accent-option="{{ $theme['key'] }}"
                        aria-label="{{ $theme['label'] }}"
                        title="{{ $theme['label'] }}"
                    >
                        <span class="h-[22px] w-[22px] rounded-full" style="background-color: {{ $theme['color'] }};"></span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="mt-4 space-y-2">
            <a href="/settings/profile" class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-white px-3 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:bg-zinc-900" wire:navigate>
                <span>Profile settings</span>
                <flux:icon.chevron-right class="h-4 w-4 text-zinc-400 dark:text-zinc-500" />
            </a>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="flex w-full items-center justify-between rounded-2xl border border-zinc-200 bg-white px-3 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:bg-zinc-900">
                    <span>{{ __('Log Out') }}</span>
                    <flux:icon.arrow-right-start-on-rectangle class="h-4 w-4 text-zinc-400 dark:text-zinc-500" />
                </button>
            </form>
        </div>
    </div>
</div>
