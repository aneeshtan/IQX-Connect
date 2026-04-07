<?php

namespace App\Jobs;

use App\Models\Workspace;
use App\Services\CustomerSegmentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RefreshWorkspaceSegmentation implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 1200;

    public int $uniqueFor = 1800;

    public function __construct(public int $workspaceId) {}

    public function uniqueId(): string
    {
        return (string) $this->workspaceId;
    }

    public function handle(CustomerSegmentationService $segmentation): void
    {
        try {
            $workspace = Workspace::query()->find($this->workspaceId);

            if (! $workspace) {
                return;
            }

            $segmentation->syncWorkspace($workspace);
        } finally {
            $segmentation->markRefreshCompleted($this->workspaceId);
        }
    }

    public function failed(Throwable $exception): void
    {
        app(CustomerSegmentationService::class)->markRefreshCompleted($this->workspaceId);
    }
}
