<section class="rounded-[1.25rem] border border-zinc-200 bg-white px-4 py-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="text-sm font-semibold text-zinc-950">Team collaboration</div>
            <p class="mt-1 text-sm text-zinc-500">Assign a teammate, leave internal notes, or send a workspace message on this {{ $recordLabel }}.</p>
        </div>

        @if ($showAssignment)
            <div class="w-full max-w-xs">
                <label class="text-xs uppercase tracking-[0.2em] text-zinc-400">Assigned sales</label>
                <select
                    wire:change="updateRecordAssignment('{{ $recordType }}', {{ $record->id }}, $event.target.value)"
                    class="mt-2 w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none"
                >
                    <option value="">Unassigned</option>
                    @foreach ($workspaceUsers as $workspaceUser)
                        <option value="{{ $workspaceUser->id }}" @selected((int) $record->assigned_user_id === (int) $workspaceUser->id)>
                            {{ $workspaceUser->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    <div class="mt-4 grid gap-3 lg:grid-cols-[160px_1fr_220px_auto]">
        <select wire:model.live="collaborationForms.{{ $recordType }}.type" class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
            <option value="{{ \App\Models\CollaborationEntry::TYPE_NOTE }}">Internal note</option>
            <option value="{{ \App\Models\CollaborationEntry::TYPE_MESSAGE }}">Message teammate</option>
        </select>

        <textarea
            wire:model="collaborationForms.{{ $recordType }}.body"
            rows="3"
            placeholder="Add context, next step, or a quick message for the team"
            class="rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none"
        ></textarea>

        <div>
            @if ((data_get($collaborationForms, $recordType.'.type') ?? \App\Models\CollaborationEntry::TYPE_NOTE) === \App\Models\CollaborationEntry::TYPE_MESSAGE)
                <select wire:model="collaborationForms.{{ $recordType }}.recipient_user_id" class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none">
                    <option value="">Choose teammate</option>
                    @foreach ($workspaceUsers as $workspaceUser)
                        <option value="{{ $workspaceUser->id }}">{{ $workspaceUser->name }}</option>
                    @endforeach
                </select>
                @error('collaborationForms.'.$recordType.'.recipient_user_id')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            @else
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-500">
                    Team-visible note
                </div>
            @endif
        </div>

        <div class="flex items-start justify-end">
            <button wire:click="addCollaborationEntry('{{ $recordType }}', {{ $record->id }})" type="button" class="rounded-xl bg-zinc-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800">
                Post
            </button>
        </div>
    </div>

    @error('collaborationForms.'.$recordType.'.body')
        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
    @enderror

    <div class="mt-4 space-y-3">
        @forelse ($entries as $entry)
            <div class="rounded-[1rem] border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-zinc-950">{{ $entry->user?->name ?: 'Workspace user' }}</span>
                            <span class="rounded-full border px-2.5 py-1 text-[11px] font-medium uppercase tracking-[0.18em] {{ $entry->entry_type === \App\Models\CollaborationEntry::TYPE_MESSAGE ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-zinc-200 bg-white text-zinc-600' }}">
                                {{ $entry->entry_type === \App\Models\CollaborationEntry::TYPE_MESSAGE ? 'Message' : 'Note' }}
                            </span>
                            @if ($entry->entry_type === \App\Models\CollaborationEntry::TYPE_MESSAGE && $entry->recipientUser)
                                <span class="text-xs text-zinc-400">to {{ $entry->recipientUser->name }}</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $entry->body }}</p>
                    </div>
                    <div class="text-right text-xs text-zinc-400">
                        <div>{{ $entry->created_at?->format('d M Y') }}</div>
                        <div class="mt-1">{{ $entry->created_at?->format('H:i') }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-[1rem] border border-dashed border-zinc-200 bg-zinc-50/60 px-4 py-4 text-sm text-zinc-500">
                No collaboration yet. Add the first note or message for this {{ $recordLabel }}.
            </div>
        @endforelse
    </div>
</section>
