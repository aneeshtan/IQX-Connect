<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[linear-gradient(180deg,_#eff6ff,_#f8fafc_18%,_#f8fafc)] text-zinc-900">
        <flux:sidebar sticky stashable class="border-r border-sky-100 bg-white/90 backdrop-blur">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            @php
                $sidebarUser = auth()->user();
                $sidebarRequestedTab = request()->query('tab', 'leads');
                $sidebarWorkspace = null;
                $sidebarWorkspaceTabs = [];
                $sidebarWorkspaceTools = [];

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
                            'analytics' => 'Analytics',
                        ];

                        foreach ($sidebarWorkspace->templateModules() as $module) {
                            if (in_array($module, ['quotes', 'shipments', 'carriers', 'projects', 'drawings', 'delivery_tracking', 'vessel_calls', 'supply_orders', 'delivery_tasks', 'bookings', 'sailings', 'customer_accounts', 'fleet', 'technical_management', 'crewing', 'inventory', 'leasing', 'depots'], true)) {
                                $sidebarWorkspaceTabs[$module] = str($module)->replace('_', ' ')->title();
                            }
                        }

                        $sidebarWorkspaceTools = [
                            'settings' => 'Settings',
                            'sources' => 'Sources',
                        ];

                        if ($sidebarUser->isAdmin() || $sidebarUser->ownsWorkspace($sidebarWorkspace->id)) {
                            $sidebarWorkspaceTools['access'] = 'User Access';
                        }
                    }
                }
            @endphp

            <a href="{{ route('dashboard') }}" class="mr-5 flex items-center space-x-2" wire:navigate>
                <x-app-logo class="size-8" href="#"></x-app-logo>
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group heading="Platform" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard') && ! request()->has('tab')" wire:navigate>Home</flux:navlist.item>
                    @if (auth()->user()->isAdmin())
                        <flux:navlist.item icon="chart-bar" :href="route('admin')" :current="request()->routeIs('admin')" wire:navigate>Admin</flux:navlist.item>
                    @endif
                    <flux:navlist.item icon="cog" :href="route('settings.profile')" :current="request()->routeIs('settings.*')" wire:navigate>Profile Settings</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            @if ($sidebarWorkspace && ($sidebarWorkspaceTabs !== [] || $sidebarWorkspaceTools !== []))
                <div class="mt-6 space-y-5">
                    <div class="px-2">
                        <div class="text-xs font-medium uppercase tracking-[0.25em] text-zinc-400">Workspace</div>
                        <div class="mt-2 text-sm font-semibold text-zinc-900">{{ $sidebarWorkspace->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $sidebarWorkspace->company?->name }}</div>
                    </div>

                    @if ($sidebarWorkspaceTabs !== [])
                        <div class="space-y-2">
                            <div class="px-2 text-xs font-medium uppercase tracking-[0.25em] text-zinc-400">CRM Views</div>
                            <div class="space-y-1">
                                @foreach ($sidebarWorkspaceTabs as $tabKey => $label)
                                    <a
                                        href="{{ route('dashboard', ['tab' => $tabKey]) }}"
                                        class="flex items-center rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') && $sidebarRequestedTab === $tabKey ? 'bg-sky-50 text-sky-900' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900' }}"
                                        wire:navigate
                                    >
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($sidebarWorkspaceTools !== [])
                        <div class="space-y-2">
                            <div class="px-2 text-xs font-medium uppercase tracking-[0.25em] text-zinc-400">Workspace Tools</div>
                            <div class="space-y-1">
                                @foreach ($sidebarWorkspaceTools as $tabKey => $label)
                                    <a
                                        href="{{ route('dashboard', ['tab' => $tabKey]) }}"
                                        class="flex items-center rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') && $sidebarRequestedTab === $tabKey ? 'bg-sky-50 text-sky-900' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900' }}"
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

            <flux:spacer />

            <div class="rounded-2xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-950">
                <div class="font-medium">IQX Connect</div>
                <div class="mt-1 text-sky-700">Live lead sync, one-page updates, and admin reporting in a single workspace.</div>
            </div>

            <flux:dropdown position="bottom" align="start">
                <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()" icon-trailing="chevrons-up-down" />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-sky-100 text-sky-950">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-sky-100 text-sky-950">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main>
            {{ $slot }}

            @include('partials.site-footer', [
                'class' => 'border-t border-zinc-200 px-6 py-5 text-center',
            ])
        </flux:main>

        @fluxScripts
    </body>
</html>
