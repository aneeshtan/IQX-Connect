<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->index(['workspace_id', 'assigned_user_id'], 'leads_workspace_assigned_idx');
            $table->index(['workspace_id', 'account_id'], 'leads_workspace_account_idx');
            $table->index(['workspace_id', 'contact_id'], 'leads_workspace_contact_idx');
            $table->index(['workspace_id', 'created_at'], 'leads_workspace_created_idx');
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->index(['workspace_id', 'assigned_user_id'], 'opps_workspace_assigned_idx');
            $table->index(['workspace_id', 'account_id'], 'opps_workspace_account_idx');
            $table->index(['workspace_id', 'contact_id'], 'opps_workspace_contact_idx');
            $table->index(['workspace_id', 'lead_id'], 'opps_workspace_lead_idx');
            $table->index(['workspace_id', 'created_at'], 'opps_workspace_created_idx');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->index(['workspace_id', 'account_id'], 'quotes_workspace_account_idx');
            $table->index(['workspace_id', 'contact_id'], 'quotes_workspace_contact_idx');
            $table->index(['workspace_id', 'opportunity_id'], 'quotes_workspace_opportunity_idx');
            $table->index(['workspace_id', 'assigned_user_id'], 'quotes_workspace_assigned_idx');
            $table->index(['workspace_id', 'status', 'quoted_at'], 'quotes_workspace_status_quoted_idx');
        });

        Schema::table('shipment_jobs', function (Blueprint $table) {
            $table->index(['workspace_id', 'account_id'], 'shipments_workspace_account_idx');
            $table->index(['workspace_id', 'contact_id'], 'shipments_workspace_contact_idx');
            $table->index(['workspace_id', 'opportunity_id'], 'shipments_workspace_opportunity_idx');
            $table->index(['workspace_id', 'quote_id'], 'shipments_workspace_quote_idx');
            $table->index(['workspace_id', 'assigned_user_id'], 'shipments_workspace_assigned_idx');
            $table->index(['workspace_id', 'status', 'estimated_departure_at'], 'shipments_workspace_status_etd_idx');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['workspace_id', 'shipment_job_id'], 'bookings_workspace_shipment_idx');
            $table->index(['workspace_id', 'account_id'], 'bookings_workspace_account_idx');
            $table->index(['workspace_id', 'contact_id'], 'bookings_workspace_contact_idx');
            $table->index(['workspace_id', 'requested_etd'], 'bookings_workspace_requested_etd_idx');
            $table->index(['workspace_id', 'status', 'requested_etd'], 'bookings_workspace_status_etd_idx');
        });

        Schema::table('job_costings', function (Blueprint $table) {
            $table->index(['workspace_id', 'quote_id'], 'costings_workspace_quote_idx');
            $table->index(['workspace_id', 'opportunity_id'], 'costings_workspace_opportunity_idx');
            $table->index(['workspace_id', 'lead_id'], 'costings_workspace_lead_idx');
            $table->index(['workspace_id', 'status', 'created_at'], 'costings_workspace_status_created_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['workspace_id', 'booking_id'], 'invoices_workspace_booking_idx');
            $table->index(['workspace_id', 'job_costing_id'], 'invoices_workspace_costing_idx');
            $table->index(['workspace_id', 'account_id'], 'invoices_workspace_account_idx');
            $table->index(['workspace_id', 'contact_id'], 'invoices_workspace_contact_idx');
            $table->index(['workspace_id', 'issue_date'], 'invoices_workspace_issue_idx');
            $table->index(['workspace_id', 'status', 'issue_date'], 'invoices_workspace_status_issue_idx');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->index(['workspace_id', 'assigned_user_id'], 'accounts_workspace_assigned_idx');
            $table->index(['workspace_id', 'last_activity_at'], 'accounts_workspace_activity_idx');
            $table->index(['workspace_id', 'primary_email'], 'accounts_workspace_email_idx');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->index(['workspace_id', 'account_id'], 'contacts_workspace_account_idx');
            $table->index(['workspace_id', 'assigned_user_id'], 'contacts_workspace_assigned_idx');
            $table->index(['workspace_id', 'last_activity_at'], 'contacts_workspace_activity_idx');
        });

        Schema::table('rate_cards', function (Blueprint $table) {
            $table->index(['workspace_id', 'carrier_id'], 'rates_workspace_carrier_idx');
            $table->index(['workspace_id', 'assigned_user_id'], 'rates_workspace_assigned_idx');
            $table->index(['workspace_id', 'is_active', 'valid_until'], 'rates_workspace_active_valid_idx');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index(['workspace_id', 'account_id'], 'projects_workspace_account_idx');
            $table->index(['workspace_id', 'contact_id'], 'projects_workspace_contact_idx');
            $table->index(['workspace_id', 'opportunity_id'], 'projects_workspace_opportunity_idx');
            $table->index(['workspace_id', 'assigned_user_id'], 'projects_workspace_assigned_idx');
            $table->index(['workspace_id', 'status', 'target_delivery_date'], 'projects_workspace_status_delivery_idx');
        });

        Schema::table('project_drawings', function (Blueprint $table) {
            $table->index(['workspace_id', 'project_id'], 'drawings_workspace_project_idx');
            $table->index(['workspace_id', 'assigned_user_id'], 'drawings_workspace_assigned_idx');
            $table->index(['workspace_id', 'status', 'submitted_at'], 'drawings_workspace_status_submitted_idx');
        });

        Schema::table('project_delivery_milestones', function (Blueprint $table) {
            $table->index(['workspace_id', 'project_id'], 'delivery_workspace_project_idx');
            $table->index(['workspace_id', 'assigned_user_id'], 'delivery_workspace_assigned_idx');
            $table->index(['workspace_id', 'status', 'planned_date'], 'delivery_workspace_status_planned_idx');
        });

        Schema::table('account_metric_snapshots', function (Blueprint $table) {
            $table->index(['workspace_id', 'snapshot_key', 'evaluated_at'], 'account_metrics_workspace_snapshot_eval_idx');
        });
    }

    public function down(): void
    {
        Schema::table('account_metric_snapshots', function (Blueprint $table) {
            $table->dropIndex('account_metrics_workspace_snapshot_eval_idx');
        });

        Schema::table('project_delivery_milestones', function (Blueprint $table) {
            $table->dropIndex('delivery_workspace_status_planned_idx');
            $table->dropIndex('delivery_workspace_assigned_idx');
            $table->dropIndex('delivery_workspace_project_idx');
        });

        Schema::table('project_drawings', function (Blueprint $table) {
            $table->dropIndex('drawings_workspace_status_submitted_idx');
            $table->dropIndex('drawings_workspace_assigned_idx');
            $table->dropIndex('drawings_workspace_project_idx');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_workspace_status_delivery_idx');
            $table->dropIndex('projects_workspace_assigned_idx');
            $table->dropIndex('projects_workspace_opportunity_idx');
            $table->dropIndex('projects_workspace_contact_idx');
            $table->dropIndex('projects_workspace_account_idx');
        });

        Schema::table('rate_cards', function (Blueprint $table) {
            $table->dropIndex('rates_workspace_active_valid_idx');
            $table->dropIndex('rates_workspace_assigned_idx');
            $table->dropIndex('rates_workspace_carrier_idx');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('contacts_workspace_activity_idx');
            $table->dropIndex('contacts_workspace_assigned_idx');
            $table->dropIndex('contacts_workspace_account_idx');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex('accounts_workspace_email_idx');
            $table->dropIndex('accounts_workspace_activity_idx');
            $table->dropIndex('accounts_workspace_assigned_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_workspace_status_issue_idx');
            $table->dropIndex('invoices_workspace_issue_idx');
            $table->dropIndex('invoices_workspace_contact_idx');
            $table->dropIndex('invoices_workspace_account_idx');
            $table->dropIndex('invoices_workspace_costing_idx');
            $table->dropIndex('invoices_workspace_booking_idx');
        });

        Schema::table('job_costings', function (Blueprint $table) {
            $table->dropIndex('costings_workspace_status_created_idx');
            $table->dropIndex('costings_workspace_lead_idx');
            $table->dropIndex('costings_workspace_opportunity_idx');
            $table->dropIndex('costings_workspace_quote_idx');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_workspace_status_etd_idx');
            $table->dropIndex('bookings_workspace_requested_etd_idx');
            $table->dropIndex('bookings_workspace_contact_idx');
            $table->dropIndex('bookings_workspace_account_idx');
            $table->dropIndex('bookings_workspace_shipment_idx');
        });

        Schema::table('shipment_jobs', function (Blueprint $table) {
            $table->dropIndex('shipments_workspace_status_etd_idx');
            $table->dropIndex('shipments_workspace_assigned_idx');
            $table->dropIndex('shipments_workspace_quote_idx');
            $table->dropIndex('shipments_workspace_opportunity_idx');
            $table->dropIndex('shipments_workspace_contact_idx');
            $table->dropIndex('shipments_workspace_account_idx');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex('quotes_workspace_status_quoted_idx');
            $table->dropIndex('quotes_workspace_assigned_idx');
            $table->dropIndex('quotes_workspace_opportunity_idx');
            $table->dropIndex('quotes_workspace_contact_idx');
            $table->dropIndex('quotes_workspace_account_idx');
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex('opps_workspace_created_idx');
            $table->dropIndex('opps_workspace_lead_idx');
            $table->dropIndex('opps_workspace_contact_idx');
            $table->dropIndex('opps_workspace_account_idx');
            $table->dropIndex('opps_workspace_assigned_idx');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_workspace_created_idx');
            $table->dropIndex('leads_workspace_contact_idx');
            $table->dropIndex('leads_workspace_account_idx');
            $table->dropIndex('leads_workspace_assigned_idx');
        });
    }
};
