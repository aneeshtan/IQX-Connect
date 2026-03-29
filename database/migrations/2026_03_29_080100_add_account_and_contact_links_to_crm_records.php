<?php

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\ShipmentJob;
use App\Services\WorkspacePartySyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('sheet_source_id')->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->after('account_id')->constrained()->nullOnDelete();
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('sheet_source_id')->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->after('account_id')->constrained()->nullOnDelete();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('sheet_source_id')->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->after('account_id')->constrained()->nullOnDelete();
        });

        Schema::table('shipment_jobs', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('sheet_source_id')->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->after('account_id')->constrained()->nullOnDelete();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('sheet_source_id')->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->after('account_id')->constrained()->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('workspace_id')->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->after('account_id')->constrained()->nullOnDelete();
        });

        $sync = new WorkspacePartySyncService;

        Lead::query()->orderBy('id')->each(fn (Lead $lead) => $sync->syncRecord($lead));
        Opportunity::query()->with('lead')->orderBy('id')->each(fn (Opportunity $opportunity) => $sync->syncRecord($opportunity));
        Quote::query()->orderBy('id')->each(fn (Quote $quote) => $sync->syncRecord($quote));
        ShipmentJob::query()->orderBy('id')->each(fn (ShipmentJob $shipment) => $sync->syncRecord($shipment));
        Booking::query()->orderBy('id')->each(fn (Booking $booking) => $sync->syncRecord($booking));
        Invoice::query()->orderBy('id')->each(fn (Invoice $invoice) => $sync->syncRecord($invoice));
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::table('shipment_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
            $table->dropConstrainedForeignId('account_id');
        });
    }
};
