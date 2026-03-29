<?php

namespace App\Livewire;

use App\Models\Workspace;
use App\Models\WorkspaceNotification;
use Livewire\Attributes\On;
use Livewire\Component;

class TopbarNotifications extends Component
{
    public ?int $workspaceId = null;

    public bool $showDropdown = false;

    public function mount(?int $workspaceId = null): void
    {
        $this->workspaceId = $workspaceId;
    }

    #[On('workspace-notification-workspace-changed')]
    public function syncWorkspace(?int $workspaceId = null): void
    {
        $this->workspaceId = $workspaceId;
        $this->showDropdown = false;
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    public function markAllRead(): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace) {
            return;
        }

        WorkspaceNotification::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function openNotification(int $notificationId)
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace) {
            return null;
        }

        $notification = WorkspaceNotification::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', auth()->id())
            ->findOrFail($notificationId);

        if (! $notification->is_read) {
            $notification->forceFill([
                'is_read' => true,
                'read_at' => now(),
            ])->save();
        }

        $this->showDropdown = false;

        if (request()->routeIs('dashboard')) {
            $this->dispatch('open-workspace-notification', notificationId: $notificationId);

            return null;
        }

        return $this->redirect(route('dashboard', ['notification' => $notificationId]), navigate: true);
    }

    public function render()
    {
        $workspace = $this->currentWorkspace();
        $unreadCount = 0;
        $notifications = collect();

        if ($workspace) {
            $unreadCount = WorkspaceNotification::query()
                ->where('workspace_id', $workspace->id)
                ->where('user_id', auth()->id())
                ->where('is_read', false)
                ->count();

            if ($this->showDropdown) {
                $notifications = WorkspaceNotification::query()
                    ->with('actor')
                    ->where('workspace_id', $workspace->id)
                    ->where('user_id', auth()->id())
                    ->latest()
                    ->limit(8)
                    ->get();
            }
        }

        return view('livewire.topbar-notifications', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'workspace' => $workspace,
        ]);
    }

    protected function currentWorkspace(): ?Workspace
    {
        if (! $this->workspaceId) {
            return null;
        }

        return Workspace::query()->find($this->workspaceId);
    }
}
