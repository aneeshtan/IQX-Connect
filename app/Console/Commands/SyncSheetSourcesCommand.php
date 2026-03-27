<?php

namespace App\Console\Commands;

use App\Models\SheetSource;
use App\Services\SheetSourceSyncService;
use Illuminate\Console\Command;

class SyncSheetSourcesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:sync-sheet-sources {sourceId? : Sync only one source id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Sheet / CSV sources into the CRM';

    /**
     * Execute the console command.
     */
    public function handle(SheetSourceSyncService $syncService): int
    {
        $sourceId = $this->argument('sourceId');

        $sources = SheetSource::query()
            ->when($sourceId, fn ($query) => $query->whereKey($sourceId), fn ($query) => $query->where('is_active', true))
            ->orderBy('id')
            ->get();

        if ($sources->isEmpty()) {
            $this->warn('No matching sheet sources found.');

            return self::FAILURE;
        }

        $totalRows = 0;

        foreach ($sources as $source) {
            try {
                $rows = $syncService->sync($source);
                $totalRows += $rows;
                $this->info("Synced source #{$source->id} [{$source->type}] with {$rows} rows.");
            } catch (\Throwable $exception) {
                $this->error("Source #{$source->id} failed: {$exception->getMessage()}");
            }
        }

        $this->line("Total imported rows: {$totalRows}");

        return self::SUCCESS;
    }
}
