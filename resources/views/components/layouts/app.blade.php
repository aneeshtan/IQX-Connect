<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[linear-gradient(180deg,_#eff6ff,_#f8fafc_18%,_#f8fafc)] text-zinc-900 dark:bg-[linear-gradient(180deg,_#09090b,_#111827_18%,_#0b1120)] dark:text-zinc-100">
        @php
            $billing = app(\App\Services\WorkspaceBillingService::class);
            $sidebarUser = auth()->user();
            $sidebarRequestedTab = request()->query('tab', 'leads');
            $sidebarWorkspace = null;
            $sidebarWorkspaceTabs = [];
            $sidebarWorkspaceTools = [];
            $topbarPageTitle = request()->routeIs('dashboard') ? 'Home' : 'Workspace';
            $topbarCreateTab = null;
            $topbarCreateLabel = null;
            $topbarTabLabels = [
                'leads' => 'Leads',
                'opportunities' => 'Opportunities',
                'contacts' => 'Contacts',
                'customers' => 'Customers',
                'analytics' => 'Reports',
                'rates' => 'Rates',
                'quotes' => 'Quotes',
                'shipments' => 'Shipments',
                'projects' => 'Projects',
                'drawings' => 'Drawings',
                'delivery_tracking' => 'Delivery Tracking',
                'carriers' => 'Carriers',
                'bookings' => 'Bookings',
                'costings' => 'Job Costing',
                'invoices' => 'Invoices',
                'settings' => 'Settings',
                'sources' => 'Sources',
                'access' => 'User Access',
            ];
            $topbarCreateMap = [
                'leads' => ['tab' => 'manual-lead', 'label' => 'Add Lead'],
                'opportunities' => ['tab' => 'manual-opportunity', 'label' => 'Add Opportunity'],
                'rates' => ['tab' => 'manual-rate', 'label' => 'Add Rate'],
                'quotes' => ['tab' => 'manual-quote', 'label' => 'Add Quote'],
                'shipments' => ['tab' => 'manual-shipment', 'label' => 'Add Shipment'],
                'projects' => ['tab' => 'manual-project', 'label' => 'Add Project'],
                'drawings' => ['tab' => 'manual-drawing', 'label' => 'Add Drawing'],
                'delivery_tracking' => ['tab' => 'manual-delivery', 'label' => 'Add Delivery Milestone'],
                'carriers' => ['tab' => 'manual-carrier', 'label' => 'Add Carrier'],
                'bookings' => ['tab' => 'manual-booking', 'label' => 'Add Booking'],
                'costings' => ['tab' => 'manual-costing', 'label' => 'Add Job Costing'],
                'invoices' => ['tab' => 'manual-invoice', 'label' => 'Add Invoice'],
            ];
            $topbarCreateItems = [];
            $profileWorkspace = null;
            $profileCompanyName = null;
            $profileRoleLabel = 'Workspace User';
            $profilePlanLabel = 'Freemium';

            if ($sidebarUser && request()->routeIs('dashboard')) {
                $sidebarWorkspace = $sidebarUser->defaultWorkspace;

                if (! $sidebarWorkspace && ! $sidebarUser->isAdmin()) {
                    $sidebarWorkspace = $sidebarUser->workspaces()->with('company')->orderBy('name')->first();
                }

                if ($sidebarWorkspace) {
                    $sidebarWorkspaceTabs = [
                        'leads' => 'Leads',
                        'opportunities' => 'Opportunities',
                        'contacts' => 'Contacts',
                        'customers' => 'Customers',
                    ];

                    foreach ($sidebarWorkspace->templateModules() as $module) {
                        if (in_array($module, ['rates', 'quotes', 'shipments', 'carriers', 'projects', 'drawings', 'delivery_tracking', 'vessel_calls', 'supply_orders', 'delivery_tasks', 'bookings', 'sailings', 'customer_accounts', 'fleet', 'technical_management', 'crewing', 'inventory', 'leasing', 'depots'], true)) {
                            $sidebarWorkspaceTabs[$module] = str($module)->replace('_', ' ')->title();
                        }
                    }

                    $sidebarWorkspaceTabs['analytics'] = 'Reports';

                    $sidebarWorkspaceTools = [
                        'settings' => 'Settings',
                    ];

                    if ($sidebarUser->isAdmin() || $sidebarUser->ownsWorkspace($sidebarWorkspace->id) || $sidebarUser->hasRole('manager')) {
                        $sidebarWorkspaceTools['sources'] = 'Sources';
                    }

                    if ($sidebarUser->isAdmin() || $sidebarUser->ownsWorkspace($sidebarWorkspace->id)) {
                        $sidebarWorkspaceTools['access'] = 'User Access';
                    }

                    $requestedTab = request()->query('tab', 'leads');

                    if (str_starts_with($requestedTab, 'manual-')) {
                        $requestedTab = match ($requestedTab) {
                            'manual-lead' => 'leads',
                            'manual-opportunity' => 'opportunities',
                            'manual-rate' => 'rates',
                            'manual-quote' => 'quotes',
                            'manual-shipment' => 'shipments',
                            'manual-project' => 'projects',
                            'manual-drawing' => 'drawings',
                            'manual-delivery' => 'delivery_tracking',
                            'manual-carrier' => 'carriers',
                            'manual-booking' => 'bookings',
                            'manual-costing' => 'costings',
                            'manual-invoice' => 'invoices',
                            default => 'leads',
                        };
                    }

                    $topbarPageTitle = $topbarTabLabels[$requestedTab] ?? 'Home';

                    if (isset($topbarCreateMap[$requestedTab])) {
                        $topbarCreateTab = $topbarCreateMap[$requestedTab]['tab'];
                        $topbarCreateLabel = $topbarCreateMap[$requestedTab]['label'];
                    }

                    $topbarCreateItems = collect($sidebarWorkspaceTabs)
                        ->map(function ($label, $tabKey) use ($topbarCreateMap) {
                            if (! isset($topbarCreateMap[$tabKey])) {
                                return null;
                            }

                            return [
                                'tab' => $topbarCreateMap[$tabKey]['tab'],
                                'label' => $topbarCreateMap[$tabKey]['label'],
                                'module' => $label,
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all();
                }
            } elseif (request()->routeIs('documentation')) {
                $topbarPageTitle = 'Documentation';
            } elseif (request()->routeIs('admin')) {
                $topbarPageTitle = 'Admin';
            } elseif (request()->routeIs('settings.*')) {
                $topbarPageTitle = 'Profile Settings';
            }

            if ($sidebarUser) {
                $profileWorkspace = $sidebarWorkspace ?: $sidebarUser->defaultWorkspace ?: $sidebarUser->workspaces()->with('company')->orderBy('name')->first();
                $profileCompanyName = $profileWorkspace?->company?->name ?: $sidebarUser->company?->name;

                $roleModel = $sidebarUser->roles()->orderByDesc('level')->first();

                if ($profileWorkspace && $sidebarUser->ownsWorkspace($profileWorkspace->id)) {
                    $profileRoleLabel = 'Workspace Owner';
                } elseif ($roleModel) {
                    $profileRoleLabel = \Illuminate\Support\Str::headline($roleModel->name ?: $roleModel->slug);
                }

                if ($profileWorkspace) {
                    $profilePlanLabel = $billing->summary($profileWorkspace)['plan_name'];
                }
            }
        @endphp

        <div class="iqx-mobile-sidebar-overlay" data-iqx-sidebar-close></div>

        <div class="min-h-screen lg:flex">
            <aside class="iqx-sidebar-shell iqx-mobile-sidebar border-r">
                <div class="flex items-center justify-between px-4 py-3 lg:hidden">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate>
                        <div class="iqx-brand-mark flex aspect-square size-10 items-center justify-center rounded-xl text-white shadow-sm">
                            <x-app-logo-icon class="size-6" />
                        </div>
                    </a>

                    <button type="button" data-iqx-sidebar-close class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                        <flux:icon.x-mark class="h-5 w-5" />
                    </button>
                </div>

                <a href="{{ route('dashboard') }}" class="mx-2 hidden items-center justify-center lg:mt-2 lg:flex" wire:navigate>
                    <div class="iqx-brand-mark flex aspect-square size-10 items-center justify-center rounded-xl text-white shadow-sm">
                        <x-app-logo-icon class="size-6" />
                    </div>
                </a>

                <div class="space-y-2 px-2">
                    <div class="px-2 text-xs font-medium uppercase tracking-[0.25em] text-zinc-400 dark:text-zinc-500">Platform</div>
                    <div class="space-y-1">
                        <a
                            href="{{ route('dashboard') }}"
                            class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors duration-150 {{ request()->routeIs('dashboard') && ! request()->has('tab') ? 'iqx-sidebar-link-active' : 'iqx-sidebar-link' }}"
                            wire:navigate
                        >
                            <flux:icon.home class="h-4 w-4" />
                            <span>Home</span>
                        </a>
                        <a
                            href="{{ route('documentation') }}"
                            class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors duration-150 {{ request()->routeIs('documentation') ? 'iqx-sidebar-link-active' : 'iqx-sidebar-link' }}"
                            wire:navigate
                        >
                            <flux:icon.book-open class="h-4 w-4" />
                            <span>Product Guide</span>
                        </a>
                    </div>
                </div>

                @if ($sidebarWorkspace && ($sidebarWorkspaceTabs !== [] || $sidebarWorkspaceTools !== []))
                    <div class="mt-6 space-y-5">
                        @if ($sidebarWorkspaceTabs !== [])
                            <div class="space-y-1">
                                @foreach ($sidebarWorkspaceTabs as $tabKey => $label)
                                    <a
                                        href="{{ route('dashboard', ['tab' => $tabKey]) }}"
                                        class="flex items-center rounded-xl px-3 py-2 text-sm font-medium transition-colors duration-150 {{ request()->routeIs('dashboard') && $sidebarRequestedTab === $tabKey ? 'iqx-sidebar-link-active' : 'iqx-sidebar-link' }}"
                                        wire:navigate
                                    >
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if ($sidebarWorkspaceTools !== [])
                            <div class="space-y-2">
                                <div class="px-2 text-xs font-medium uppercase tracking-[0.25em] text-zinc-400 dark:text-zinc-500">Workspace Tools</div>
                                <div class="space-y-1">
                                    @foreach ($sidebarWorkspaceTools as $tabKey => $label)
                                        <a
                                            href="{{ route('dashboard', ['tab' => $tabKey]) }}"
                                            class="flex items-center rounded-xl px-3 py-2 text-sm font-medium transition-colors duration-150 {{ request()->routeIs('dashboard') && $sidebarRequestedTab === $tabKey ? 'iqx-sidebar-link-active' : 'iqx-sidebar-link' }}"
                                            wire:navigate
                                        >
                                            {{ $label }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="mt-auto px-2 py-4"></div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-30 flex items-center justify-between border-b border-zinc-200/80 bg-white px-4 py-3 dark:border-zinc-800/80 dark:bg-zinc-950 lg:hidden">
                    <div class="flex items-center gap-3">
                        <button type="button" data-iqx-sidebar-toggle class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 transition-colors duration-150 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">
                            <flux:icon.bars-2 class="h-5 w-5" />
                        </button>
                        <div class="text-base font-semibold text-zinc-950 dark:text-zinc-100">{{ $topbarPageTitle }}</div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if (request()->routeIs('dashboard'))
                            <livewire:topbar-notifications :workspace-id="$sidebarWorkspace?->id" :key="'mobile-topbar-notifications-'.($sidebarWorkspace?->id ?? 'none')" />
                        @endif

                        @if ($topbarCreateItems !== [])
                            <details class="iqx-menu">
                                <summary class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 transition-colors duration-150 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                    <flux:icon.plus class="h-5 w-5" />
                                </summary>

                                <div class="iqx-menu-panel w-[220px] p-2">
                                    @foreach ($topbarCreateItems as $createItem)
                                        <a href="{{ route('dashboard', ['tab' => $createItem['tab']]) }}" class="iqx-menu-item" wire:navigate>{{ $createItem['label'] }}</a>
                                    @endforeach
                                </div>
                            </details>
                        @endif

                        <details class="iqx-menu">
                            <summary class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-zinc-200 bg-white transition-colors duration-150 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[rgb(var(--iqx-accent-soft-rgb))] text-sm font-semibold text-[rgb(var(--iqx-accent-ink-rgb))]">
                                    {{ $sidebarUser->initials() }}
                                </span>
                            </summary>

                            <div class="iqx-menu-panel">
                                @include('partials.profile-menu-content', [
                                    'user' => $sidebarUser,
                                    'companyName' => $profileCompanyName,
                                    'roleLabel' => $profileRoleLabel,
                                    'planLabel' => $profilePlanLabel,
                                ])
                            </div>
                        </details>
                    </div>
                </header>

                <main class="!px-0 !pt-0 pb-[calc(6.5rem+env(safe-area-inset-bottom))] lg:pb-0">
                    <div class="iqx-topbar sticky top-0 z-30 hidden border-b border-zinc-200/80 bg-white py-3 dark:border-zinc-800/80 dark:bg-zinc-950 lg:flex lg:items-center lg:justify-between">
                <div class="text-base font-semibold text-zinc-950 dark:text-zinc-100">{{ $topbarPageTitle }}</div>

                <div class="flex items-center gap-3">
                    @if (request()->routeIs('dashboard'))
                        <livewire:topbar-notifications :workspace-id="$sidebarWorkspace?->id" :key="'desktop-topbar-notifications-'.($sidebarWorkspace?->id ?? 'none')" />
                    @endif

                    @if ($topbarCreateItems !== [])
                        <details class="iqx-menu">
                            <summary class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 transition-colors duration-150 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <flux:icon.plus class="h-4 w-4" />
                            </summary>

                            <div class="iqx-menu-panel w-[240px] p-2">
                                @foreach ($topbarCreateItems as $createItem)
                                    <a href="{{ route('dashboard', ['tab' => $createItem['tab']]) }}" class="iqx-menu-item" wire:navigate>{{ $createItem['label'] }}</a>
                                @endforeach
                            </div>
                        </details>
                    @endif

                    <details class="iqx-menu">
                        <summary class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-zinc-200 bg-white transition-colors duration-150 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[rgb(var(--iqx-accent-soft-rgb))] text-sm font-semibold text-[rgb(var(--iqx-accent-ink-rgb))]">
                                {{ $sidebarUser->initials() }}
                            </span>
                        </summary>

                        <div class="iqx-menu-panel">
                            @include('partials.profile-menu-content', [
                                'user' => $sidebarUser,
                                'companyName' => $profileCompanyName,
                                'roleLabel' => $profileRoleLabel,
                                'planLabel' => $profilePlanLabel,
                            ])
                        </div>
                    </details>
                </div>
            </div>

            <div class="iqx-app-content px-6">
                {{ $slot }}

                @include('partials.site-footer', [
                    'class' => 'border-t border-zinc-200 py-5 text-center',
                ])
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
