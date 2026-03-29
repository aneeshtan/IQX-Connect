<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\ShipmentJob;
use App\Services\WorkspacePartySyncService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Lead::saved(fn (Lead $lead) => app(WorkspacePartySyncService::class)->syncRecord($lead));
        Opportunity::saved(fn (Opportunity $opportunity) => app(WorkspacePartySyncService::class)->syncRecord($opportunity));
        Quote::saved(fn (Quote $quote) => app(WorkspacePartySyncService::class)->syncRecord($quote));
        ShipmentJob::saved(fn (ShipmentJob $shipment) => app(WorkspacePartySyncService::class)->syncRecord($shipment));
        Booking::saved(fn (Booking $booking) => app(WorkspacePartySyncService::class)->syncRecord($booking));
        Invoice::saved(fn (Invoice $invoice) => app(WorkspacePartySyncService::class)->syncRecord($invoice));
    }
}
