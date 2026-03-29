<div class="relative">
    <button
        wire:click="toggleDropdown"
        type="button"
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 transition-colors duration-150 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
        aria-label="Notifications"
    >
        <flux:icon.bell class="h-5 w-5" />

        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white shadow-sm">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    @if ($showDropdown)
        <div class="absolute right-0 top-[calc(100%+0.75rem)] z-50 w-[24rem] rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-2xl shadow-zinc-950/10 dark:border-zinc-700 dark:bg-zinc-950 dark:shadow-black/40">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-zinc-950 dark:text-zinc-100">Notifications</div>
                    <div class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                        {{ $workspace ? $workspace->name.' workspace activity' : 'Workspace activity' }}
                    </div>
                </div>

                @if ($unreadCount > 0)
                    <button wire:click="markAllRead" type="button" class="text-xs font-medium text-red-600 transition hover:text-red-700">
                        Mark all read
                    </button>
                @endif
            </div>

            <div class="mt-4 max-h-96 space-y-3 overflow-y-auto">
                @forelse ($notifications as $notification)
                    <button
                        wire:click="openNotification({{ $notification->id }})"
                        type="button"
                        class="block w-full rounded-[1rem] border px-4 py-3 text-left transition {{ $notification->is_read ? 'border-zinc-200 bg-zinc-50/70 hover:bg-zinc-100/70 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800' : 'border-red-200 bg-red-50/70 hover:bg-red-100/70 dark:border-red-900/60 dark:bg-red-950/20 dark:hover:bg-red-950/30' }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-zinc-950 dark:text-zinc-100">{{ $notification->title }}</div>
                                <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $notification->body }}</div>
                                @if (data_get($notification->data, 'entry_preview'))
                                    <div class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">{{ data_get($notification->data, 'entry_preview') }}</div>
                                @endif
                            </div>
                            <div class="text-right text-xs text-zinc-400 dark:text-zinc-500">
                                <div>{{ $notification->created_at?->format('d M') }}</div>
                                <div class="mt-1">{{ $notification->created_at?->format('H:i') }}</div>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="rounded-[1rem] border border-dashed border-zinc-200 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        No notifications yet.
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
