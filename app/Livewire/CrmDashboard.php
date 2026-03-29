<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Booking;
use App\Models\Carrier;
use App\Models\CollaborationEntry;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CustomerSegmentDefinition;
use App\Models\CustomerSegmentRule;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JobCosting;
use App\Models\JobCostingLine;
use App\Models\Lead;
use App\Models\LeadStatusLog;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\ProjectDeliveryMilestone;
use App\Models\ProjectDrawing;
use App\Models\Quote;
use App\Models\RateCard;
use App\Models\SheetSource;
use App\Models\ShipmentDocument;
use App\Models\ShipmentJob;
use App\Models\ShipmentMilestone;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceNotification;
use App\Services\CustomerSegmentationService;
use App\Services\GoogleSheetsService;
use App\Services\LeadScoringService;
use App\Services\SheetSourceSyncService;
use App\Services\WorkspaceCollaborationService;
use App\Services\WorkspaceEnrichmentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use jeremykenedy\LaravelRoles\Models\Permission;
use jeremykenedy\LaravelRoles\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CrmDashboard extends Component
{
    use WithPagination;

    protected array $leadScoreCache = [];

    public $workspaceId = null;

    public ?int $editingSourceId = null;

    public ?int $editingWorkspaceUserId = null;

    public ?int $selectedLeadId = null;

    public ?int $selectedContactId = null;

    public ?int $selectedCustomerId = null;

    public ?int $selectedOpportunityId = null;

    public ?int $selectedProjectId = null;

    public ?int $selectedDrawingId = null;

    public ?int $selectedDeliveryId = null;

    public ?int $selectedQuoteId = null;

    public ?int $selectedRateId = null;

    public ?int $selectedShipmentId = null;

    public ?int $selectedCarrierId = null;

    public ?int $selectedBookingId = null;

    public ?int $selectedCostingId = null;

    public ?int $selectedInvoiceId = null;

    public array $selectedCollaborationCollections = [];

    public ?int $editingOpportunityId = null;

    public ?int $editingProjectId = null;

    public ?int $editingDrawingId = null;

    public ?int $editingDeliveryId = null;

    public ?int $editingQuoteId = null;

    public ?int $editingRateId = null;

    public ?int $editingShipmentId = null;

    public ?int $editingCarrierId = null;

    public ?int $editingBookingId = null;

    public ?int $editingCostingId = null;

    public ?int $editingInvoiceId = null;

    public ?int $pendingDisqualificationLeadId = null;

    public string $activeTab = 'leads';

    public string $settingsTab = 'general';

    public bool $showNotifications = false;

    public string $search = '';

    public string $leadStatusFilter = '';

    public string $leadSourceFilter = '';

    public string $opportunityStageFilter = '';

    public string $contactSearch = '';

    public string $customerSearch = '';

    public string $customerSegmentFilter = '';

    public string $quoteSearch = '';

    public string $shipmentSearch = '';

    public string $projectSearch = '';

    public string $drawingSearch = '';

    public string $deliverySearch = '';

    public string $rateSearch = '';

    public string $carrierSearch = '';

    public string $bookingSearch = '';

    public string $costingSearch = '';

    public string $invoiceSearch = '';

    public string $leadSort = 'newest';

    public string $opportunitySort = 'newest';

    public string $contactSort = 'newest';

    public string $customerSort = 'newest';

    public string $quoteSort = 'newest';

    public string $shipmentSort = 'newest';

    public string $projectSort = 'newest';

    public string $drawingSort = 'newest';

    public string $deliverySort = 'newest';

    public string $rateSort = 'newest';

    public string $carrierSort = 'name_asc';

    public string $bookingSort = 'newest';

    public string $costingSort = 'newest';

    public string $invoiceSort = 'newest';

    public string $quoteStatusFilter = '';

    public string $shipmentStatusFilter = '';

    public string $projectStatusFilter = '';

    public string $drawingStatusFilter = '';

    public string $deliveryStatusFilter = '';

    public string $rateModeFilter = '';

    public string $carrierModeFilter = '';

    public string $bookingStatusFilter = '';

    public string $costingStatusFilter = '';

    public string $invoiceStatusFilter = '';

    public string $invoiceBookingFilter = '';

    public string $analyticsRange = 'last_month';

    public string $analyticsBreakdown = 'source';

    public string $analyticsMonth = '';

    public int $leadPerPage = 15;

    public int $opportunityPerPage = 15;

    public int $contactPerPage = 12;

    public int $customerPerPage = 12;

    public int $quotePerPage = 15;

    public int $shipmentPerPage = 15;

    public int $projectPerPage = 15;

    public int $drawingPerPage = 15;

    public int $deliveryPerPage = 15;

    public int $ratePerPage = 15;

    public int $carrierPerPage = 15;

    public int $bookingPerPage = 15;

    public int $costingPerPage = 15;

    public int $invoicePerPage = 15;

    public array $companyForm = [];

    public array $workspaceForm = [];

    public array $workspaceSettingsForm = [];

    public array $notificationSettingsForm = [];

    public array $sourceForm = [];

    public array $editingSourceForm = [];

    public array $userForm = [];

    public array $roleForm = [];

    public array $permissionForm = [];

    public array $editingWorkspaceUserForm = [];

    public array $manualLeadForm = [];

    public array $manualOpportunityForm = [];

    public array $manualProjectForm = [];

    public array $manualDrawingForm = [];

    public array $manualDeliveryForm = [];

    public array $manualQuoteForm = [];

    public array $manualRateForm = [];

    public array $manualShipmentForm = [];

    public array $manualCarrierForm = [];

    public array $manualBookingForm = [];

    public array $manualCostingForm = [];

    public array $manualInvoiceForm = [];

    public array $shipmentMilestoneForm = [];

    public array $shipmentDocumentForm = [];

    public array $opportunityEditForm = [];

    public array $projectEditForm = [];

    public array $drawingEditForm = [];

    public array $deliveryEditForm = [];

    public array $quoteEditForm = [];

    public array $rateEditForm = [];

    public array $shipmentEditForm = [];

    public array $carrierEditForm = [];

    public array $bookingEditForm = [];

    public array $costingEditForm = [];

    public array $invoiceEditForm = [];

    public array $collaborationForms = [];

    public function mount(): void
    {
        $this->resetForms();

        $workspace = $this->resolveCurrentWorkspace($this->accessibleWorkspaces());

        $this->workspaceId = $workspace?->id;
        $requestedTab = request()->query('tab');

        if (is_string($requestedTab) && $requestedTab !== '') {
            $this->activeTab = $requestedTab;
        }

        $this->primeForms($workspace);
    }

    public function updatedWorkspaceId(): void
    {
        $this->primeForms($this->currentWorkspace());
        $this->resetPage('leadsPage');
        $this->resetPage('opportunitiesPage');
        $this->resetPage('contactsPage');
        $this->resetPage('customersPage');
        $this->resetPage('ratesPage');
        $this->resetPage('quotesPage');
        $this->resetPage('shipmentsPage');
        $this->resetPage('projectsPage');
        $this->resetPage('drawingsPage');
        $this->resetPage('deliveryPage');
        $this->resetPage('carriersPage');
        $this->resetPage('bookingsPage');
        $this->resetPage('costingsPage');
        $this->resetPage('invoicesPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
        $this->selectedContactId = null;
        $this->selectedCustomerId = null;
        $this->customerSegmentFilter = '';
        $this->selectedRateId = null;
        $this->selectedOpportunityId = null;
        $this->selectedQuoteId = null;
        $this->selectedShipmentId = null;
        $this->selectedProjectId = null;
        $this->selectedDrawingId = null;
        $this->selectedDeliveryId = null;
        $this->selectedCarrierId = null;
        $this->selectedBookingId = null;
        $this->selectedCostingId = null;
        $this->selectedInvoiceId = null;
        $this->editingWorkspaceUserId = null;
        $this->editingRateId = null;
        $this->editingOpportunityId = null;
        $this->editingQuoteId = null;
        $this->editingShipmentId = null;
        $this->editingProjectId = null;
        $this->editingDrawingId = null;
        $this->editingDeliveryId = null;
        $this->editingCarrierId = null;
        $this->editingBookingId = null;
        $this->editingCostingId = null;
        $this->editingInvoiceId = null;
        $this->resetManualOpportunityForm();
        $this->resetManualRateForm();
        $this->resetManualQuoteForm();
        $this->resetManualShipmentForm();
        $this->resetManualProjectForm();
        $this->resetManualDrawingForm();
        $this->resetManualDeliveryForm();
        $this->resetManualCarrierForm();
        $this->resetManualBookingForm();
        $this->resetManualCostingForm();
        $this->resetManualInvoiceForm();
        $this->resetShipmentMilestoneForm();
        $this->resetShipmentDocumentForm();
        $this->opportunityEditForm = [];
        $this->quoteEditForm = [];
        $this->rateEditForm = [];
        $this->shipmentEditForm = [];
        $this->projectEditForm = [];
        $this->drawingEditForm = [];
        $this->deliveryEditForm = [];
        $this->carrierEditForm = [];
        $this->bookingEditForm = [];
        $this->costingEditForm = [];
        $this->invoiceEditForm = [];
        $this->editingWorkspaceUserForm = [];
    }

    public function updatedSearch(): void
    {
        $this->resetPage('leadsPage');
        $this->resetPage('opportunitiesPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
        $this->selectedOpportunityId = null;
    }

    public function updatedContactSearch(): void
    {
        $this->resetPage('contactsPage');
        $this->selectedContactId = null;
    }

    public function updatedCustomerSearch(): void
    {
        $this->resetPage('customersPage');
        $this->selectedCustomerId = null;
    }

    public function updatedCustomerSegmentFilter(): void
    {
        $this->resetPage('customersPage');
        $this->selectedCustomerId = null;
    }

    public function updatedRateSearch(): void
    {
        $this->resetPage('ratesPage');
        $this->selectedRateId = null;
    }

    public function updatedQuoteSearch(): void
    {
        $this->resetPage('quotesPage');
        $this->selectedQuoteId = null;
    }

    public function updatedShipmentSearch(): void
    {
        $this->resetPage('shipmentsPage');
        $this->selectedShipmentId = null;
    }

    public function updatedCarrierSearch(): void
    {
        $this->resetPage('carriersPage');
        $this->selectedCarrierId = null;
    }

    public function updatedBookingSearch(): void
    {
        $this->resetPage('bookingsPage');
        $this->selectedBookingId = null;
    }

    public function updatedLeadStatusFilter(): void
    {
        $this->resetPage('leadsPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
    }

    public function updatedLeadSourceFilter(): void
    {
        $this->resetPage('leadsPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
    }

    public function updatedOpportunityStageFilter(): void
    {
        $this->resetPage('opportunitiesPage');
        $this->selectedOpportunityId = null;
    }

    public function updatedQuoteStatusFilter(): void
    {
        $this->resetPage('quotesPage');
        $this->selectedQuoteId = null;
    }

    public function updatedRateModeFilter(): void
    {
        $this->resetPage('ratesPage');
        $this->selectedRateId = null;
    }

    public function updatedInvoiceBookingFilter(): void
    {
        $this->resetPage('invoicesPage');
        $this->selectedInvoiceId = null;
    }

    public function updatedManualQuoteFormCustomerRecordId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualQuoteForm = [
                ...$this->manualQuoteForm,
                'customer_record_id' => '',
                'opportunity_id' => '',
                'lead_id' => '',
            ];

            return;
        }

        $customer = Account::query()
            ->with('contacts')
            ->where('workspace_id', $workspace->id)
            ->find((int) $value);

        if (! $customer) {
            $opportunity = Opportunity::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail((int) $value);

            $customer = $opportunity->account_id
                ? Account::query()->with('contacts')->where('workspace_id', $workspace->id)->findOrFail($opportunity->account_id)
                : abort(404);
        }

        $this->hydrateManualQuoteFromAccount($customer);
    }

    public function updatedManualQuoteFormOpportunityId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $customer = $this->selectedManualQuoteCustomer($workspace);

            $this->manualQuoteForm = [
                ...$this->manualQuoteForm,
                'opportunity_id' => '',
                'lead_id' => '',
            ];

            return;
        }

        $opportunity = Opportunity::query()
            ->with('lead')
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $value);

        $this->hydrateManualQuoteFromOpportunity($opportunity);
    }

    public function updatedManualQuoteFormRateCardId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualQuoteForm = [
                ...$this->manualQuoteForm,
                'rate_card_id' => '',
            ];

            return;
        }

        $rateCard = RateCard::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $value);

        $this->hydrateManualQuoteFromRateCard($rateCard);
    }

    public function updatedManualBookingFormShipmentJobId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualBookingForm = [
                ...$this->manualBookingForm,
                'shipment_job_id' => '',
                'quote_id' => '',
                'opportunity_id' => '',
                'lead_id' => '',
            ];

            return;
        }

        $shipment = ShipmentJob::query()
            ->with(['quote', 'opportunity', 'lead'])
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $value);

        $this->hydrateManualBookingFromShipment($shipment);
    }

    public function updatedManualBookingFormCarrierId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualBookingForm['carrier_id'] = '';

            return;
        }

        $carrier = Carrier::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $value);

        if (blank($this->manualBookingForm['notes'] ?? '')) {
            $lane = $carrier->service_lanes ? "Carrier lanes: {$carrier->service_lanes}" : null;
            $this->manualBookingForm['notes'] = $lane ?: ($carrier->notes ?: '');
        }
    }

    public function updatedManualCostingFormShipmentJobId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualCostingForm = [
                ...$this->manualCostingForm,
                'shipment_job_id' => '',
                'quote_id' => '',
                'opportunity_id' => '',
                'lead_id' => '',
                'customer_name' => '',
                'service_mode' => '',
                'currency' => 'AED',
                'lines' => $this->blankCostingLines(),
            ];

            return;
        }

        $shipment = ShipmentJob::query()
            ->with(['quote', 'opportunity', 'lead'])
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $value);

        $this->hydrateManualCostingFromShipment($shipment);
    }

    public function updatedManualInvoiceFormShipmentJobId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualInvoiceForm = [
                ...$this->manualInvoiceForm,
                'shipment_job_id' => '',
                'booking_id' => '',
                'job_costing_id' => '',
                'quote_id' => '',
                'opportunity_id' => '',
                'lead_id' => '',
                'invoice_type' => $this->manualInvoiceForm['invoice_type'] ?? Invoice::TYPE_ACCOUNTS_RECEIVABLE,
                'bill_to_name' => '',
                'contact_email' => '',
                'issue_date' => $this->manualInvoiceForm['issue_date'] ?? now()->toDateString(),
                'due_date' => $this->manualInvoiceForm['due_date'] ?? now()->addDays(14)->toDateString(),
                'currency' => $this->manualInvoiceForm['currency'] ?? 'AED',
                'subtotal_amount' => '',
                'tax_amount' => '0',
                'paid_amount' => '0',
                'total_amount' => '',
                'balance_amount' => '',
                'status' => $this->manualInvoiceForm['status'] ?? Invoice::STATUS_DRAFT,
                'lines' => $this->blankInvoiceLines(),
            ];

            return;
        }

        $shipment = ShipmentJob::query()
            ->with(['quote', 'opportunity', 'lead', 'bookings' => fn ($query) => $query->latest('id'), 'jobCostings' => fn ($query) => $query->latest('id')])
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $value);

        $this->hydrateManualInvoiceFromShipment($shipment);
    }

    public function updatedManualInvoiceFormBookingId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualInvoiceForm = [
                ...$this->manualInvoiceForm,
                'booking_id' => '',
            ];

            return;
        }

        $booking = Booking::query()
            ->with(['shipmentJob.jobCostings' => fn ($query) => $query->latest('id')])
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $value);

        $this->hydrateManualInvoiceFromBooking($booking);
    }

    public function updatedManualInvoiceFormJobCostingId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualInvoiceForm = [
                ...$this->manualInvoiceForm,
                'job_costing_id' => '',
                'status' => $this->manualInvoiceForm['status'] ?? Invoice::STATUS_DRAFT,
                'lines' => $this->blankInvoiceLines(),
            ];

            return;
        }

        $costing = JobCosting::query()
            ->with('lines')
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $value);

        $this->hydrateManualInvoiceFromCosting($costing);
    }

    public function updatedManualInvoiceFormInvoiceType($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank(data_get($this->manualInvoiceForm, 'job_costing_id'))) {
            data_set($this->manualInvoiceForm, 'lines', $this->blankInvoiceLines());
            $this->applyInvoiceLineTotalsToForm('manualInvoiceForm');

            return;
        }

        $costing = JobCosting::query()
            ->with('lines')
            ->where('workspace_id', $workspace->id)
            ->find((int) data_get($this->manualInvoiceForm, 'job_costing_id'));

        if (! $costing) {
            return;
        }

        data_set($this->manualInvoiceForm, 'lines', $this->invoiceLinesFromCosting($costing, (string) $value));
        $this->applyInvoiceLineTotalsToForm('manualInvoiceForm');
    }

    public function updatedInvoiceEditFormInvoiceType($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || ! $this->selectedInvoiceId) {
            return;
        }

        $invoice = Invoice::query()
            ->with('jobCosting.lines')
            ->where('workspace_id', $workspace->id)
            ->find($this->selectedInvoiceId);

        if (! $invoice || ! $invoice->jobCosting) {
            return;
        }

        data_set($this->invoiceEditForm, 'lines', $this->invoiceLinesFromCosting($invoice->jobCosting, (string) $value));
        $this->applyInvoiceLineTotalsToForm('invoiceEditForm');
    }

    public function updatedManualInvoiceFormTaxAmount(): void
    {
        $this->applyInvoiceLineTotalsToForm('manualInvoiceForm');
    }

    public function updatedManualInvoiceFormPaidAmount(): void
    {
        $this->applyInvoiceLineTotalsToForm('manualInvoiceForm');
    }

    public function updatedManualInvoiceFormLines(): void
    {
        $this->applyInvoiceLineTotalsToForm('manualInvoiceForm');
    }

    public function updatedInvoiceEditFormTaxAmount(): void
    {
        $this->applyInvoiceLineTotalsToForm('invoiceEditForm');
    }

    public function updatedInvoiceEditFormPaidAmount(): void
    {
        $this->applyInvoiceLineTotalsToForm('invoiceEditForm');
    }

    public function updatedInvoiceEditFormLines(): void
    {
        $this->applyInvoiceLineTotalsToForm('invoiceEditForm');
    }

    public function addCostingLine(string $scope = 'manual'): void
    {
        $formKey = $scope === 'edit' ? 'costingEditForm' : 'manualCostingForm';
        $lines = data_get($this->{$formKey}, 'lines', []);
        $lines[] = $this->blankCostingLine();
        data_set($this->{$formKey}, 'lines', array_values($lines));
    }

    public function addInvoiceLine(string $scope = 'manual'): void
    {
        $formKey = $scope === 'edit' ? 'invoiceEditForm' : 'manualInvoiceForm';
        $lines = data_get($this->{$formKey}, 'lines', []);
        $lines[] = $this->blankInvoiceLine();
        data_set($this->{$formKey}, 'lines', array_values($lines));
        $this->applyInvoiceLineTotalsToForm($formKey);
    }

    public function removeCostingLine(string $scope, int $index): void
    {
        $formKey = $scope === 'edit' ? 'costingEditForm' : 'manualCostingForm';
        $lines = data_get($this->{$formKey}, 'lines', []);
        unset($lines[$index]);
        $lines = array_values($lines);

        if ($lines === []) {
            $lines = $this->blankCostingLines();
        }

        data_set($this->{$formKey}, 'lines', $lines);
    }

    public function removeInvoiceLine(string $scope, int $index): void
    {
        $formKey = $scope === 'edit' ? 'invoiceEditForm' : 'manualInvoiceForm';
        $lines = data_get($this->{$formKey}, 'lines', []);
        unset($lines[$index]);
        $lines = array_values($lines);

        if ($lines === []) {
            $lines = $this->blankInvoiceLines();
        }

        data_set($this->{$formKey}, 'lines', $lines);
        $this->applyInvoiceLineTotalsToForm($formKey);
    }

    public function updatedShipmentSort(): void
    {
        $this->resetPage('shipmentsPage');
        $this->selectedShipmentId = null;
    }

    public function updatedShipmentStatusFilter(): void
    {
        $this->resetPage('shipmentsPage');
        $this->selectedShipmentId = null;
    }

    public function updatedProjectSearch(): void
    {
        $this->resetPage('projectsPage');
        $this->selectedProjectId = null;
    }

    public function updatedProjectStatusFilter(): void
    {
        $this->resetPage('projectsPage');
        $this->selectedProjectId = null;
    }

    public function updatedProjectSort(): void
    {
        $this->resetPage('projectsPage');
        $this->selectedProjectId = null;
    }

    public function updatedProjectPerPage(): void
    {
        $this->resetPage('projectsPage');
        $this->selectedProjectId = null;
    }

    public function updatedDrawingSearch(): void
    {
        $this->resetPage('drawingsPage');
        $this->selectedDrawingId = null;
    }

    public function updatedDrawingStatusFilter(): void
    {
        $this->resetPage('drawingsPage');
        $this->selectedDrawingId = null;
    }

    public function updatedDrawingSort(): void
    {
        $this->resetPage('drawingsPage');
        $this->selectedDrawingId = null;
    }

    public function updatedDrawingPerPage(): void
    {
        $this->resetPage('drawingsPage');
        $this->selectedDrawingId = null;
    }

    public function updatedDeliverySearch(): void
    {
        $this->resetPage('deliveryPage');
        $this->selectedDeliveryId = null;
    }

    public function updatedDeliveryStatusFilter(): void
    {
        $this->resetPage('deliveryPage');
        $this->selectedDeliveryId = null;
    }

    public function updatedDeliverySort(): void
    {
        $this->resetPage('deliveryPage');
        $this->selectedDeliveryId = null;
    }

    public function updatedDeliveryPerPage(): void
    {
        $this->resetPage('deliveryPage');
        $this->selectedDeliveryId = null;
    }

    public function updatedCarrierModeFilter(): void
    {
        $this->resetPage('carriersPage');
        $this->selectedCarrierId = null;
    }

    public function updatedBookingStatusFilter(): void
    {
        $this->resetPage('bookingsPage');
        $this->selectedBookingId = null;
    }

    public function updatedCostingSearch(): void
    {
        $this->resetPage('costingsPage');
        $this->selectedCostingId = null;
    }

    public function updatedInvoiceSearch(): void
    {
        $this->resetPage('invoicesPage');
        $this->selectedInvoiceId = null;
    }

    public function updatedCostingStatusFilter(): void
    {
        $this->resetPage('costingsPage');
        $this->selectedCostingId = null;
    }

    public function updatedInvoiceStatusFilter(): void
    {
        $this->resetPage('invoicesPage');
        $this->selectedInvoiceId = null;
    }

    public function updatedCostingSort(): void
    {
        $this->resetPage('costingsPage');
        $this->selectedCostingId = null;
    }

    public function updatedInvoiceSort(): void
    {
        $this->resetPage('invoicesPage');
        $this->selectedInvoiceId = null;
    }

    public function updatedCostingPerPage(): void
    {
        $this->resetPage('costingsPage');
        $this->selectedCostingId = null;
    }

    public function updatedInvoicePerPage(): void
    {
        $this->resetPage('invoicesPage');
        $this->selectedInvoiceId = null;
    }

    public function updatedManualShipmentFormCustomerRecordId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualShipmentForm = [
                ...$this->manualShipmentForm,
                'customer_record_id' => '',
                'opportunity_id' => '',
                'quote_id' => '',
                'lead_id' => '',
            ];

            return;
        }

        $customer = Account::query()
            ->with('contacts')
            ->where('workspace_id', $workspace->id)
            ->find((int) $value);

        if (! $customer) {
            $opportunity = Opportunity::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail((int) $value);

            $customer = $opportunity->account_id
                ? Account::query()->with('contacts')->where('workspace_id', $workspace->id)->findOrFail($opportunity->account_id)
                : abort(404);
        }

        $primaryContact = $customer->contacts->sortByDesc('last_activity_at')->first();

        $this->manualShipmentForm = [
            ...$this->manualShipmentForm,
            'customer_record_id' => (string) $customer->id,
            'opportunity_id' => '',
            'quote_id' => '',
            'lead_id' => '',
            'company_name' => $customer->name ?: '',
            'contact_name' => $primaryContact?->full_name ?: '',
            'contact_email' => $primaryContact?->email ?: ($customer->primary_email ?: ''),
            'service_mode' => $customer->latest_service ?: 'Ocean Freight',
            'origin' => '',
            'destination' => '',
            'incoterm' => '',
            'commodity' => '',
            'equipment_type' => '',
            'weight_kg' => '',
            'volume_cbm' => '',
            'buy_amount' => '',
            'sell_amount' => '',
            'notes' => $customer->notes ?: '',
        ];
    }

    public function updatedManualShipmentFormOpportunityId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $customer = $this->selectedManualShipmentCustomer($workspace);

            $this->manualShipmentForm = [
                ...$this->manualShipmentForm,
                'opportunity_id' => '',
                'quote_id' => '',
                'lead_id' => '',
            ];

            return;
        }

        $opportunity = Opportunity::query()
            ->with([
                'lead',
                'quotes' => fn ($query) => $query->orderByDesc('quoted_at')->orderByDesc('created_at'),
            ])
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $value);

        $this->hydrateManualShipmentFromOpportunity($opportunity);
    }

    public function updatedManualShipmentFormQuoteId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualShipmentForm = [
                ...$this->manualShipmentForm,
                'quote_id' => '',
            ];

            return;
        }

        $quote = Quote::query()
            ->with(['lead', 'opportunity'])
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $value);

        $this->hydrateManualShipmentFromQuote($quote);
    }

    public function updatedManualProjectFormCustomerRecordId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualProjectForm = [
                ...$this->manualProjectForm,
                'customer_record_id' => '',
                'opportunity_id' => '',
                'lead_id' => '',
            ];

            return;
        }

        $customer = Account::query()
            ->with('contacts')
            ->where('workspace_id', $workspace->id)
            ->find((int) $value);

        if (! $customer) {
            abort(404);
        }

        $this->hydrateManualProjectFromAccount($customer);
    }

    public function updatedManualProjectFormOpportunityId($value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace || blank($value)) {
            $this->manualProjectForm = [
                ...$this->manualProjectForm,
                'opportunity_id' => '',
                'lead_id' => '',
            ];

            return;
        }

        $opportunity = Opportunity::query()
            ->with(['lead', 'account.contacts'])
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $value);

        $this->hydrateManualProjectFromOpportunity($opportunity);
    }

    public function updatedManualDrawingFormProjectId($value): void
    {
        if (blank($value)) {
            $this->manualDrawingForm = [
                ...$this->manualDrawingForm,
                'project_id' => '',
            ];
        }
    }

    public function updatedManualDeliveryFormProjectId($value): void
    {
        if (blank($value)) {
            $this->manualDeliveryForm = [
                ...$this->manualDeliveryForm,
                'project_id' => '',
            ];
        }
    }

    public function updatedSourceFormSourceKind(string $value): void
    {
        $this->sourceForm = [
            ...$this->sourceForm,
            ...$this->defaultSourceConnectionFields(),
            'source_kind' => $value,
            'url' => $value === SheetSource::SOURCE_KIND_CARGOWISE_API ? '' : ($this->sourceForm['url'] ?? ''),
        ];
    }

    public function updatedSourceFormCargoAuthMode(string $value): void
    {
        if ($value === 'bearer') {
            $this->sourceForm['cargo_username'] = '';
            $this->sourceForm['cargo_password'] = '';
        }

        if ($value === 'basic') {
            $this->sourceForm['cargo_token'] = '';
        }
    }

    public function updatedEditingSourceFormSourceKind(string $value): void
    {
        $this->editingSourceForm = [
            ...$this->editingSourceForm,
            ...$this->defaultSourceConnectionFields(),
            'source_kind' => $value,
            'url' => $value === SheetSource::SOURCE_KIND_CARGOWISE_API ? '' : ($this->editingSourceForm['url'] ?? ''),
        ];
    }

    public function updatedEditingSourceFormCargoAuthMode(string $value): void
    {
        if ($value === 'bearer') {
            $this->editingSourceForm['cargo_username'] = '';
            $this->editingSourceForm['cargo_password'] = '';
        }

        if ($value === 'basic') {
            $this->editingSourceForm['cargo_token'] = '';
        }
    }

    public function updatedLeadSort(): void
    {
        $this->resetPage('leadsPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
    }

    public function updatedOpportunitySort(): void
    {
        $this->resetPage('opportunitiesPage');
        $this->selectedOpportunityId = null;
    }

    public function updatedQuoteSort(): void
    {
        $this->resetPage('quotesPage');
        $this->selectedQuoteId = null;
    }

    public function updatedRateSort(): void
    {
        $this->resetPage('ratesPage');
        $this->selectedRateId = null;
    }

    public function updatedLeadPerPage(): void
    {
        $this->resetPage('leadsPage');
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
    }

    public function updatedActiveTab(string $value): void
    {
        if ($value !== 'leads') {
            $this->selectedLeadId = null;
        }

        if ($value !== 'contacts') {
            $this->selectedContactId = null;
        }

        if ($value !== 'customers') {
            $this->selectedCustomerId = null;
        }

        if ($value !== 'opportunities') {
            $this->selectedOpportunityId = null;
            $this->opportunityEditForm = [];
        }

        if (! in_array($value, ['quotes', 'manual-quote'], true)) {
            $this->selectedQuoteId = null;
            $this->quoteEditForm = [];
        }

        if (! in_array($value, ['rates', 'manual-rate'], true)) {
            $this->selectedRateId = null;
            $this->rateEditForm = [];
        }

        if (! in_array($value, ['shipments', 'manual-shipment'], true)) {
            $this->selectedShipmentId = null;
            $this->shipmentEditForm = [];
            $this->resetShipmentMilestoneForm();
            $this->resetShipmentDocumentForm();
        }

        if (! in_array($value, ['projects', 'manual-project'], true)) {
            $this->selectedProjectId = null;
            $this->projectEditForm = [];
        }

        if (! in_array($value, ['drawings', 'manual-drawing'], true)) {
            $this->selectedDrawingId = null;
            $this->drawingEditForm = [];
        }

        if (! in_array($value, ['delivery_tracking', 'manual-delivery'], true)) {
            $this->selectedDeliveryId = null;
            $this->deliveryEditForm = [];
        }

        if (! in_array($value, ['carriers', 'manual-carrier'], true)) {
            $this->selectedCarrierId = null;
            $this->carrierEditForm = [];
        }

        if (! in_array($value, ['bookings', 'manual-booking'], true)) {
            $this->selectedBookingId = null;
            $this->bookingEditForm = [];
        }

        if (! in_array($value, ['costings', 'manual-costing'], true)) {
            $this->selectedCostingId = null;
            $this->costingEditForm = [];
        }

        if (! in_array($value, ['invoices', 'manual-invoice'], true)) {
            $this->selectedInvoiceId = null;
            $this->invoiceEditForm = [];
        }

        if ($value !== 'access') {
            $this->editingWorkspaceUserId = null;
            $this->editingWorkspaceUserForm = [];
        }

        if ($value !== 'leads') {
            $this->pendingDisqualificationLeadId = null;
        }
    }

    public function setSettingsTab(string $tab): void
    {
        $allowedTabs = ['notifications'];

        if ($this->currentWorkspace() && $this->canManageWorkspaceAccess($this->currentWorkspace())) {
            $allowedTabs = ['general', 'notifications', 'segmentations'];
        }

        if (! in_array($tab, $allowedTabs, true)) {
            return;
        }

        $this->settingsTab = $tab;
    }

    public function openTemplateModule(string $module): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace) {
            return;
        }

        $allowedTabs = array_keys($this->availableTabsForWorkspace(
            $workspace,
            $this->canManageWorkspaceAccess($workspace),
            true,
            $this->canManageWorkspaceAccess($workspace) || auth()->user()->hasRole(['admin', 'manager']),
        ));

        if (! in_array($module, $allowedTabs, true)) {
            return;
        }

        $this->activeTab = $module;
    }

    public function updatedOpportunityPerPage(): void
    {
        $this->resetPage('opportunitiesPage');
        $this->selectedOpportunityId = null;
    }

    public function updatedQuotePerPage(): void
    {
        $this->resetPage('quotesPage');
        $this->selectedQuoteId = null;
    }

    public function updatedRatePerPage(): void
    {
        $this->resetPage('ratesPage');
        $this->selectedRateId = null;
    }

    public function updatedShipmentPerPage(): void
    {
        $this->resetPage('shipmentsPage');
        $this->selectedShipmentId = null;
    }

    public function updatedCarrierSort(): void
    {
        $this->resetPage('carriersPage');
        $this->selectedCarrierId = null;
    }

    public function updatedBookingSort(): void
    {
        $this->resetPage('bookingsPage');
        $this->selectedBookingId = null;
    }

    public function updatedCarrierPerPage(): void
    {
        $this->resetPage('carriersPage');
        $this->selectedCarrierId = null;
    }

    public function updatedBookingPerPage(): void
    {
        $this->resetPage('bookingsPage');
        $this->selectedBookingId = null;
    }

    public function updatedContactSort(): void
    {
        $this->resetPage('contactsPage');
        $this->selectedContactId = null;
    }

    public function updatedCustomerSort(): void
    {
        $this->resetPage('customersPage');
        $this->selectedCustomerId = null;
    }

    public function updatedContactPerPage(): void
    {
        $this->resetPage('contactsPage');
        $this->selectedContactId = null;
    }

    public function updatedCustomerPerPage(): void
    {
        $this->resetPage('customersPage');
        $this->selectedCustomerId = null;
    }

    public function updatedAnalyticsRange(): void
    {
        if ($this->analyticsRange === 'month' && $this->analyticsMonth === '') {
            $this->analyticsMonth = $this->defaultAnalyticsMonth();
        }
    }

    public function updatedAnalyticsBreakdown(): void
    {
        // Analytics uses in-memory cards and tables, so no paginator reset is needed.
    }

    public function updatedWorkspaceSettingsFormTemplateKey(string $value): void
    {
        $workspace = $this->currentWorkspace();

        if (! $workspace) {
            return;
        }

        $this->workspaceSettingsForm['template_key'] = $workspace->templateKey();
    }

    public function saveCompany(): void
    {
        abort_unless(auth()->user()->isAdmin() || $this->canBootstrapWorkspace(), 403);

        $validated = validator($this->companyForm, [
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:100'],
        ])->validate();

        $company = Company::create([
            ...$validated,
            'slug' => $this->uniqueSlug(Company::class, $validated['name']),
            'is_active' => true,
        ]);

        $this->companyForm = [
            ...$this->companyForm,
            'name' => '',
            'contact_email' => '',
            'contact_phone' => '',
        ];

        $this->workspaceForm['company_id'] = $company->id;
        $this->flash("Company {$company->name} created.");
    }

    public function saveWorkspace(): void
    {
        abort_unless(auth()->user()->isAdmin() || $this->canBootstrapWorkspace(), 403);

        $validated = validator($this->workspaceForm, [
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'template_key' => ['required', Rule::in(array_keys(Workspace::workspaceTemplates()))],
        ])->validate();

        $workspace = Workspace::create([
            ...collect($validated)->except('template_key')->all(),
            'slug' => $this->uniqueSlug(
                Workspace::class,
                $validated['name'],
                ['company_id' => $validated['company_id']],
            ),
            'is_default' => Workspace::where('company_id', $validated['company_id'])->doesntExist(),
            'settings' => Workspace::applyTemplateSettings(null, $validated['template_key']),
        ]);

        if ($this->canBootstrapWorkspace()) {
            $this->bootstrapWorkspaceOwner($workspace);
        }

        $this->workspaceId = $workspace->id;
        $this->workspaceForm['name'] = '';
        $this->workspaceForm['description'] = '';
        $this->workspaceForm['template_key'] = Workspace::defaultTemplateKey();
        $this->primeForms($workspace);
        $this->flash("Workspace {$workspace->name} created.");
    }

    public function startWorkspace(): void
    {
        abort_unless($this->canBootstrapWorkspace(), 403);

        $validated = validator([
            'company' => $this->companyForm,
            'workspace' => $this->workspaceForm,
        ], [
            'company.name' => ['required', 'string', 'max:255'],
            'company.industry' => ['required', 'string', 'max:255'],
            'company.contact_email' => ['nullable', 'email'],
            'company.contact_phone' => ['nullable', 'string', 'max:255'],
            'company.timezone' => ['required', 'string', 'max:100'],
            'workspace.name' => ['required', 'string', 'max:255'],
            'workspace.description' => ['nullable', 'string', 'max:255'],
            'workspace.template_key' => ['required', Rule::in(array_keys(Workspace::workspaceTemplates()))],
        ])->validate();

        $company = Company::create([
            'name' => $validated['company']['name'],
            'slug' => $this->uniqueSlug(Company::class, $validated['company']['name']),
            'industry' => $validated['company']['industry'],
            'contact_email' => $validated['company']['contact_email'] ?: null,
            'contact_phone' => $validated['company']['contact_phone'] ?: null,
            'timezone' => $validated['company']['timezone'],
            'is_active' => true,
        ]);

        $workspace = Workspace::create([
            'company_id' => $company->id,
            'name' => $validated['workspace']['name'],
            'slug' => $this->uniqueSlug(
                Workspace::class,
                $validated['workspace']['name'],
                ['company_id' => $company->id],
            ),
            'description' => $validated['workspace']['description'] ?: null,
            'is_default' => true,
            'settings' => Workspace::applyTemplateSettings(null, $validated['workspace']['template_key']),
        ]);

        $this->bootstrapWorkspaceOwner($workspace);

        $this->workspaceId = $workspace->id;
        $this->resetForms();
        $this->primeForms($workspace);
        $this->flash("Workspace {$workspace->name} is ready. Add your first source next.");
    }

    public function saveSheetSource(): void
    {
        $this->ensureWorkspaceManager();

        $workspaceValidation = validator($this->sourceForm, [
            'workspace_id' => ['required', 'exists:workspaces,id'],
        ])->validate();

        $workspace = Workspace::query()->with('company')->findOrFail($workspaceValidation['workspace_id']);

        $this->ensureWorkspaceVisible($workspace->id);

        $validated = validator($this->sourceForm, [
            'workspace_id' => ['required', 'exists:workspaces,id'],
            'type' => ['required', Rule::in(array_keys(SheetSource::availableTypesForWorkspace($workspace)))],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url'],
            'description' => ['nullable', 'string', 'max:255'],
            'source_kind' => ['required', Rule::in(SheetSource::SOURCE_KINDS)],
            'is_active' => ['boolean'],
            'cargo_auth_mode' => ['nullable', Rule::in(array_keys(SheetSource::cargoWiseAuthModes()))],
            'cargo_username' => ['nullable', 'string', 'max:255'],
            'cargo_password' => ['nullable', 'string', 'max:2048'],
            'cargo_token' => ['nullable', 'string', 'max:4096'],
            'cargo_format' => ['nullable', Rule::in(array_keys(SheetSource::cargoWiseFormats()))],
            'cargo_data_path' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $sourceKind = SheetSource::normalizeSourceKind(
            $validated['source_kind'],
            $validated['url'],
        );

        SheetSource::create([
            ...$validated,
            'company_id' => $workspace->company_id,
            'source_kind' => $sourceKind,
            'sync_status' => 'idle',
            'mapping' => $this->sourceMappingFromForm($validated),
        ]);

        $this->sourceForm = [
            ...$this->sourceForm,
            ...$this->defaultSourceConnectionFields(),
            'name' => '',
            'url' => '',
            'description' => '',
        ];
        $this->flash('Sheet source added.');
    }

    public function createRole(): void
    {
        $this->ensureWorkspaceOwner();

        $validated = validator($this->roleForm, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', Rule::unique('roles', 'slug')],
            'description' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:99'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ])->validate();

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'],
            'level' => $validated['level'],
        ]);

        $role->syncPermissions(
            collect($validated['permission_ids'] ?? [])->map(fn ($id) => (int) $id)->all()
        );

        $this->roleForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'level' => 3,
            'permission_ids' => [],
        ];

        $this->flash('Role created.');
    }

    public function createUser(): void
    {
        $this->ensureWorkspaceOwner();
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->userForm, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(Role::query()->pluck('slug')->all())],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ])->validate();

        $user = User::create([
            'company_id' => $workspace->company_id,
            'default_workspace_id' => $workspace->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'job_title' => $validated['job_title'],
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->workspaces()->sync([
            $workspace->id => [
                'job_title' => $validated['job_title'],
                'is_owner' => false,
            ],
        ]);

        $role = Role::query()->where('slug', $validated['role'])->firstOrFail();
        $user->syncRoles([$role]);
        $user->syncPermissions(
            collect($validated['permission_ids'] ?? [])->map(fn ($id) => (int) $id)->all()
        );

        $this->userForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'job_title' => '',
            'role' => 'sales',
            'permission_ids' => [],
        ];

        $this->flash("User {$user->email} created.");
    }

    public function createPermission(): void
    {
        $this->ensureWorkspaceOwner();

        $validated = validator($this->permissionForm, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', Rule::unique('permissions', 'slug')],
            'description' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
        ])->validate();

        Permission::create($validated);

        $this->permissionForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'model' => 'User',
        ];

        $this->flash('Permission created.');
    }

    public function saveWorkspaceSettings(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_unless($this->canManageWorkspaceAccess($workspace), 403);

        $currentTemplateKey = $workspace->templateKey();

        $validated = validator($this->workspaceSettingsForm, [
            'lead_status_labels' => ['required', 'array'],
            'lead_status_labels.*' => ['required', 'string', 'max:255'],
            'opportunity_stage_labels' => ['required', 'array'],
            'opportunity_stage_labels.*' => ['required', 'string', 'max:255'],
            'disqualification_reasons' => ['required', 'array', 'min:1'],
            'disqualification_reasons.*' => ['required', 'string', 'max:255'],
            'lead_sources' => ['required', 'array', 'min:1'],
            'lead_sources.*' => ['required', 'string', 'max:255'],
            'lead_services' => ['required', 'array', 'min:1'],
            'lead_services.*' => ['required', 'string', 'max:255'],
            'segment_definitions' => ['required', 'array', 'min:1'],
            'segment_definitions.*.name' => ['required', 'string', 'max:255'],
            'segment_definitions.*.description' => ['nullable', 'string', 'max:1000'],
            'segment_definitions.*.color' => ['required', Rule::in(CustomerSegmentDefinition::COLORS)],
            'segment_definitions.*.priority' => ['required', 'integer', 'between:0,999'],
            'segment_definitions.*.is_active' => ['nullable', 'boolean'],
            'segment_definitions.*.rules' => ['required', 'array', 'min:1'],
            'segment_definitions.*.rules.*.metric_key' => ['required', Rule::in(array_keys(CustomerSegmentationService::metricCatalog()))],
            'segment_definitions.*.rules.*.operator' => ['required', Rule::in(array_keys(CustomerSegmentationService::operatorCatalog()))],
            'segment_definitions.*.rules.*.threshold_value' => ['required', 'numeric'],
        ])->validate();

        $settings = Workspace::applyTemplateSettings(
            $workspace->settings ?? [],
            $currentTemplateKey,
            false,
        );

        data_set($settings, Workspace::CRM_VOCABULARY_KEY, [
            'lead_status_labels' => $this->sanitizeLabelMap(
                $validated['lead_status_labels'],
                array_keys(Workspace::defaultLeadStatusLabels($currentTemplateKey)),
            ),
            'opportunity_stage_labels' => $this->sanitizeLabelMap(
                $validated['opportunity_stage_labels'],
                array_keys(Workspace::defaultOpportunityStageLabels($currentTemplateKey)),
            ),
            'disqualification_reasons' => $this->sanitizeList($validated['disqualification_reasons']),
            'lead_sources' => $this->sanitizeList($validated['lead_sources']),
            'lead_services' => $this->sanitizeList($validated['lead_services']),
        ]);

        $segmentDefinitions = $this->normalizeSegmentDefinitions($validated['segment_definitions']);

        DB::transaction(function () use ($workspace, $settings, $segmentDefinitions) {
            $workspace->forceFill(['settings' => $settings])->save();

            $workspace->segmentDefinitions()->delete();

            foreach ($segmentDefinitions as $segmentDefinition) {
                $segment = $workspace->segmentDefinitions()->create([
                    'company_id' => $workspace->company_id,
                    'name' => $segmentDefinition['name'],
                    'slug' => $segmentDefinition['slug'],
                    'description' => $segmentDefinition['description'],
                    'color' => $segmentDefinition['color'],
                    'priority' => $segmentDefinition['priority'],
                    'is_active' => $segmentDefinition['is_active'],
                ]);

                foreach ($segmentDefinition['rules'] as $index => $rule) {
                    $segment->rules()->create([
                        'metric_key' => $rule['metric_key'],
                        'operator' => $rule['operator'],
                        'threshold_value' => $rule['threshold_value'],
                        'sort_order' => $index,
                    ]);
                }
            }
        });

        app(CustomerSegmentationService::class)->syncWorkspace($workspace->fresh());

        $this->primeForms($workspace->fresh('company'));
        $this->customerSegmentFilter = '';
        $this->flash("Workspace settings updated for {$workspace->name}.");
    }

    public function saveNotificationSettings(): void
    {
        $workspace = $this->currentWorkspaceOrFail();
        $membership = $this->currentWorkspaceMembership($workspace);

        abort_if(! $membership, 403);

        $validated = validator($this->notificationSettingsForm, [
            'channels' => ['required', 'array'],
            'channels.in_app' => ['nullable', 'boolean'],
            'channels.email' => ['nullable', 'boolean'],
            'events' => ['required', 'array'],
            'events.assignment' => ['nullable', 'boolean'],
            'events.note' => ['nullable', 'boolean'],
            'events.message' => ['nullable', 'boolean'],
        ])->validate();

        $preferences = WorkspaceMembership::normalizeNotificationPreferences($validated);

        $membership->forceFill([
            'notification_preferences' => $preferences,
        ])->save();

        $this->notificationSettingsForm = $preferences;
        $this->flash('Notification preferences updated.');
    }

    public function exportLeadsCsv(): StreamedResponse
    {
        $workspace = $this->currentWorkspaceOrFail();
        $this->ensureWorkspaceOwner();

        $rows = $this->applyLeadSorting($this->buildLeadQuery($workspace))
            ->get()
            ->map(fn (Lead $lead) => [
                $lead->lead_id ?: $lead->external_key,
                $lead->company_name ?: '',
                $lead->contact_name ?: '',
                $lead->email ?: '',
                $lead->phone ?: '',
                $lead->lead_source ?: '',
                $lead->service ?: '',
                $this->leadStatusLabel($lead->status, $workspace),
                $lead->disqualification_reason ?: '',
                $lead->lead_value !== null ? (string) $lead->lead_value : '',
                optional($lead->submission_date)->format('Y-m-d H:i:s') ?: '',
                $lead->assignedUser?->name ?: '',
                (string) $lead->opportunities_count,
            ])
            ->all();

        return $this->streamCsv(
            $this->workspaceExportFilename($workspace, 'leads'),
            ['Lead ID', 'Company', 'Contact', 'Email', 'Phone', 'Source', 'Service', 'Status', 'Disqualification Reason', 'Lead Value', 'Submission Date', 'Owner', 'Linked Opportunities'],
            $rows,
        );
    }

    public function exportOpportunitiesCsv(): StreamedResponse
    {
        $workspace = $this->currentWorkspaceOrFail();
        $this->ensureWorkspaceOwner();

        $rows = $this->applyOpportunitySorting($this->buildOpportunityQuery($workspace))
            ->get()
            ->map(fn (Opportunity $opportunity) => [
                $opportunity->external_key ?: '',
                $opportunity->company_name ?: '',
                $opportunity->contact_email ?: '',
                $opportunity->lead_source ?: '',
                $opportunity->required_service ?: '',
                $opportunity->revenue_potential !== null ? (string) $opportunity->revenue_potential : '',
                $opportunity->project_timeline_days !== null ? (string) $opportunity->project_timeline_days : '',
                $this->opportunityStageLabel($opportunity->sales_stage, $workspace),
                optional($opportunity->submission_date)->format('Y-m-d H:i:s') ?: '',
                $opportunity->assignedUser?->name ?: '',
                $opportunity->notes ?: '',
            ])
            ->all();

        return $this->streamCsv(
            $this->workspaceExportFilename($workspace, 'opportunities'),
            ['Opportunity Key', 'Company', 'Contact Email', 'Source', 'Required Service', 'Revenue Potential', 'Timeline Days', 'Stage', 'Submission Date', 'Owner', 'Notes'],
            $rows,
        );
    }

    public function exportContactsCsv(): StreamedResponse
    {
        $workspace = $this->currentWorkspaceOrFail();
        $this->ensureWorkspaceOwner();

        $rows = $this->applyContactSorting($this->buildContactsQuery($workspace))
            ->get()
            ->map(fn (Contact $contact) => [
                $contact->full_name ?: '',
                $contact->account?->name ?: '',
                $contact->email ?: '',
                $contact->phone ?: '',
                (string) $contact->leads_count,
                (string) $contact->opportunities_count,
                (string) $contact->quotes_count,
                optional($contact->last_activity_at)->format('Y-m-d H:i:s') ?: '',
            ])
            ->all();

        return $this->streamCsv(
            $this->workspaceExportFilename($workspace, 'contacts'),
            ['Contact', 'Account', 'Email', 'Phone', 'Leads', 'Opportunities', 'Quotes', 'Last Activity'],
            $rows,
        );
    }

    public function exportCustomersCsv(): StreamedResponse
    {
        $workspace = $this->currentWorkspaceOrFail();
        $this->ensureWorkspaceOwner();

        $rows = $this->applyCustomerSorting($this->buildCustomersQuery($workspace))
            ->get()
            ->map(fn (Account $customer) => [
                $customer->name ?: '',
                $customer->primary_email ?: '',
                $customer->latest_service ?: '',
                (string) $customer->contacts_count,
                (string) $customer->opportunities_count,
                (string) $customer->quotes_count,
                (string) $customer->shipment_jobs_count,
                (string) $customer->invoices_count,
                $customer->opportunity_revenue_sum !== null ? (string) $customer->opportunity_revenue_sum : '',
                optional($customer->last_activity_at)->format('Y-m-d H:i:s') ?: '',
            ])
            ->all();

        return $this->streamCsv(
            $this->workspaceExportFilename($workspace, 'customers'),
            ['Account', 'Primary Email', 'Latest Service', 'Contacts', 'Opportunities', 'Quotes', 'Shipments', 'Invoices', 'Tracked Revenue', 'Last Activity'],
            $rows,
        );
    }

    public function addWorkspaceSettingItem(string $field): void
    {
        $this->ensureWorkspaceOwner();

        if (! in_array($field, ['disqualification_reasons', 'lead_sources', 'lead_services'], true)) {
            abort(404);
        }

        $this->workspaceSettingsForm[$field][] = '';
    }

    public function removeWorkspaceSettingItem(string $field, int $index): void
    {
        $this->ensureWorkspaceOwner();

        if (! in_array($field, ['disqualification_reasons', 'lead_sources', 'lead_services'], true)) {
            abort(404);
        }

        $items = $this->workspaceSettingsForm[$field] ?? [];
        unset($items[$index]);
        $items = array_values($items);

        if ($items === []) {
            $items = match ($field) {
                'disqualification_reasons' => Lead::DISQUALIFICATION_REASONS,
                'lead_sources' => Workspace::defaultLeadSources(),
                'lead_services' => Workspace::defaultLeadServices(),
                default => [''],
            };
        }

        $this->workspaceSettingsForm[$field] = $items;
    }

    public function addWorkspaceSegmentDefinition(): void
    {
        $this->ensureWorkspaceOwner();

        $this->workspaceSettingsForm['segment_definitions'][] = $this->emptySegmentDefinitionForm();
    }

    public function removeWorkspaceSegmentDefinition(int $index): void
    {
        $this->ensureWorkspaceOwner();

        $definitions = $this->workspaceSettingsForm['segment_definitions'] ?? [];
        unset($definitions[$index]);
        $definitions = array_values($definitions);

        if ($definitions === []) {
            $definitions = $this->defaultSegmentDefinitionsForTemplate(
                (string) ($this->workspaceSettingsForm['template_key'] ?? Workspace::defaultTemplateKey()),
            );
        }

        $this->workspaceSettingsForm['segment_definitions'] = $definitions;
    }

    public function addWorkspaceSegmentRule(int $segmentIndex): void
    {
        $this->ensureWorkspaceOwner();

        if (! isset($this->workspaceSettingsForm['segment_definitions'][$segmentIndex])) {
            abort(404);
        }

        $this->workspaceSettingsForm['segment_definitions'][$segmentIndex]['rules'][] = $this->emptySegmentRuleForm();
    }

    public function removeWorkspaceSegmentRule(int $segmentIndex, int $ruleIndex): void
    {
        $this->ensureWorkspaceOwner();

        if (! isset($this->workspaceSettingsForm['segment_definitions'][$segmentIndex])) {
            abort(404);
        }

        $rules = $this->workspaceSettingsForm['segment_definitions'][$segmentIndex]['rules'] ?? [];
        unset($rules[$ruleIndex]);
        $rules = array_values($rules);

        if ($rules === []) {
            $rules = [$this->emptySegmentRuleForm()];
        }

        $this->workspaceSettingsForm['segment_definitions'][$segmentIndex]['rules'] = $rules;
    }

    public function startEditingWorkspaceUser(int $userId): void
    {
        $this->ensureWorkspaceOwner();

        $workspace = $this->currentWorkspaceOrFail();

        $user = $workspace->users()
            ->with(['roles', 'userPermissions'])
            ->where('users.id', $userId)
            ->firstOrFail();

        $this->editingWorkspaceUserId = $user->id;
        $this->editingWorkspaceUserForm = [
            'job_title' => $user->pivot->job_title ?? '',
            'role' => $user->roles->pluck('slug')->first() ?: 'sales',
            'permission_ids' => $user->userPermissions->pluck('id')->map(fn ($id) => (string) $id)->all(),
        ];
    }

    public function cancelEditingWorkspaceUser(): void
    {
        $this->editingWorkspaceUserId = null;
        $this->editingWorkspaceUserForm = [];
    }

    public function updateWorkspaceUserAccess(): void
    {
        $this->ensureWorkspaceOwner();

        abort_if(! $this->editingWorkspaceUserId, 404);

        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->editingWorkspaceUserForm, [
            'job_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in(Role::query()->pluck('slug')->all())],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ])->validate();

        $user = $workspace->users()
            ->where('users.id', $this->editingWorkspaceUserId)
            ->firstOrFail();

        $workspace->users()->updateExistingPivot($user->id, [
            'job_title' => $validated['job_title'],
        ]);

        $role = Role::query()->where('slug', $validated['role'])->firstOrFail();
        $user->syncRoles([$role]);
        $user->syncPermissions(
            collect($validated['permission_ids'] ?? [])->map(fn ($id) => (int) $id)->all()
        );

        $this->cancelEditingWorkspaceUser();
        $this->flash("Workspace access updated for {$user->email}.");
    }

    public function addManualLead(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualLeadForm, [
            'contact_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'service' => ['required', 'string', 'max:255'],
            'lead_source' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys($this->leadStatusOptions($workspace)))],
            'lead_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        Lead::create([
            'company_id' => $workspace->company_id,
            'workspace_id' => $workspace->id,
            'assigned_user_id' => auth()->id(),
            'external_key' => 'manual-'.Str::ulid(),
            'lead_id' => 'MANUAL-'.Str::upper(Str::random(6)),
            'submission_date' => now(),
            'manual_entry' => true,
            ...$validated,
        ]);

        $this->manualLeadForm = [
            ...$this->manualLeadForm,
            'contact_name' => '',
            'company_name' => '',
            'email' => '',
            'phone' => '',
            'notes' => '',
            'lead_value' => '',
        ];

        $this->flash('Manual lead added.');
    }

    public function addManualOpportunity(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualOpportunityForm, [
            'lead_id' => ['nullable', 'exists:leads,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'lead_source' => ['required', 'string', 'max:255'],
            'required_service' => ['required', 'string', 'max:255'],
            'revenue_potential' => ['nullable', 'numeric', 'min:0'],
            'project_timeline_days' => ['nullable', 'integer', 'min:0'],
            'sales_stage' => ['required', Rule::in(array_keys($this->opportunityStageOptions($workspace)))],
            'notes' => ['nullable', 'string'],
        ])->validate();

        if ($this->editingOpportunityId) {
            $opportunity = Opportunity::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingOpportunityId);

            $opportunity->update([
                ...$validated,
                'assigned_user_id' => $opportunity->assigned_user_id ?: auth()->id(),
                'manual_entry' => true,
                'year_month' => $opportunity->year_month ?: now()->format('M-y'),
            ]);

            $message = 'Opportunity updated.';
        } else {
            Opportunity::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'assigned_user_id' => auth()->id(),
                'external_key' => 'manual-'.Str::ulid(),
                'submission_date' => now(),
                'year_month' => now()->format('M-y'),
                'manual_entry' => true,
                ...$validated,
            ]);

            $message = 'Manual opportunity added.';
        }

        $this->editingOpportunityId = null;
        $this->resetManualOpportunityForm();
        $this->activeTab = 'opportunities';

        $this->flash($message);
    }

    public function addManualRate(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualRateForm, [
            'carrier_id' => ['nullable', 'exists:carriers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'service_mode' => ['required', Rule::in(RateCard::MODES)],
            'origin' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'via_port' => ['nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:100'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'transit_days' => ['nullable', 'integer', 'min:0'],
            'buy_amount' => ['nullable', 'numeric', 'min:0'],
            'sell_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $payload = [
            'carrier_id' => ($validated['carrier_id'] ?? null) ?: null,
            'customer_name' => ($validated['customer_name'] ?? null) ?: null,
            'service_mode' => $validated['service_mode'],
            'origin' => $validated['origin'],
            'destination' => $validated['destination'],
            'via_port' => ($validated['via_port'] ?? null) ?: null,
            'incoterm' => ($validated['incoterm'] ?? null) ?: null,
            'commodity' => ($validated['commodity'] ?? null) ?: null,
            'equipment_type' => ($validated['equipment_type'] ?? null) ?: null,
            'transit_days' => ($validated['transit_days'] ?? null) ?: null,
            'buy_amount' => $validated['buy_amount'] ?? null,
            'sell_amount' => $validated['sell_amount'] ?? null,
            'margin_amount' => $this->quoteMarginFromPayload($validated),
            'currency' => $validated['currency'],
            'valid_from' => ($validated['valid_from'] ?? null) ?: null,
            'valid_until' => ($validated['valid_until'] ?? null) ?: null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'notes' => $validated['notes'] ?? null,
            'assigned_user_id' => auth()->id(),
        ];

        if ($this->editingRateId) {
            $rateCard = RateCard::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingRateId);

            $rateCard->update($payload);
            $message = "Rate {$rateCard->rate_code} updated.";
        } else {
            $rateCard = RateCard::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'rate_code' => $this->nextRateCode($workspace),
                ...$payload,
            ]);

            $message = "Rate {$rateCard->rate_code} added.";
        }

        $this->resetManualRateForm();
        $this->activeTab = 'rates';

        $this->flash($message);
    }

    public function addManualQuote(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualQuoteForm, [
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'rate_card_id' => ['nullable', Rule::exists('rate_cards', 'id')->where('workspace_id', $workspace->id)],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'service_mode' => ['required', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:100'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'volume_cbm' => ['nullable', 'numeric', 'min:0'],
            'buy_amount' => ['nullable', 'numeric', 'min:0'],
            'sell_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::in(Quote::STATUSES)],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $payload = [
            ...$validated,
            'opportunity_id' => ($validated['opportunity_id'] ?? null) ?: null,
            'rate_card_id' => ($validated['rate_card_id'] ?? null) ?: null,
            'lead_id' => ($validated['lead_id'] ?? null) ?: null,
            'assigned_user_id' => auth()->id(),
            'quoted_at' => now(),
            'margin_amount' => $this->quoteMarginFromPayload($validated),
        ];

        if ($this->editingQuoteId) {
            $quote = Quote::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingQuoteId);

            $quote->update($payload);
            $message = 'Quote updated.';
        } else {
            Quote::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'quote_number' => $this->nextQuoteNumber($workspace),
                ...$payload,
            ]);

            $message = 'Quote added.';
        }

        $this->editingQuoteId = null;
        $this->resetManualQuoteForm();
        $this->activeTab = 'quotes';

        $this->flash($message);
    }

    public function addManualShipment(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualShipmentForm, [
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'quote_id' => ['nullable', 'exists:quotes,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'service_mode' => ['required', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:100'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'container_count' => ['nullable', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'volume_cbm' => ['nullable', 'numeric', 'min:0'],
            'carrier_name' => ['nullable', 'string', 'max:255'],
            'vessel_name' => ['nullable', 'string', 'max:255'],
            'voyage_number' => ['nullable', 'string', 'max:255'],
            'house_bill_no' => ['nullable', 'string', 'max:255'],
            'master_bill_no' => ['nullable', 'string', 'max:255'],
            'estimated_departure_at' => ['nullable', 'date'],
            'estimated_arrival_at' => ['nullable', 'date'],
            'actual_departure_at' => ['nullable', 'date'],
            'actual_arrival_at' => ['nullable', 'date'],
            'buy_amount' => ['nullable', 'numeric', 'min:0'],
            'sell_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::in(ShipmentJob::STATUSES)],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $payload = [
            ...$validated,
            'opportunity_id' => $validated['opportunity_id'] ?: null,
            'quote_id' => $validated['quote_id'] ?: null,
            'lead_id' => $validated['lead_id'] ?: null,
            'assigned_user_id' => auth()->id(),
            'margin_amount' => $this->shipmentMarginFromPayload($validated),
        ];

        if ($this->editingShipmentId) {
            $shipment = ShipmentJob::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingShipmentId);

            $shipment->update($payload);
            $this->ensureShipmentExecutionDefaults($shipment->fresh());
            $this->draftCostingFromShipment($shipment->fresh());
            $message = 'Shipment job updated.';
        } else {
            $shipment = ShipmentJob::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'job_number' => $this->nextShipmentJobNumber($workspace),
                ...$payload,
            ]);
            $this->ensureShipmentExecutionDefaults($shipment);
            $this->draftCostingFromShipment($shipment);

            $message = 'Shipment job added.';
        }

        $this->editingShipmentId = null;
        $this->resetManualShipmentForm();
        $this->activeTab = 'shipments';

        $this->flash($message);
    }

    public function addManualProject(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualProjectForm, [
            'customer_record_id' => ['nullable', 'exists:accounts,id'],
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'project_name' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'container_type' => ['nullable', 'string', 'max:255'],
            'unit_quantity' => ['nullable', 'integer', 'min:0'],
            'scope_summary' => ['nullable', 'string'],
            'site_location' => ['nullable', 'string', 'max:255'],
            'target_delivery_date' => ['nullable', 'date'],
            'target_installation_date' => ['nullable', 'date'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $payload = [
            'account_id' => $validated['customer_record_id'] ?: null,
            'opportunity_id' => $validated['opportunity_id'] ?: null,
            'lead_id' => $validated['lead_id'] ?: null,
            'assigned_user_id' => auth()->id(),
            'project_name' => $validated['project_name'],
            'customer_name' => $validated['customer_name'],
            'contact_name' => $validated['contact_name'] ?: null,
            'contact_email' => $validated['contact_email'] ?: null,
            'service_type' => $validated['service_type'] ?: null,
            'container_type' => $validated['container_type'] ?: null,
            'unit_quantity' => $validated['unit_quantity'] ?: null,
            'scope_summary' => $validated['scope_summary'] ?: null,
            'site_location' => $validated['site_location'] ?: null,
            'target_delivery_date' => $validated['target_delivery_date'] ?: null,
            'target_installation_date' => $validated['target_installation_date'] ?: null,
            'estimated_value' => $validated['estimated_value'] ?: null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?: null,
        ];

        $account = ! empty($payload['account_id'])
            ? Account::query()->where('workspace_id', $workspace->id)->find($payload['account_id'])
            : null;
        $contact = $account?->contacts()->orderByDesc('last_activity_at')->orderBy('full_name')->first();
        $payload['contact_id'] = $contact?->id;

        if ($this->editingProjectId) {
            $project = Project::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingProjectId);

            $project->update($payload);
            $this->ensureProjectExecutionDefaults($project->fresh());
            $message = 'Project updated.';
        } else {
            $project = Project::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'project_number' => $this->nextProjectNumber($workspace),
                ...$payload,
            ]);
            $this->ensureProjectExecutionDefaults($project);
            $message = 'Project added.';
        }

        $this->editingProjectId = null;
        $this->resetManualProjectForm();
        $this->activeTab = 'projects';

        $this->flash($message);
    }

    public function addManualDrawing(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualDrawingForm, [
            'project_id' => ['required', 'exists:projects,id'],
            'revision_number' => ['required', 'string', 'max:100'],
            'drawing_title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(ProjectDrawing::STATUSES)],
            'external_url' => ['nullable', 'url', 'max:255'],
            'submitted_at' => ['nullable', 'date'],
            'approved_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $project = Project::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $validated['project_id']);

        if ($this->editingDrawingId) {
            $drawing = ProjectDrawing::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingDrawingId);

            $drawing->update([
                'project_id' => $project->id,
                'revision_number' => $validated['revision_number'],
                'drawing_title' => $validated['drawing_title'],
                'status' => $validated['status'],
                'external_url' => ($validated['external_url'] ?? null) ?: null,
                'submitted_at' => ($validated['submitted_at'] ?? null) ?: null,
                'approved_at' => ($validated['approved_at'] ?? null) ?: null,
                'notes' => ($validated['notes'] ?? null) ?: null,
                'assigned_user_id' => auth()->id(),
            ]);
            $message = 'Drawing updated.';
        } else {
            ProjectDrawing::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'project_id' => $project->id,
                'assigned_user_id' => auth()->id(),
                'revision_number' => $validated['revision_number'],
                'drawing_title' => $validated['drawing_title'],
                'status' => $validated['status'],
                'external_url' => ($validated['external_url'] ?? null) ?: null,
                'submitted_at' => ($validated['submitted_at'] ?? null) ?: null,
                'approved_at' => ($validated['approved_at'] ?? null) ?: null,
                'notes' => ($validated['notes'] ?? null) ?: null,
            ]);
            $message = 'Drawing added.';
        }

        $this->editingDrawingId = null;
        $this->resetManualDrawingForm();
        $this->activeTab = 'drawings';

        $this->flash($message);
    }

    public function addManualDelivery(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualDeliveryForm, [
            'project_id' => ['required', 'exists:projects,id'],
            'milestone_label' => ['required', 'string', 'max:255'],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'planned_date' => ['nullable', 'date'],
            'actual_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(ProjectDeliveryMilestone::STATUSES)],
            'site_location' => ['nullable', 'string', 'max:255'],
            'requires_crane' => ['boolean'],
            'installation_required' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $project = Project::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail((int) $validated['project_id']);

        if ($this->editingDeliveryId) {
            $delivery = ProjectDeliveryMilestone::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingDeliveryId);

            $delivery->update([
                'project_id' => $project->id,
                'milestone_label' => $validated['milestone_label'],
                'sequence' => $validated['sequence'] ?? 0,
                'planned_date' => ($validated['planned_date'] ?? null) ?: null,
                'actual_date' => ($validated['actual_date'] ?? null) ?: null,
                'status' => $validated['status'],
                'site_location' => ($validated['site_location'] ?? null) ?: null,
                'requires_crane' => (bool) ($validated['requires_crane'] ?? false),
                'installation_required' => (bool) ($validated['installation_required'] ?? false),
                'notes' => ($validated['notes'] ?? null) ?: null,
                'assigned_user_id' => auth()->id(),
            ]);
            $message = 'Delivery milestone updated.';
        } else {
            ProjectDeliveryMilestone::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'project_id' => $project->id,
                'assigned_user_id' => auth()->id(),
                'milestone_label' => $validated['milestone_label'],
                'sequence' => $validated['sequence'] ?? 0,
                'planned_date' => ($validated['planned_date'] ?? null) ?: null,
                'actual_date' => ($validated['actual_date'] ?? null) ?: null,
                'status' => $validated['status'],
                'site_location' => ($validated['site_location'] ?? null) ?: null,
                'requires_crane' => (bool) ($validated['requires_crane'] ?? false),
                'installation_required' => (bool) ($validated['installation_required'] ?? false),
                'notes' => ($validated['notes'] ?? null) ?: null,
            ]);
            $message = 'Delivery milestone added.';
        }

        $this->editingDeliveryId = null;
        $this->resetManualDeliveryForm();
        $this->activeTab = 'delivery_tracking';

        $this->flash($message);
    }

    public function addManualCarrier(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualCarrierForm, [
            'name' => ['required', 'string', 'max:255'],
            'mode' => ['nullable', Rule::in(Carrier::MODES)],
            'code' => ['nullable', 'string', 'max:100'],
            'scac_code' => ['nullable', 'string', 'max:20'],
            'iata_code' => ['nullable', 'string', 'max:20'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'service_lanes' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ])->validate();

        $payload = [
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if ($this->editingCarrierId) {
            $carrier = Carrier::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingCarrierId);

            $carrier->update($payload);
            $message = 'Carrier updated.';
        } else {
            Carrier::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                ...$payload,
            ]);

            $message = 'Carrier added.';
        }

        $this->editingCarrierId = null;
        $this->resetManualCarrierForm();
        $this->activeTab = 'carriers';

        $this->flash($message);
    }

    public function addManualBooking(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualBookingForm, [
            'shipment_job_id' => ['nullable', 'exists:shipment_jobs,id'],
            'carrier_id' => ['nullable', 'exists:carriers,id'],
            'quote_id' => ['nullable', 'exists:quotes,id'],
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'service_mode' => ['required', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:100'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'container_count' => ['nullable', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'volume_cbm' => ['nullable', 'numeric', 'min:0'],
            'requested_etd' => ['nullable', 'date'],
            'requested_eta' => ['nullable', 'date'],
            'confirmed_etd' => ['nullable', 'date'],
            'confirmed_eta' => ['nullable', 'date'],
            'carrier_confirmation_ref' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Booking::STATUSES)],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $payload = [
            ...$validated,
            'shipment_job_id' => $validated['shipment_job_id'] ?: null,
            'carrier_id' => $validated['carrier_id'] ?: null,
            'quote_id' => $validated['quote_id'] ?: null,
            'opportunity_id' => $validated['opportunity_id'] ?: null,
            'lead_id' => $validated['lead_id'] ?: null,
            'assigned_user_id' => auth()->id(),
        ];

        if ($this->editingBookingId) {
            $booking = Booking::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingBookingId);

            $booking->update($payload);
            $this->applyBookingShipmentConnection($booking->fresh(['carrier']));
            $message = 'Booking updated.';
        } else {
            $booking = Booking::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'booking_number' => $this->nextBookingNumber($workspace),
                ...$payload,
            ]);
            $this->applyBookingShipmentConnection($booking->fresh(['carrier']));

            $message = 'Booking added.';
        }

        $this->editingBookingId = null;
        $this->resetManualBookingForm();
        $this->activeTab = 'bookings';

        $this->flash($message);
    }

    public function addManualCosting(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualCostingForm, [
            'shipment_job_id' => ['nullable', 'exists:shipment_jobs,id'],
            'quote_id' => ['nullable', 'exists:quotes,id'],
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'service_mode' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::in(JobCosting::STATUSES)],
            'notes' => ['nullable', 'string'],
            'lines' => ['array', 'min:1'],
            'lines.*.line_type' => ['required', Rule::in(JobCostingLine::TYPES)],
            'lines.*.charge_code' => ['nullable', 'string', 'max:100'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.vendor_name' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.is_billable' => ['boolean'],
            'lines.*.notes' => ['nullable', 'string'],
        ])->validate();

        $totals = $this->costingTotalsFromLines($validated['lines']);

        $payload = [
            'shipment_job_id' => $validated['shipment_job_id'] ?: null,
            'quote_id' => $validated['quote_id'] ?: null,
            'opportunity_id' => $validated['opportunity_id'] ?: null,
            'lead_id' => $validated['lead_id'] ?: null,
            'customer_name' => $validated['customer_name'],
            'service_mode' => $validated['service_mode'] ?: null,
            'currency' => $validated['currency'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'assigned_user_id' => auth()->id(),
            ...$totals,
        ];

        if ($this->editingCostingId) {
            $costing = JobCosting::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingCostingId);

            $costing->update($payload);
            $this->syncCostingLines($costing, $validated['lines']);
            $this->applyCostingShipmentConnection($costing->fresh('lines'));
            $message = 'Job costing updated.';
        } else {
            $costing = JobCosting::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'costing_number' => $this->nextCostingNumber($workspace),
                ...$payload,
            ]);

            $this->syncCostingLines($costing, $validated['lines']);
            $this->applyCostingShipmentConnection($costing->fresh('lines'));
            $message = 'Job costing added.';
        }

        $this->editingCostingId = null;
        $this->resetManualCostingForm();
        $this->activeTab = 'costings';

        $this->flash($message);
    }

    public function addManualInvoice(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator($this->manualInvoiceForm, [
            'shipment_job_id' => ['nullable', 'exists:shipment_jobs,id'],
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'job_costing_id' => ['nullable', 'exists:job_costings,id'],
            'quote_id' => ['nullable', 'exists:quotes,id'],
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'invoice_type' => ['required', Rule::in(Invoice::TYPES)],
            'bill_to_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency' => ['required', 'string', 'max:10'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Invoice::STATUSES)],
            'notes' => ['nullable', 'string'],
            'lines' => ['array', 'min:1'],
            'lines.*.job_costing_line_id' => ['nullable', 'exists:job_costing_lines,id'],
            'lines.*.charge_code' => ['nullable', 'string', 'max:100'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ])->validate();

        $subtotal = $this->invoiceLineSubtotal($validated['lines']);
        $tax = (float) ($validated['tax_amount'] ?? 0);
        $paid = (float) ($validated['paid_amount'] ?? 0);
        $total = $subtotal + $tax;

        $payload = [
            'shipment_job_id' => $validated['shipment_job_id'] ?: null,
            'booking_id' => $validated['booking_id'] ?: null,
            'job_costing_id' => $validated['job_costing_id'] ?: null,
            'quote_id' => $validated['quote_id'] ?: null,
            'opportunity_id' => $validated['opportunity_id'] ?: null,
            'lead_id' => $validated['lead_id'] ?: null,
            'invoice_type' => $validated['invoice_type'],
            'bill_to_name' => $validated['bill_to_name'],
            'contact_email' => $validated['contact_email'] ?: null,
            'issue_date' => $validated['issue_date'] ?: null,
            'due_date' => $validated['due_date'] ?: null,
            'currency' => $validated['currency'],
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_amount' => max($total - $paid, 0),
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'assigned_user_id' => auth()->id(),
        ];

        if ($this->editingInvoiceId) {
            $invoice = Invoice::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($this->editingInvoiceId);

            $invoice->update($payload);
            $this->syncInvoiceLines($invoice, $validated['lines']);
            $this->syncCostingInvoiceState($invoice->jobCosting);
            $message = 'Invoice updated.';
        } else {
            $invoice = Invoice::create([
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'invoice_number' => $this->nextInvoiceNumber($workspace, $validated['invoice_type']),
                ...$payload,
            ]);

            $this->syncInvoiceLines($invoice, $validated['lines']);
            $this->syncCostingInvoiceState($invoice->jobCosting);

            $message = 'Invoice added.';
        }

        $this->editingInvoiceId = null;
        $this->resetManualInvoiceForm();
        $this->activeTab = 'invoices';

        $this->flash($message);
    }

    public function selectLead(int $leadId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $lead = Lead::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($leadId);

        $this->selectedLeadId = $lead->id;
        $this->resetCollaborationForm('lead');
        $this->activeTab = 'leads';
    }

    public function closeLeadDetails(): void
    {
        $this->selectedLeadId = null;
        $this->pendingDisqualificationLeadId = null;
        $this->resetCollaborationForm('lead');
    }

    public function selectContact(int $contactId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $contact = Contact::query()
            ->where('workspace_id', $workspace->id)
            ->find($contactId);

        if (! $contact) {
            $lead = Lead::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($contactId);

            $contact = $lead->contact_id
                ? Contact::query()->where('workspace_id', $workspace->id)->findOrFail($lead->contact_id)
                : abort(404);
        }

        $this->selectedContactId = $contact->id;
        $this->resetCollaborationForm('contact');
        $this->activeTab = 'contacts';
    }

    public function closeContactDetails(): void
    {
        $this->selectedContactId = null;
        $this->resetCollaborationForm('contact');
    }

    public function selectCustomer(int $customerId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $customer = Account::query()
            ->where('workspace_id', $workspace->id)
            ->find($customerId);

        if (! $customer) {
            $opportunity = Opportunity::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($customerId);

            $customer = $opportunity->account_id
                ? Account::query()->where('workspace_id', $workspace->id)->findOrFail($opportunity->account_id)
                : abort(404);
        }

        $this->selectedCustomerId = $customer->id;
        $this->resetCollaborationForm('customer');
        $this->activeTab = 'customers';
    }

    public function closeCustomerDetails(): void
    {
        $this->selectedCustomerId = null;
        $this->resetCollaborationForm('customer');
    }

    public function selectOpportunity(int $opportunityId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $opportunity = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($opportunityId);

        $this->selectedOpportunityId = $opportunity->id;
        $this->fillOpportunityEditForm($opportunity);
        $this->resetCollaborationForm('opportunity');
        $this->activeTab = 'opportunities';
    }

    public function selectQuote(int $quoteId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $quote = Quote::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($quoteId);

        $this->selectedQuoteId = $quote->id;
        $this->fillQuoteEditForm($quote);
        $this->resetCollaborationForm('quote');
        $this->activeTab = 'quotes';
    }

    public function selectRate(int $rateId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $rateCard = RateCard::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($rateId);

        $this->selectedRateId = $rateCard->id;
        $this->fillRateEditForm($rateCard);
        $this->activeTab = 'rates';
    }

    public function selectShipment(int $shipmentId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $shipment = ShipmentJob::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($shipmentId);

        $this->ensureShipmentExecutionDefaults($shipment->fresh());
        $this->selectedShipmentId = $shipment->id;
        $this->fillShipmentEditForm($shipment->fresh());
        $this->resetCollaborationForm('shipment');
        $this->activeTab = 'shipments';
    }

    public function selectProject(int $projectId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $project = Project::query()
            ->with(['drawings', 'deliveryMilestones', 'assignedUser', 'account', 'contact', 'opportunity', 'lead'])
            ->where('workspace_id', $workspace->id)
            ->findOrFail($projectId);

        $this->ensureProjectExecutionDefaults($project);
        $project = $project->fresh(['drawings', 'deliveryMilestones', 'assignedUser', 'account', 'contact', 'opportunity', 'lead']);

        $this->selectedProjectId = $project->id;
        $this->fillProjectEditForm($project);
        $this->resetCollaborationForm('project');
        $this->activeTab = 'projects';
    }

    public function selectDrawing(int $drawingId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $drawing = ProjectDrawing::query()
            ->with(['project', 'assignedUser'])
            ->where('workspace_id', $workspace->id)
            ->findOrFail($drawingId);

        $this->selectedDrawingId = $drawing->id;
        $this->fillDrawingEditForm($drawing);
        $this->activeTab = 'drawings';
    }

    public function selectDelivery(int $deliveryId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $delivery = ProjectDeliveryMilestone::query()
            ->with(['project', 'assignedUser'])
            ->where('workspace_id', $workspace->id)
            ->findOrFail($deliveryId);

        $this->selectedDeliveryId = $delivery->id;
        $this->fillDeliveryEditForm($delivery);
        $this->activeTab = 'delivery_tracking';
    }

    public function selectCarrier(int $carrierId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $carrier = Carrier::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($carrierId);

        $this->selectedCarrierId = $carrier->id;
        $this->fillCarrierEditForm($carrier);
        $this->activeTab = 'carriers';
    }

    public function selectBooking(int $bookingId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $booking = Booking::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($bookingId);

        $this->selectedBookingId = $booking->id;
        $this->fillBookingEditForm($booking);
        $this->resetCollaborationForm('booking');
        $this->activeTab = 'bookings';
    }

    public function selectCosting(int $costingId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $costing = JobCosting::query()
            ->with(['lines', 'shipmentJob', 'quote', 'assignedUser'])
            ->where('workspace_id', $workspace->id)
            ->findOrFail($costingId);

        $this->selectedCostingId = $costing->id;
        $this->fillCostingEditForm($costing);
        $this->resetCollaborationForm('costing');
        $this->activeTab = 'costings';
    }

    public function selectInvoice(int $invoiceId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $invoice = Invoice::query()
            ->with(['lines', 'shipmentJob', 'booking', 'jobCosting.lines', 'quote', 'assignedUser', 'postedByUser'])
            ->where('workspace_id', $workspace->id)
            ->findOrFail($invoiceId);

        $this->selectedInvoiceId = $invoice->id;
        $this->fillInvoiceEditForm($invoice);
        $this->resetCollaborationForm('invoice');
        $this->activeTab = 'invoices';
    }

    public function closeOpportunityDetails(): void
    {
        $this->selectedOpportunityId = null;
        $this->opportunityEditForm = [];
        $this->resetCollaborationForm('opportunity');
    }

    public function closeQuoteDetails(): void
    {
        $this->selectedQuoteId = null;
        $this->quoteEditForm = [];
        $this->resetCollaborationForm('quote');
    }

    public function closeRateDetails(): void
    {
        $this->selectedRateId = null;
        $this->rateEditForm = [];
    }

    public function closeShipmentDetails(): void
    {
        $this->selectedShipmentId = null;
        $this->shipmentEditForm = [];
        $this->resetShipmentMilestoneForm();
        $this->resetShipmentDocumentForm();
        $this->resetCollaborationForm('shipment');
    }

    public function closeProjectDetails(): void
    {
        $this->selectedProjectId = null;
        $this->projectEditForm = [];
        $this->resetCollaborationForm('project');
    }

    public function closeDrawingDetails(): void
    {
        $this->selectedDrawingId = null;
        $this->drawingEditForm = [];
    }

    public function closeDeliveryDetails(): void
    {
        $this->selectedDeliveryId = null;
        $this->deliveryEditForm = [];
    }

    public function toggleNotifications(): void
    {
        $this->showNotifications = ! $this->showNotifications;
    }

    public function markWorkspaceNotificationRead(int $notificationId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        WorkspaceNotification::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', auth()->id())
            ->whereKey($notificationId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function markAllWorkspaceNotificationsRead(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        WorkspaceNotification::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function openWorkspaceNotification(int $notificationId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

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

        $this->showNotifications = false;

        match ($notification->notable_type) {
            Lead::class => $this->selectLead($notification->notable_id),
            Opportunity::class => $this->selectOpportunity($notification->notable_id),
            Quote::class => $this->selectQuote($notification->notable_id),
            ShipmentJob::class => $this->selectShipment($notification->notable_id),
            Project::class => $this->selectProject($notification->notable_id),
            Booking::class => $this->selectBooking($notification->notable_id),
            JobCosting::class => $this->selectCosting($notification->notable_id),
            Invoice::class => $this->selectInvoice($notification->notable_id),
            Contact::class => $this->selectContact($notification->notable_id),
            Account::class => $this->selectCustomer($notification->notable_id),
            default => null,
        };
    }

    public function addCollaborationEntry(string $recordType, int $recordId): void
    {
        $workspace = $this->currentWorkspaceOrFail();
        $record = $this->collaborationRecordFor($recordType, $recordId, $workspace);
        $form = data_get($this->collaborationForms, $recordType, []);
        $entryType = data_get($form, 'type', CollaborationEntry::TYPE_NOTE);
        $body = trim((string) data_get($form, 'body', ''));
        $recipientId = data_get($form, 'recipient_user_id');

        if (! in_array($entryType, CollaborationEntry::TYPES, true)) {
            $entryType = CollaborationEntry::TYPE_NOTE;
        }

        if ($body === '') {
            $this->addError("collaborationForms.{$recordType}.body", 'Enter a note or message.');

            return;
        }

        $recipient = null;

        if ($entryType === CollaborationEntry::TYPE_MESSAGE) {
            if (blank($recipientId)) {
                $this->addError("collaborationForms.{$recordType}.recipient_user_id", 'Choose a teammate for this message.');

                return;
            }

            $recipient = User::query()
                ->whereHas('workspaces', fn ($query) => $query->where('workspaces.id', $workspace->id))
                ->findOrFail((int) $recipientId);
        }

        app(WorkspaceCollaborationService::class)->addEntry(
            $record,
            auth()->user(),
            $entryType,
            $body,
            $recipient,
        );

        $this->resetCollaborationForm($recordType);
        $this->resetErrorBag([
            "collaborationForms.{$recordType}.body",
            "collaborationForms.{$recordType}.recipient_user_id",
        ]);

        $this->flash(ucfirst($entryType).' added to '.$this->collaborationRecordLabel($recordType).'.');
    }

    public function updateRecordAssignment(string $recordType, int $recordId, $assignedUserId): void
    {
        $workspace = $this->currentWorkspaceOrFail();
        $record = $this->collaborationRecordFor($recordType, $recordId, $workspace);

        if (! in_array($recordType, ['lead', 'opportunity', 'quote', 'shipment', 'project', 'booking', 'costing', 'invoice', 'contact', 'customer'], true)) {
            return;
        }

        $assignee = null;

        if (filled($assignedUserId)) {
            $assignee = User::query()
                ->whereHas('workspaces', fn ($query) => $query->where('workspaces.id', $workspace->id))
                ->findOrFail((int) $assignedUserId);
        }

        if ((int) ($record->assigned_user_id ?? 0) === (int) ($assignee?->id ?? 0)) {
            return;
        }

        $record->forceFill([
            'assigned_user_id' => $assignee?->id,
        ])->save();

        app(WorkspaceCollaborationService::class)->notifyAssignment(
            $record->fresh(),
            auth()->user(),
            $assignee,
        );

        $label = $this->collaborationRecordLabel($recordType);

        $this->flash($assignee
            ? ucfirst($label).' assigned to '.$assignee->name.'.'
            : ucfirst($label).' assignment cleared.');
    }

    public function addShipmentMilestone(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedShipmentId, 404);

        $validated = validator($this->shipmentMilestoneForm, [
            'label' => ['required', 'string', 'max:255'],
            'planned_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(ShipmentMilestone::STATUSES)],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $shipment = ShipmentJob::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedShipmentId);

        ShipmentMilestone::create([
            'company_id' => $workspace->company_id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'event_key' => null,
            'label' => $validated['label'],
            'sequence' => (($shipment->milestones()->max('sequence') ?? 0) + 1),
            'status' => $validated['status'],
            'planned_at' => $validated['planned_at'] ?: null,
            'completed_at' => $validated['status'] === ShipmentMilestone::STATUS_COMPLETED ? now() : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->resetShipmentMilestoneForm();
        $this->flash("Milestone added to {$shipment->job_number}.");
    }

    public function updateShipmentMilestoneStatus(int $milestoneId, string $status): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_unless(in_array($status, ShipmentMilestone::STATUSES, true), 422);

        $milestone = ShipmentMilestone::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($milestoneId);

        $milestone->update([
            'status' => $status,
            'completed_at' => $status === ShipmentMilestone::STATUS_COMPLETED
                ? ($milestone->completed_at ?: now())
                : null,
        ]);

        $this->flash("Milestone {$milestone->label} updated.");
    }

    public function addShipmentDocument(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedShipmentId, 404);

        $validated = validator($this->shipmentDocumentForm, [
            'document_type' => ['required', Rule::in(ShipmentDocument::TYPES)],
            'document_name' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'external_url' => ['nullable', 'url', 'max:1000'],
            'status' => ['required', Rule::in(ShipmentDocument::STATUSES)],
            'uploaded_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $shipment = ShipmentJob::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedShipmentId);

        ShipmentDocument::create([
            'company_id' => $workspace->company_id,
            'workspace_id' => $workspace->id,
            'shipment_job_id' => $shipment->id,
            'document_type' => $validated['document_type'],
            'document_name' => $validated['document_name'],
            'reference_number' => $validated['reference_number'] ?: null,
            'external_url' => $validated['external_url'] ?: null,
            'status' => $validated['status'],
            'uploaded_at' => $validated['uploaded_at'] ?: now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->resetShipmentDocumentForm();
        $this->flash("Document added to {$shipment->job_number}.");
    }

    public function updateShipmentDocumentStatus(int $documentId, string $status): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_unless(in_array($status, ShipmentDocument::STATUSES, true), 422);

        $document = ShipmentDocument::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($documentId);

        $document->update([
            'status' => $status,
            'uploaded_at' => in_array($status, [ShipmentDocument::STATUS_RECEIVED, ShipmentDocument::STATUS_SENT, ShipmentDocument::STATUS_APPROVED], true)
                ? ($document->uploaded_at ?: now())
                : $document->uploaded_at,
        ]);

        $this->flash("Document {$document->document_name} updated.");
    }

    public function closeCarrierDetails(): void
    {
        $this->selectedCarrierId = null;
        $this->carrierEditForm = [];
    }

    public function closeBookingDetails(): void
    {
        $this->selectedBookingId = null;
        $this->bookingEditForm = [];
        $this->resetCollaborationForm('booking');
    }

    public function closeCostingDetails(): void
    {
        $this->selectedCostingId = null;
        $this->costingEditForm = [];
        $this->resetCollaborationForm('costing');
    }

    public function closeInvoiceDetails(): void
    {
        $this->selectedInvoiceId = null;
        $this->invoiceEditForm = [];
        $this->resetCollaborationForm('invoice');
    }

    public function saveOpportunityDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedOpportunityId, 404);

        $validated = validator($this->opportunityEditForm, [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'lead_source' => ['required', 'string', 'max:255'],
            'required_service' => ['required', 'string', 'max:255'],
            'revenue_potential' => ['nullable', 'numeric', 'min:0'],
            'project_timeline_days' => ['nullable', 'integer', 'min:0'],
            'sales_stage' => ['required', Rule::in(array_keys($this->opportunityStageOptions($workspace)))],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $opportunity = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedOpportunityId);

        $opportunity->update($validated);

        $this->fillOpportunityEditForm($opportunity->fresh(['lead', 'assignedUser']));

        $this->flash('Opportunity updated.');
    }

    public function saveQuoteDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedQuoteId, 404);

        $validated = validator($this->quoteEditForm, [
            'rate_card_id' => ['nullable', Rule::exists('rate_cards', 'id')->where('workspace_id', $workspace->id)],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'service_mode' => ['required', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:100'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'volume_cbm' => ['nullable', 'numeric', 'min:0'],
            'buy_amount' => ['nullable', 'numeric', 'min:0'],
            'sell_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::in(Quote::STATUSES)],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $quote = Quote::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedQuoteId);

        $quote->update([
            ...$validated,
            'rate_card_id' => ($validated['rate_card_id'] ?? null) ?: null,
            'margin_amount' => $this->quoteMarginFromPayload($validated),
        ]);

        $this->fillQuoteEditForm($quote->fresh(['lead', 'opportunity', 'assignedUser', 'rateCard']));

        $this->flash("Quote {$quote->quote_number} updated.");
    }

    public function saveRateDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedRateId, 404);

        $validated = validator($this->rateEditForm, [
            'carrier_id' => ['nullable', 'exists:carriers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'service_mode' => ['required', Rule::in(RateCard::MODES)],
            'origin' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'via_port' => ['nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:100'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'transit_days' => ['nullable', 'integer', 'min:0'],
            'buy_amount' => ['nullable', 'numeric', 'min:0'],
            'sell_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $rateCard = RateCard::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedRateId);

        $rateCard->update([
            'carrier_id' => ($validated['carrier_id'] ?? null) ?: null,
            'customer_name' => ($validated['customer_name'] ?? null) ?: null,
            'service_mode' => $validated['service_mode'],
            'origin' => $validated['origin'],
            'destination' => $validated['destination'],
            'via_port' => ($validated['via_port'] ?? null) ?: null,
            'incoterm' => ($validated['incoterm'] ?? null) ?: null,
            'commodity' => ($validated['commodity'] ?? null) ?: null,
            'equipment_type' => ($validated['equipment_type'] ?? null) ?: null,
            'transit_days' => ($validated['transit_days'] ?? null) ?: null,
            'buy_amount' => $validated['buy_amount'] ?? null,
            'sell_amount' => $validated['sell_amount'] ?? null,
            'margin_amount' => $this->quoteMarginFromPayload($validated),
            'currency' => $validated['currency'],
            'valid_from' => ($validated['valid_from'] ?? null) ?: null,
            'valid_until' => ($validated['valid_until'] ?? null) ?: null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->fillRateEditForm($rateCard->fresh(['carrier', 'assignedUser']));

        $this->flash("Rate {$rateCard->rate_code} updated.");
    }

    public function saveShipmentDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedShipmentId, 404);

        $validated = validator($this->shipmentEditForm, [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'service_mode' => ['required', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:100'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'container_count' => ['nullable', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'volume_cbm' => ['nullable', 'numeric', 'min:0'],
            'carrier_name' => ['nullable', 'string', 'max:255'],
            'vessel_name' => ['nullable', 'string', 'max:255'],
            'voyage_number' => ['nullable', 'string', 'max:255'],
            'house_bill_no' => ['nullable', 'string', 'max:255'],
            'master_bill_no' => ['nullable', 'string', 'max:255'],
            'estimated_departure_at' => ['nullable', 'date'],
            'estimated_arrival_at' => ['nullable', 'date'],
            'actual_departure_at' => ['nullable', 'date'],
            'actual_arrival_at' => ['nullable', 'date'],
            'buy_amount' => ['nullable', 'numeric', 'min:0'],
            'sell_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::in(ShipmentJob::STATUSES)],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $shipment = ShipmentJob::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedShipmentId);

        $shipment->update([
            ...$validated,
            'margin_amount' => $this->shipmentMarginFromPayload($validated),
        ]);

        $this->ensureShipmentExecutionDefaults($shipment->fresh());
        $this->draftCostingFromShipment($shipment->fresh());

        $this->fillShipmentEditForm($shipment->fresh(['lead', 'opportunity', 'quote', 'assignedUser']));

        $this->flash("Shipment {$shipment->job_number} updated.");
    }

    public function saveProjectDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedProjectId, 404);

        $validated = validator($this->projectEditForm, [
            'project_name' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'container_type' => ['nullable', 'string', 'max:255'],
            'unit_quantity' => ['nullable', 'integer', 'min:0'],
            'scope_summary' => ['nullable', 'string'],
            'site_location' => ['nullable', 'string', 'max:255'],
            'target_delivery_date' => ['nullable', 'date'],
            'target_installation_date' => ['nullable', 'date'],
            'actual_delivery_date' => ['nullable', 'date'],
            'actual_installation_date' => ['nullable', 'date'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $project = Project::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedProjectId);

        $project->update($validated);
        $this->ensureProjectExecutionDefaults($project->fresh());
        $this->fillProjectEditForm($project->fresh(['assignedUser', 'account', 'contact', 'opportunity', 'lead', 'drawings', 'deliveryMilestones']));

        $this->flash("Project {$project->project_number} updated.");
    }

    public function saveDrawingDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedDrawingId, 404);

        $validated = validator($this->drawingEditForm, [
            'revision_number' => ['required', 'string', 'max:100'],
            'drawing_title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(ProjectDrawing::STATUSES)],
            'external_url' => ['nullable', 'url', 'max:255'],
            'submitted_at' => ['nullable', 'date'],
            'approved_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $drawing = ProjectDrawing::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedDrawingId);

        $drawing->update($validated);
        $this->fillDrawingEditForm($drawing->fresh(['project', 'assignedUser']));

        $this->flash("Drawing {$drawing->revision_number} updated.");
    }

    public function saveDeliveryDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedDeliveryId, 404);

        $validated = validator($this->deliveryEditForm, [
            'milestone_label' => ['required', 'string', 'max:255'],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'planned_date' => ['nullable', 'date'],
            'actual_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(ProjectDeliveryMilestone::STATUSES)],
            'site_location' => ['nullable', 'string', 'max:255'],
            'requires_crane' => ['boolean'],
            'installation_required' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $delivery = ProjectDeliveryMilestone::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedDeliveryId);

        $delivery->update([
            ...$validated,
            'requires_crane' => (bool) ($validated['requires_crane'] ?? false),
            'installation_required' => (bool) ($validated['installation_required'] ?? false),
        ]);
        $this->fillDeliveryEditForm($delivery->fresh(['project', 'assignedUser']));

        $this->flash("Delivery milestone {$delivery->milestone_label} updated.");
    }

    public function saveCarrierDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedCarrierId, 404);

        $validated = validator($this->carrierEditForm, [
            'name' => ['required', 'string', 'max:255'],
            'mode' => ['nullable', Rule::in(Carrier::MODES)],
            'code' => ['nullable', 'string', 'max:100'],
            'scac_code' => ['nullable', 'string', 'max:20'],
            'iata_code' => ['nullable', 'string', 'max:20'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'service_lanes' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ])->validate();

        $carrier = Carrier::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedCarrierId);

        $carrier->update([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $this->fillCarrierEditForm($carrier->fresh());

        $this->flash("Carrier {$carrier->name} updated.");
    }

    public function saveBookingDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedBookingId, 404);

        $validated = validator($this->bookingEditForm, [
            'carrier_id' => ['nullable', 'exists:carriers,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'service_mode' => ['required', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:100'],
            'commodity' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'container_count' => ['nullable', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'volume_cbm' => ['nullable', 'numeric', 'min:0'],
            'requested_etd' => ['nullable', 'date'],
            'requested_eta' => ['nullable', 'date'],
            'confirmed_etd' => ['nullable', 'date'],
            'confirmed_eta' => ['nullable', 'date'],
            'carrier_confirmation_ref' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Booking::STATUSES)],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $booking = Booking::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedBookingId);

        $booking->update([
            ...$validated,
            'carrier_id' => $validated['carrier_id'] ?: null,
        ]);

        $this->applyBookingShipmentConnection($booking->fresh(['carrier']));

        $this->fillBookingEditForm($booking->fresh(['carrier', 'shipmentJob', 'quote', 'assignedUser']));

        $this->flash("Booking {$booking->booking_number} updated.");
    }

    public function saveCostingDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedCostingId, 404);

        $validated = validator($this->costingEditForm, [
            'customer_name' => ['required', 'string', 'max:255'],
            'service_mode' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::in(JobCosting::STATUSES)],
            'notes' => ['nullable', 'string'],
            'lines' => ['array', 'min:1'],
            'lines.*.line_type' => ['required', Rule::in(JobCostingLine::TYPES)],
            'lines.*.charge_code' => ['nullable', 'string', 'max:100'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.vendor_name' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.is_billable' => ['boolean'],
            'lines.*.notes' => ['nullable', 'string'],
        ])->validate();

        $costing = JobCosting::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedCostingId);

        $totals = $this->costingTotalsFromLines($validated['lines']);

        $costing->update([
            'customer_name' => $validated['customer_name'],
            'service_mode' => $validated['service_mode'] ?: null,
            'currency' => $validated['currency'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            ...$totals,
        ]);

        $this->syncCostingLines($costing, $validated['lines']);
        $this->applyCostingShipmentConnection($costing->fresh('lines'));

        $this->fillCostingEditForm($costing->fresh(['lines', 'shipmentJob', 'quote', 'assignedUser']));

        $this->flash("Job costing {$costing->costing_number} updated.");
    }

    public function saveInvoiceDetails(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_if(! $this->selectedInvoiceId, 404);

        $validated = validator($this->invoiceEditForm, [
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'invoice_type' => ['required', Rule::in(Invoice::TYPES)],
            'bill_to_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency' => ['required', 'string', 'max:10'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Invoice::STATUSES)],
            'notes' => ['nullable', 'string'],
            'lines' => ['array', 'min:1'],
            'lines.*.job_costing_line_id' => ['nullable', 'exists:job_costing_lines,id'],
            'lines.*.charge_code' => ['nullable', 'string', 'max:100'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ])->validate();

        $invoice = Invoice::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($this->selectedInvoiceId);

        if ($invoice->posted_at) {
            $this->flash("Invoice {$invoice->invoice_number} is already posted and can no longer be edited.");

            return;
        }

        $subtotal = $this->invoiceLineSubtotal($validated['lines']);
        $tax = (float) ($validated['tax_amount'] ?? 0);
        $paid = (float) ($validated['paid_amount'] ?? 0);
        $total = $subtotal + $tax;

        $invoice->update([
            'booking_id' => $validated['booking_id'] ?: null,
            'invoice_type' => $validated['invoice_type'],
            'bill_to_name' => $validated['bill_to_name'],
            'contact_email' => $validated['contact_email'] ?: null,
            'issue_date' => $validated['issue_date'] ?: null,
            'due_date' => $validated['due_date'] ?: null,
            'currency' => $validated['currency'],
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_amount' => max($total - $paid, 0),
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncInvoiceLines($invoice, $validated['lines'] ?? []);
        $this->syncCostingInvoiceState($invoice->jobCosting);

        $this->fillInvoiceEditForm($invoice->fresh(['lines', 'shipmentJob', 'booking', 'jobCosting.lines', 'quote', 'assignedUser', 'postedByUser']));

        $this->flash("Invoice {$invoice->invoice_number} updated.");
    }

    public function postInvoice(int $invoiceId): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $invoice = Invoice::query()
            ->with(['lines', 'jobCosting', 'booking'])
            ->where('workspace_id', $workspace->id)
            ->findOrFail($invoiceId);

        if ($invoice->posted_at) {
            $this->flash("Invoice {$invoice->invoice_number} is already posted.");

            return;
        }

        $subtotal = $this->invoiceLineSubtotal($invoice->lines->toArray());
        $tax = (float) $invoice->tax_amount;
        $paid = (float) $invoice->paid_amount;
        $total = $subtotal + $tax;

        $invoice->forceFill([
            'subtotal_amount' => $subtotal,
            'total_amount' => $total,
            'balance_amount' => max($total - $paid, 0),
            'status' => $invoice->status === Invoice::STATUS_DRAFT ? Invoice::STATUS_SENT : $invoice->status,
            'posted_at' => now(),
            'posted_by_user_id' => auth()->id(),
        ])->save();

        $this->syncCostingInvoiceState($invoice->jobCosting);

        if ($this->selectedInvoiceId === $invoice->id) {
            $this->fillInvoiceEditForm($invoice->fresh(['lines', 'shipmentJob', 'booking', 'jobCosting.lines', 'quote', 'assignedUser', 'postedByUser']));
        }

        $this->flash("Invoice {$invoice->invoice_number} posted.");
    }

    public function updateLeadStatus(int $leadId, string $status): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_unless(in_array($status, Lead::STATUSES, true), 422);

        $lead = Lead::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($leadId);

        if ($lead->status === $status) {
            if ($status === Lead::STATUS_DISQUALIFIED && blank($lead->disqualification_reason)) {
                $this->pendingDisqualificationLeadId = $lead->id;
            }

            return;
        }

        if ($status === Lead::STATUS_DISQUALIFIED) {
            $this->pendingDisqualificationLeadId = $lead->id;

            return;
        }

        if ($this->pendingDisqualificationLeadId === $lead->id) {
            $this->pendingDisqualificationLeadId = null;
        }

        $this->persistLeadStatus($lead, $status);
    }

    public function saveDisqualificationReason(int $leadId, string $reason): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        $validated = validator([
            'lead_id' => $leadId,
            'reason' => $reason,
        ], [
            'lead_id' => ['required', 'integer', 'exists:leads,id'],
            'reason' => ['required', Rule::in($this->disqualificationReasonOptions($workspace))],
        ])->validate();

        $lead = Lead::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($validated['lead_id']);

        if ($lead->status === Lead::STATUS_DISQUALIFIED) {
            $lead->update([
                'disqualification_reason' => $validated['reason'],
            ]);

            $this->pendingDisqualificationLeadId = null;
            $this->flash('Disqualification reason updated.');

            return;
        }

        $this->persistLeadStatus($lead, Lead::STATUS_DISQUALIFIED, $validated['reason']);
        $this->pendingDisqualificationLeadId = null;
    }

    protected function persistLeadStatus(Lead $lead, string $status, ?string $disqualificationReason = null): void
    {
        $fromStatus = $lead->status;

        $lead->update([
            'status' => $status,
            'disqualification_reason' => $status === Lead::STATUS_DISQUALIFIED
                ? $disqualificationReason
                : null,
        ]);
        $lead->loadMissing('sheetSource');

        LeadStatusLog::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'from_status' => $fromStatus,
            'to_status' => $status,
            'change_context' => 'dashboard',
            'note' => $status === Lead::STATUS_DISQUALIFIED ? $disqualificationReason : null,
        ]);

        $leadLabel = $lead->lead_id ?: $lead->external_key;

        $message = "Lead {$leadLabel} updated to {$status}.";

        if ($status === Lead::STATUS_SALES_QUALIFIED) {
            $opportunity = $this->draftOpportunityFromQualifiedLead($lead);

            $this->editingOpportunityId = $opportunity->id;
            $this->fillManualOpportunityFormFromOpportunity($opportunity);
            $this->activeTab = 'manual-opportunity';

            $message .= ' Opportunity draft is ready to complete.';
        }

        try {
            if (app(GoogleSheetsService::class)->writeLeadStatus($lead, $status)) {
                $message .= ' Synced to Google Sheets.';
            }
        } catch (Throwable $exception) {
            report($exception);
            $message .= ' Google Sheets write-back failed.';
        }

        $this->flash($message);
    }

    public function updateOpportunityStage(int $opportunityId, string $stage): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_unless(in_array($stage, Opportunity::STAGES, true), 422);

        $opportunity = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($opportunityId);

        $opportunity->update(['sales_stage' => $stage]);
        $opportunity->loadMissing('sheetSource');

        $message = "Opportunity {$opportunity->external_key} moved to {$stage}.";

        if ($stage === Opportunity::STAGE_CLOSED_WON) {
            if ($workspace->templateKey() === 'container_conversion') {
                $project = $this->draftProjectFromAwardedOpportunity($opportunity->fresh(['lead', 'account.contacts']));

                $this->editingProjectId = $project->id;
                $this->fillManualProjectFormFromProject($project);
                $this->activeTab = 'manual-project';

                $message .= ' Project draft is ready to complete.';
            } else {
                $shipment = $this->draftShipmentFromWonOpportunity($opportunity->fresh(['lead', 'quotes']));

                $this->editingShipmentId = $shipment->id;
                $this->fillManualShipmentFormFromShipment($shipment);
                $this->activeTab = 'manual-shipment';

                $message .= ' Shipment job draft is ready to complete.';
            }
        }

        try {
            if (app(GoogleSheetsService::class)->writeOpportunityStage($opportunity, $stage)) {
                $message .= ' Synced to Google Sheets.';
            }
        } catch (Throwable $exception) {
            report($exception);
            $message .= ' Google Sheets write-back failed.';
        }

        $this->flash($message);
    }

    public function syncSource(int $sourceId): void
    {
        $this->ensureWorkspaceManager();

        $workspaceIds = $this->accessibleWorkspaces()->pluck('id')->all();

        $source = SheetSource::query()
            ->whereIn('workspace_id', $workspaceIds)
            ->findOrFail($sourceId);

        if (! SheetSource::supportsSync($source->type)) {
            $this->flash(SheetSource::typeLabel($source->type).' sources are saved as connections for now. Sync will be added when that module data model is ready.');

            return;
        }

        try {
            $rows = app(SheetSourceSyncService::class)->sync($source);

            $this->flash("Source {$source->name} synced with {$rows} imported rows.");
        } catch (Throwable $exception) {
            $this->flash($this->friendlySyncError($exception));
        }
    }

    public function startEditingSource(int $sourceId): void
    {
        $this->ensureWorkspaceManager();

        $workspaceIds = $this->accessibleWorkspaces()->pluck('id')->all();

        $source = SheetSource::query()
            ->whereIn('workspace_id', $workspaceIds)
            ->findOrFail($sourceId);

        $this->editingSourceId = $source->id;
        $this->editingSourceForm = [
            'type' => $source->type,
            'name' => $source->name,
            'url' => $source->url,
            'source_kind' => $source->source_kind,
            'description' => $source->description ?? '',
            'is_active' => $source->is_active,
            ...$this->sourceConnectionFieldsFromSource($source),
        ];
        $this->activeTab = 'sources';
    }

    public function cancelEditingSource(): void
    {
        $this->editingSourceId = null;
        $this->editingSourceForm = [];
    }

    public function updateSheetSource(): void
    {
        $this->ensureWorkspaceManager();

        abort_if(! $this->editingSourceId, 404);

        $workspaceIds = $this->accessibleWorkspaces()->pluck('id')->all();

        $source = SheetSource::query()
            ->whereIn('workspace_id', $workspaceIds)
            ->with('workspace')
            ->findOrFail($this->editingSourceId);

        $validated = validator($this->editingSourceForm, [
            'type' => ['required', Rule::in(array_keys(SheetSource::availableTypesForWorkspace($source->workspace)))],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string'],
            'source_kind' => ['required', Rule::in(SheetSource::SOURCE_KINDS)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'cargo_auth_mode' => ['nullable', Rule::in(array_keys(SheetSource::cargoWiseAuthModes()))],
            'cargo_username' => ['nullable', 'string', 'max:255'],
            'cargo_password' => ['nullable', 'string', 'max:2048'],
            'cargo_token' => ['nullable', 'string', 'max:4096'],
            'cargo_format' => ['nullable', Rule::in(array_keys(SheetSource::cargoWiseFormats()))],
            'cargo_data_path' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $sourceKind = SheetSource::normalizeSourceKind(
            $validated['source_kind'],
            $validated['url'],
        );

        $source->fill([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'url' => $validated['url'],
            'source_kind' => $sourceKind,
            'description' => $validated['description'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'mapping' => $this->sourceMappingFromForm($validated, $source),
        ])->save();

        $this->cancelEditingSource();

        $this->flash("Source {$source->name} updated.");
    }

    public function syncWorkspaceSources(): void
    {
        $this->ensureWorkspaceManager();

        $workspace = $this->currentWorkspaceOrFail();

        $totalRows = 0;
        $skippedSources = 0;

        try {
            foreach ($workspace->sheetSources()->where('is_active', true)->get() as $source) {
                if (! SheetSource::supportsSync($source->type)) {
                    $skippedSources++;

                    continue;
                }

                $totalRows += app(SheetSourceSyncService::class)->sync($source);
            }

            $message = "Workspace synced. Imported {$totalRows} rows.";

            if ($skippedSources > 0) {
                $message .= " {$skippedSources} connection-only sources were skipped.";
            }

            $this->flash($message);
        } catch (Throwable $exception) {
            $this->flash($this->friendlySyncError($exception));
        }
    }

    protected function draftOpportunityFromQualifiedLead(Lead $lead): Opportunity
    {
        $opportunity = Opportunity::query()
            ->where('workspace_id', $lead->workspace_id)
            ->where('lead_id', $lead->id)
            ->first();

        if (! $opportunity) {
            $opportunity = Opportunity::create([
                'company_id' => $lead->company_id,
                'workspace_id' => $lead->workspace_id,
                'lead_id' => $lead->id,
                'assigned_user_id' => $lead->assigned_user_id ?: auth()->id(),
                'external_key' => 'qualified-'.Str::ulid(),
                'company_name' => $lead->company_name,
                'contact_email' => $lead->email,
                'lead_source' => $lead->lead_source ?: 'Qualified lead',
                'required_service' => $lead->service ?: 'Container Conversion',
                'revenue_potential' => $lead->lead_value,
                'sales_stage' => Opportunity::STAGE_INITIAL_CONTACT,
                'notes' => $lead->notes,
                'submission_date' => now(),
                'year_month' => now()->format('M-y'),
                'manual_entry' => true,
            ]);
        } else {
            $opportunity->fill([
                'company_name' => $opportunity->company_name ?: $lead->company_name,
                'contact_email' => $opportunity->contact_email ?: $lead->email,
                'lead_source' => $opportunity->lead_source ?: ($lead->lead_source ?: 'Qualified lead'),
                'required_service' => $opportunity->required_service ?: ($lead->service ?: 'Container Conversion'),
                'revenue_potential' => $opportunity->revenue_potential ?: $lead->lead_value,
                'notes' => $opportunity->notes ?: $lead->notes,
                'assigned_user_id' => $opportunity->assigned_user_id ?: ($lead->assigned_user_id ?: auth()->id()),
            ])->save();
        }

        return $opportunity;
    }

    protected function fillManualOpportunityFormFromOpportunity(Opportunity $opportunity): void
    {
        $this->manualOpportunityForm = [
            'lead_id' => $opportunity->lead_id ?: '',
            'company_name' => $opportunity->company_name ?: '',
            'contact_email' => $opportunity->contact_email ?: '',
            'lead_source' => $opportunity->lead_source ?: 'Email',
            'required_service' => $opportunity->required_service ?: 'Container Conversion',
            'revenue_potential' => $opportunity->revenue_potential ? (string) $opportunity->revenue_potential : '',
            'project_timeline_days' => $opportunity->project_timeline_days ? (string) $opportunity->project_timeline_days : '',
            'sales_stage' => $opportunity->sales_stage ?: Opportunity::STAGE_INITIAL_CONTACT,
            'notes' => $opportunity->notes ?: '',
        ];
    }

    protected function fillOpportunityEditForm(Opportunity $opportunity): void
    {
        $this->opportunityEditForm = [
            'company_name' => $opportunity->company_name ?: '',
            'contact_email' => $opportunity->contact_email ?: '',
            'lead_source' => $opportunity->lead_source ?: 'Email',
            'required_service' => $opportunity->required_service ?: 'Container Conversion',
            'revenue_potential' => $opportunity->revenue_potential ? (string) $opportunity->revenue_potential : '',
            'project_timeline_days' => $opportunity->project_timeline_days ? (string) $opportunity->project_timeline_days : '',
            'sales_stage' => $opportunity->sales_stage ?: Opportunity::STAGE_INITIAL_CONTACT,
            'notes' => $opportunity->notes ?: '',
        ];
    }

    protected function fillQuoteEditForm(Quote $quote): void
    {
        $this->quoteEditForm = [
            'rate_card_id' => $quote->rate_card_id ? (string) $quote->rate_card_id : '',
            'company_name' => $quote->company_name ?: '',
            'contact_name' => $quote->contact_name ?: '',
            'contact_email' => $quote->contact_email ?: '',
            'service_mode' => $quote->service_mode ?: 'Ocean Freight',
            'origin' => $quote->origin ?: '',
            'destination' => $quote->destination ?: '',
            'incoterm' => $quote->incoterm ?: '',
            'commodity' => $quote->commodity ?: '',
            'equipment_type' => $quote->equipment_type ?: '',
            'weight_kg' => $quote->weight_kg !== null ? (string) $quote->weight_kg : '',
            'volume_cbm' => $quote->volume_cbm !== null ? (string) $quote->volume_cbm : '',
            'buy_amount' => $quote->buy_amount !== null ? (string) $quote->buy_amount : '',
            'sell_amount' => $quote->sell_amount !== null ? (string) $quote->sell_amount : '',
            'currency' => $quote->currency ?: 'AED',
            'status' => $quote->status ?: Quote::STATUS_DRAFT,
            'valid_until' => $quote->valid_until?->format('Y-m-d') ?: '',
            'notes' => $quote->notes ?: '',
        ];
    }

    protected function hydrateManualQuoteFromAccount(Account $customer): void
    {
        $primaryContact = $customer->contacts->sortByDesc('last_activity_at')->first();

        $this->manualQuoteForm = [
            ...$this->manualQuoteForm,
            'customer_record_id' => (string) $customer->id,
            'opportunity_id' => '',
            'rate_card_id' => '',
            'lead_id' => '',
            'company_name' => $customer->name ?: '',
            'contact_name' => $primaryContact?->full_name ?: '',
            'contact_email' => $primaryContact?->email ?: ($customer->primary_email ?: ''),
            'service_mode' => $customer->latest_service ?: 'Ocean Freight',
            'notes' => $customer->notes ?: '',
        ];
    }

    protected function hydrateManualQuoteFromOpportunity(Opportunity $opportunity): void
    {
        $lead = $opportunity->lead;

        $this->manualQuoteForm = [
            ...$this->manualQuoteForm,
            'customer_record_id' => $opportunity->account_id ? (string) $opportunity->account_id : ($this->manualQuoteForm['customer_record_id'] ?? ''),
            'opportunity_id' => (string) $opportunity->id,
            'rate_card_id' => $this->manualQuoteForm['rate_card_id'] ?? '',
            'lead_id' => $opportunity->lead_id ? (string) $opportunity->lead_id : '',
            'company_name' => $opportunity->company_name ?: ($lead?->company_name ?: ''),
            'contact_name' => $lead?->contact_name ?: ($this->manualQuoteForm['contact_name'] ?? ''),
            'contact_email' => $opportunity->contact_email ?: ($lead?->email ?: ''),
            'service_mode' => $opportunity->required_service ?: ($lead?->service ?: 'Ocean Freight'),
            'sell_amount' => $opportunity->revenue_potential !== null ? (string) $opportunity->revenue_potential : ($this->manualQuoteForm['sell_amount'] ?? ''),
            'notes' => $opportunity->notes ?: ($this->manualQuoteForm['notes'] ?? ''),
        ];
    }

    protected function hydrateManualQuoteFromRateCard(RateCard $rateCard): void
    {
        $this->manualQuoteForm = [
            ...$this->manualQuoteForm,
            'rate_card_id' => (string) $rateCard->id,
            'service_mode' => $rateCard->service_mode ?: ($this->manualQuoteForm['service_mode'] ?? 'Ocean Freight'),
            'origin' => $rateCard->origin ?: '',
            'destination' => $rateCard->destination ?: '',
            'incoterm' => $rateCard->incoterm ?: ($this->manualQuoteForm['incoterm'] ?? ''),
            'commodity' => $rateCard->commodity ?: ($this->manualQuoteForm['commodity'] ?? ''),
            'equipment_type' => $rateCard->equipment_type ?: ($this->manualQuoteForm['equipment_type'] ?? ''),
            'buy_amount' => $rateCard->buy_amount !== null ? (string) $rateCard->buy_amount : ($this->manualQuoteForm['buy_amount'] ?? ''),
            'sell_amount' => $rateCard->sell_amount !== null ? (string) $rateCard->sell_amount : ($this->manualQuoteForm['sell_amount'] ?? ''),
            'currency' => $rateCard->currency ?: ($this->manualQuoteForm['currency'] ?? 'AED'),
            'valid_until' => $rateCard->valid_until?->format('Y-m-d') ?: ($this->manualQuoteForm['valid_until'] ?? ''),
            'notes' => trim(collect([
                $this->manualQuoteForm['notes'] ?? '',
                $rateCard->carrier?->name ? 'Rate card carrier: '.$rateCard->carrier->name : '',
                $rateCard->via_port ? 'Via: '.$rateCard->via_port : '',
                $rateCard->transit_days ? 'Transit: '.$rateCard->transit_days.' days' : '',
            ])->filter()->join("\n")),
        ];
    }

    protected function fillRateEditForm(RateCard $rateCard): void
    {
        $this->rateEditForm = [
            'carrier_id' => $rateCard->carrier_id ? (string) $rateCard->carrier_id : '',
            'customer_name' => $rateCard->customer_name ?: '',
            'service_mode' => $rateCard->service_mode ?: RateCard::MODE_OCEAN,
            'origin' => $rateCard->origin ?: '',
            'destination' => $rateCard->destination ?: '',
            'via_port' => $rateCard->via_port ?: '',
            'incoterm' => $rateCard->incoterm ?: '',
            'commodity' => $rateCard->commodity ?: '',
            'equipment_type' => $rateCard->equipment_type ?: '',
            'transit_days' => $rateCard->transit_days !== null ? (string) $rateCard->transit_days : '',
            'buy_amount' => $rateCard->buy_amount !== null ? (string) $rateCard->buy_amount : '',
            'sell_amount' => $rateCard->sell_amount !== null ? (string) $rateCard->sell_amount : '',
            'currency' => $rateCard->currency ?: 'AED',
            'valid_from' => $rateCard->valid_from?->format('Y-m-d') ?: '',
            'valid_until' => $rateCard->valid_until?->format('Y-m-d') ?: '',
            'is_active' => (bool) $rateCard->is_active,
            'notes' => $rateCard->notes ?: '',
        ];
    }

    protected function fillCarrierEditForm(Carrier $carrier): void
    {
        $this->carrierEditForm = [
            'name' => $carrier->name ?: '',
            'mode' => $carrier->mode ?: '',
            'code' => $carrier->code ?: '',
            'scac_code' => $carrier->scac_code ?: '',
            'iata_code' => $carrier->iata_code ?: '',
            'contact_name' => $carrier->contact_name ?: '',
            'contact_email' => $carrier->contact_email ?: '',
            'contact_phone' => $carrier->contact_phone ?: '',
            'website' => $carrier->website ?: '',
            'service_lanes' => $carrier->service_lanes ?: '',
            'notes' => $carrier->notes ?: '',
            'is_active' => (bool) $carrier->is_active,
        ];
    }

    protected function fillBookingEditForm(Booking $booking): void
    {
        $this->bookingEditForm = [
            'carrier_id' => $booking->carrier_id ?: '',
            'customer_name' => $booking->customer_name ?: '',
            'contact_name' => $booking->contact_name ?: '',
            'contact_email' => $booking->contact_email ?: '',
            'service_mode' => $booking->service_mode ?: 'Ocean Freight',
            'origin' => $booking->origin ?: '',
            'destination' => $booking->destination ?: '',
            'incoterm' => $booking->incoterm ?: '',
            'commodity' => $booking->commodity ?: '',
            'equipment_type' => $booking->equipment_type ?: '',
            'container_count' => $booking->container_count !== null ? (string) $booking->container_count : '',
            'weight_kg' => $booking->weight_kg !== null ? (string) $booking->weight_kg : '',
            'volume_cbm' => $booking->volume_cbm !== null ? (string) $booking->volume_cbm : '',
            'requested_etd' => $booking->requested_etd?->format('Y-m-d\TH:i') ?: '',
            'requested_eta' => $booking->requested_eta?->format('Y-m-d\TH:i') ?: '',
            'confirmed_etd' => $booking->confirmed_etd?->format('Y-m-d\TH:i') ?: '',
            'confirmed_eta' => $booking->confirmed_eta?->format('Y-m-d\TH:i') ?: '',
            'carrier_confirmation_ref' => $booking->carrier_confirmation_ref ?: '',
            'status' => $booking->status ?: Booking::STATUS_DRAFT,
            'notes' => $booking->notes ?: '',
        ];
    }

    protected function fillCostingEditForm(JobCosting $costing): void
    {
        $this->costingEditForm = [
            'customer_name' => $costing->customer_name ?: '',
            'service_mode' => $costing->service_mode ?: '',
            'currency' => $costing->currency ?: 'AED',
            'status' => $costing->status ?: JobCosting::STATUS_DRAFT,
            'notes' => $costing->notes ?: '',
            'lines' => $costing->lines->map(fn (JobCostingLine $line) => [
                'line_type' => $line->line_type ?: JobCostingLine::TYPE_COST,
                'charge_code' => $line->charge_code ?: '',
                'description' => $line->description ?: '',
                'vendor_name' => $line->vendor_name ?: '',
                'quantity' => $line->quantity !== null ? (string) $line->quantity : '1',
                'unit_amount' => $line->unit_amount !== null ? (string) $line->unit_amount : '',
                'is_billable' => (bool) $line->is_billable,
                'notes' => $line->notes ?: '',
            ])->values()->all() ?: $this->blankCostingLines(),
        ];
    }

    protected function fillInvoiceEditForm(Invoice $invoice): void
    {
        $this->invoiceEditForm = [
            'booking_id' => $invoice->booking_id ? (string) $invoice->booking_id : '',
            'invoice_type' => $invoice->invoice_type ?: Invoice::TYPE_ACCOUNTS_RECEIVABLE,
            'bill_to_name' => $invoice->bill_to_name ?: '',
            'contact_email' => $invoice->contact_email ?: '',
            'issue_date' => $invoice->issue_date?->format('Y-m-d') ?: '',
            'due_date' => $invoice->due_date?->format('Y-m-d') ?: '',
            'currency' => $invoice->currency ?: 'AED',
            'subtotal_amount' => $invoice->subtotal_amount !== null ? (string) $invoice->subtotal_amount : '',
            'tax_amount' => $invoice->tax_amount !== null ? (string) $invoice->tax_amount : '0',
            'total_amount' => $invoice->total_amount !== null ? (string) $invoice->total_amount : '',
            'paid_amount' => $invoice->paid_amount !== null ? (string) $invoice->paid_amount : '0',
            'balance_amount' => $invoice->balance_amount !== null ? (string) $invoice->balance_amount : '',
            'status' => $invoice->status ?: Invoice::STATUS_DRAFT,
            'notes' => $invoice->notes ?: '',
            'lines' => $invoice->lines->map(fn (InvoiceLine $line) => [
                'job_costing_line_id' => $line->job_costing_line_id ? (string) $line->job_costing_line_id : '',
                'charge_code' => $line->charge_code ?: '',
                'description' => $line->description ?: '',
                'quantity' => $line->quantity !== null ? (string) $line->quantity : '1',
                'unit_amount' => $line->unit_amount !== null ? (string) $line->unit_amount : '',
                'notes' => $line->notes ?: '',
            ])->values()->all() ?: $this->invoiceLinesFromCosting($invoice->jobCosting, $invoice->invoice_type),
        ];
    }

    protected function hydrateManualBookingFromShipment(ShipmentJob $shipment): void
    {
        $this->manualBookingForm = [
            ...$this->manualBookingForm,
            'shipment_job_id' => (string) $shipment->id,
            'carrier_id' => $this->manualBookingForm['carrier_id'] ?? '',
            'quote_id' => $shipment->quote_id ? (string) $shipment->quote_id : '',
            'opportunity_id' => $shipment->opportunity_id ? (string) $shipment->opportunity_id : '',
            'lead_id' => $shipment->lead_id ? (string) $shipment->lead_id : '',
            'customer_name' => $shipment->company_name ?: '',
            'contact_name' => $shipment->contact_name ?: '',
            'contact_email' => $shipment->contact_email ?: '',
            'service_mode' => $shipment->service_mode ?: 'Ocean Freight',
            'origin' => $shipment->origin ?: '',
            'destination' => $shipment->destination ?: '',
            'incoterm' => $shipment->incoterm ?: '',
            'commodity' => $shipment->commodity ?: '',
            'equipment_type' => $shipment->equipment_type ?: '',
            'container_count' => $shipment->container_count !== null ? (string) $shipment->container_count : '',
            'weight_kg' => $shipment->weight_kg !== null ? (string) $shipment->weight_kg : '',
            'volume_cbm' => $shipment->volume_cbm !== null ? (string) $shipment->volume_cbm : '',
            'requested_etd' => $shipment->estimated_departure_at?->format('Y-m-d\TH:i') ?: '',
            'requested_eta' => $shipment->estimated_arrival_at?->format('Y-m-d\TH:i') ?: '',
            'confirmed_etd' => '',
            'confirmed_eta' => '',
            'carrier_confirmation_ref' => '',
            'status' => $this->manualBookingForm['status'] ?? Booking::STATUS_DRAFT,
            'notes' => $shipment->notes ?: '',
        ];
    }

    protected function hydrateManualCostingFromShipment(ShipmentJob $shipment): void
    {
        $this->manualCostingForm = [
            ...$this->manualCostingForm,
            'shipment_job_id' => (string) $shipment->id,
            'quote_id' => $shipment->quote_id ? (string) $shipment->quote_id : '',
            'opportunity_id' => $shipment->opportunity_id ? (string) $shipment->opportunity_id : '',
            'lead_id' => $shipment->lead_id ? (string) $shipment->lead_id : '',
            'customer_name' => $shipment->company_name ?: '',
            'service_mode' => $shipment->service_mode ?: '',
            'currency' => $shipment->currency ?: 'AED',
            'status' => $this->manualCostingForm['status'] ?? JobCosting::STATUS_DRAFT,
            'notes' => $shipment->notes ?: '',
            'lines' => $this->costingLinesFromShipment($shipment),
        ];
    }

    protected function hydrateManualInvoiceFromShipment(ShipmentJob $shipment): void
    {
        $latestCosting = $shipment->jobCostings->sortByDesc('id')->first();
        $latestBooking = $shipment->bookings->sortByDesc('id')->first();
        $invoiceType = $this->manualInvoiceForm['invoice_type'] ?? Invoice::TYPE_ACCOUNTS_RECEIVABLE;
        $lines = $latestCosting
            ? $this->invoiceLinesFromCosting($latestCosting, $invoiceType)
            : $this->blankInvoiceLines();
        $subtotal = $this->invoiceLineSubtotal($lines);

        $this->manualInvoiceForm = [
            ...$this->manualInvoiceForm,
            'shipment_job_id' => (string) $shipment->id,
            'booking_id' => $latestBooking?->id ? (string) $latestBooking->id : ($this->manualInvoiceForm['booking_id'] ?? ''),
            'job_costing_id' => $latestCosting?->id ? (string) $latestCosting->id : '',
            'quote_id' => $shipment->quote_id ? (string) $shipment->quote_id : '',
            'opportunity_id' => $shipment->opportunity_id ? (string) $shipment->opportunity_id : '',
            'lead_id' => $shipment->lead_id ? (string) $shipment->lead_id : '',
            'invoice_type' => $invoiceType,
            'bill_to_name' => $shipment->company_name ?: '',
            'contact_email' => $shipment->contact_email ?: '',
            'issue_date' => $this->manualInvoiceForm['issue_date'] ?? now()->toDateString(),
            'due_date' => $this->manualInvoiceForm['due_date'] ?? now()->addDays(14)->toDateString(),
            'currency' => $shipment->currency ?: 'AED',
            'subtotal_amount' => $subtotal > 0 ? (string) $subtotal : '',
            'tax_amount' => $this->manualInvoiceForm['tax_amount'] ?? '0',
            'paid_amount' => $this->manualInvoiceForm['paid_amount'] ?? '0',
            'total_amount' => $subtotal > 0 ? (string) $subtotal : '',
            'balance_amount' => $subtotal > 0 ? (string) $subtotal : '',
            'status' => $this->manualInvoiceForm['status'] ?? Invoice::STATUS_DRAFT,
            'notes' => $shipment->notes ?: '',
            'lines' => $lines,
        ];
    }

    protected function hydrateManualInvoiceFromBooking(Booking $booking): void
    {
        if ($booking->shipmentJob) {
            $this->hydrateManualInvoiceFromShipment($booking->shipmentJob->loadMissing(['jobCostings' => fn ($query) => $query->latest('id'), 'bookings']));
        }

        $this->manualInvoiceForm = [
            ...$this->manualInvoiceForm,
            'booking_id' => (string) $booking->id,
            'shipment_job_id' => $booking->shipment_job_id ? (string) $booking->shipment_job_id : ($this->manualInvoiceForm['shipment_job_id'] ?? ''),
            'bill_to_name' => $booking->customer_name ?: ($this->manualInvoiceForm['bill_to_name'] ?? ''),
            'contact_email' => $booking->contact_email ?: ($this->manualInvoiceForm['contact_email'] ?? ''),
        ];
    }

    protected function hydrateManualInvoiceFromCosting(JobCosting $costing): void
    {
        $invoiceType = $this->manualInvoiceForm['invoice_type'] ?? Invoice::TYPE_ACCOUNTS_RECEIVABLE;
        $lines = $this->invoiceLinesFromCosting($costing, $invoiceType);
        $subtotal = $this->invoiceLineSubtotal($lines);

        $this->manualInvoiceForm = [
            ...$this->manualInvoiceForm,
            'shipment_job_id' => $costing->shipment_job_id ? (string) $costing->shipment_job_id : ($this->manualInvoiceForm['shipment_job_id'] ?? ''),
            'job_costing_id' => (string) $costing->id,
            'quote_id' => $costing->quote_id ? (string) $costing->quote_id : '',
            'opportunity_id' => $costing->opportunity_id ? (string) $costing->opportunity_id : '',
            'lead_id' => $costing->lead_id ? (string) $costing->lead_id : '',
            'invoice_type' => $invoiceType,
            'bill_to_name' => $costing->customer_name ?: '',
            'contact_email' => $this->manualInvoiceForm['contact_email'] ?? '',
            'issue_date' => $this->manualInvoiceForm['issue_date'] ?? now()->toDateString(),
            'due_date' => $this->manualInvoiceForm['due_date'] ?? now()->addDays(14)->toDateString(),
            'currency' => $costing->currency ?: 'AED',
            'subtotal_amount' => $subtotal > 0 ? (string) $subtotal : '',
            'tax_amount' => $this->manualInvoiceForm['tax_amount'] ?? '0',
            'paid_amount' => $this->manualInvoiceForm['paid_amount'] ?? '0',
            'total_amount' => $subtotal > 0 ? (string) $subtotal : '',
            'balance_amount' => $subtotal > 0 ? (string) $subtotal : '',
            'status' => $this->manualInvoiceForm['status'] ?? Invoice::STATUS_DRAFT,
            'notes' => $costing->notes ?: '',
            'lines' => $lines,
        ];
    }

    protected function fillManualShipmentFormFromShipment(ShipmentJob $shipment): void
    {
        $this->manualShipmentForm = [
            'customer_record_id' => $shipment->account_id ?: '',
            'opportunity_id' => $shipment->opportunity_id ?: '',
            'quote_id' => $shipment->quote_id ?: '',
            'lead_id' => $shipment->lead_id ?: '',
            'company_name' => $shipment->company_name ?: '',
            'contact_name' => $shipment->contact_name ?: '',
            'contact_email' => $shipment->contact_email ?: '',
            'service_mode' => $shipment->service_mode ?: 'Ocean Freight',
            'origin' => $shipment->origin ?: '',
            'destination' => $shipment->destination ?: '',
            'incoterm' => $shipment->incoterm ?: '',
            'commodity' => $shipment->commodity ?: '',
            'equipment_type' => $shipment->equipment_type ?: '',
            'container_count' => $shipment->container_count !== null ? (string) $shipment->container_count : '',
            'weight_kg' => $shipment->weight_kg !== null ? (string) $shipment->weight_kg : '',
            'volume_cbm' => $shipment->volume_cbm !== null ? (string) $shipment->volume_cbm : '',
            'carrier_name' => $shipment->carrier_name ?: '',
            'vessel_name' => $shipment->vessel_name ?: '',
            'voyage_number' => $shipment->voyage_number ?: '',
            'house_bill_no' => $shipment->house_bill_no ?: '',
            'master_bill_no' => $shipment->master_bill_no ?: '',
            'estimated_departure_at' => $shipment->estimated_departure_at?->format('Y-m-d\TH:i') ?: '',
            'estimated_arrival_at' => $shipment->estimated_arrival_at?->format('Y-m-d\TH:i') ?: '',
            'actual_departure_at' => $shipment->actual_departure_at?->format('Y-m-d\TH:i') ?: '',
            'actual_arrival_at' => $shipment->actual_arrival_at?->format('Y-m-d\TH:i') ?: '',
            'buy_amount' => $shipment->buy_amount !== null ? (string) $shipment->buy_amount : '',
            'sell_amount' => $shipment->sell_amount !== null ? (string) $shipment->sell_amount : '',
            'currency' => $shipment->currency ?: 'AED',
            'status' => $shipment->status ?: ShipmentJob::STATUS_DRAFT,
            'notes' => $shipment->notes ?: '',
        ];
    }

    protected function fillManualProjectFormFromProject(Project $project): void
    {
        $this->manualProjectForm = [
            'customer_record_id' => $project->account_id ?: '',
            'opportunity_id' => $project->opportunity_id ?: '',
            'lead_id' => $project->lead_id ?: '',
            'project_name' => $project->project_name ?: '',
            'customer_name' => $project->customer_name ?: '',
            'contact_name' => $project->contact_name ?: '',
            'contact_email' => $project->contact_email ?: '',
            'service_type' => $project->service_type ?: 'Container Conversion',
            'container_type' => $project->container_type ?: '',
            'unit_quantity' => $project->unit_quantity !== null ? (string) $project->unit_quantity : '',
            'scope_summary' => $project->scope_summary ?: '',
            'site_location' => $project->site_location ?: '',
            'target_delivery_date' => $project->target_delivery_date?->format('Y-m-d') ?: '',
            'target_installation_date' => $project->target_installation_date?->format('Y-m-d') ?: '',
            'estimated_value' => $project->estimated_value !== null ? (string) $project->estimated_value : '',
            'status' => $project->status ?: Project::STATUS_DRAFT,
            'notes' => $project->notes ?: '',
        ];
    }

    protected function hydrateManualProjectFromAccount(Account $account): void
    {
        $primaryContact = $account->contacts->sortByDesc('last_activity_at')->first();

        $this->manualProjectForm = [
            ...$this->manualProjectForm,
            'customer_record_id' => (string) $account->id,
            'opportunity_id' => '',
            'lead_id' => '',
            'project_name' => ($account->name ?: 'Project').' Conversion',
            'customer_name' => $account->name ?: '',
            'contact_name' => $primaryContact?->full_name ?: '',
            'contact_email' => $primaryContact?->email ?: ($account->primary_email ?: ''),
            'service_type' => $account->latest_service ?: 'Container Conversion',
            'container_type' => '',
            'unit_quantity' => '',
            'scope_summary' => '',
            'site_location' => '',
            'estimated_value' => '',
            'status' => $this->manualProjectForm['status'] ?? Project::STATUS_DRAFT,
            'notes' => $account->notes ?: '',
        ];
    }

    protected function hydrateManualProjectFromOpportunity(Opportunity $opportunity): void
    {
        $lead = $opportunity->lead;
        $account = $opportunity->account;
        $primaryContact = $account?->contacts->sortByDesc('last_activity_at')->first();

        $this->manualProjectForm = [
            ...$this->manualProjectForm,
            'customer_record_id' => $opportunity->account_id ? (string) $opportunity->account_id : ($this->manualProjectForm['customer_record_id'] ?? ''),
            'opportunity_id' => (string) $opportunity->id,
            'lead_id' => $opportunity->lead_id ? (string) $opportunity->lead_id : '',
            'project_name' => $opportunity->company_name ? $opportunity->company_name.' Conversion Project' : ($this->manualProjectForm['project_name'] ?? 'Conversion Project'),
            'customer_name' => $opportunity->company_name ?: ($account?->name ?: ''),
            'contact_name' => $lead?->contact_name ?: ($primaryContact?->full_name ?: ($this->manualProjectForm['contact_name'] ?? '')),
            'contact_email' => $opportunity->contact_email ?: ($lead?->email ?: ($primaryContact?->email ?: ($this->manualProjectForm['contact_email'] ?? ''))),
            'service_type' => $opportunity->required_service ?: ($lead?->service ?: ($this->manualProjectForm['service_type'] ?? 'Container Conversion')),
            'estimated_value' => $opportunity->revenue_potential !== null ? (string) $opportunity->revenue_potential : '',
            'scope_summary' => $opportunity->notes ?: ($this->manualProjectForm['scope_summary'] ?? ''),
            'status' => $this->manualProjectForm['status'] ?? Project::STATUS_DRAFT,
            'notes' => $opportunity->notes ?: ($this->manualProjectForm['notes'] ?? ''),
        ];
    }

    protected function hydrateManualShipmentFromOpportunity(Opportunity $opportunity): void
    {
        $preferredQuote = $opportunity->quotes
            ->sortByDesc(fn (Quote $quote) => ($quote->status === Quote::STATUS_ACCEPTED ? 1000000 : 0) + ($quote->quoted_at?->timestamp ?? 0) + $quote->id)
            ->first();

        $lead = $opportunity->lead;

        $this->manualShipmentForm = [
            ...$this->manualShipmentForm,
            'customer_record_id' => $opportunity->account_id ? (string) $opportunity->account_id : ($this->manualShipmentForm['customer_record_id'] ?? ''),
            'opportunity_id' => (string) $opportunity->id,
            'quote_id' => $preferredQuote?->id ? (string) $preferredQuote->id : '',
            'lead_id' => $opportunity->lead_id ? (string) $opportunity->lead_id : '',
            'company_name' => $opportunity->company_name ?: $lead?->company_name ?: '',
            'contact_name' => $lead?->contact_name ?: $preferredQuote?->contact_name ?: '',
            'contact_email' => $opportunity->contact_email ?: $lead?->email ?: $preferredQuote?->contact_email ?: '',
            'service_mode' => $preferredQuote?->service_mode ?: $opportunity->required_service ?: $lead?->service ?: 'Ocean Freight',
            'origin' => $preferredQuote?->origin ?: ($this->manualShipmentForm['origin'] ?? ''),
            'destination' => $preferredQuote?->destination ?: ($this->manualShipmentForm['destination'] ?? ''),
            'incoterm' => $preferredQuote?->incoterm ?: '',
            'commodity' => $preferredQuote?->commodity ?: '',
            'equipment_type' => $preferredQuote?->equipment_type ?: '',
            'container_count' => $this->manualShipmentForm['container_count'] ?? '',
            'weight_kg' => $preferredQuote?->weight_kg !== null ? (string) $preferredQuote->weight_kg : '',
            'volume_cbm' => $preferredQuote?->volume_cbm !== null ? (string) $preferredQuote->volume_cbm : '',
            'carrier_name' => $this->manualShipmentForm['carrier_name'] ?? '',
            'vessel_name' => $this->manualShipmentForm['vessel_name'] ?? '',
            'voyage_number' => $this->manualShipmentForm['voyage_number'] ?? '',
            'house_bill_no' => $this->manualShipmentForm['house_bill_no'] ?? '',
            'master_bill_no' => $this->manualShipmentForm['master_bill_no'] ?? '',
            'estimated_departure_at' => $this->manualShipmentForm['estimated_departure_at'] ?? '',
            'estimated_arrival_at' => $this->manualShipmentForm['estimated_arrival_at'] ?? '',
            'actual_departure_at' => $this->manualShipmentForm['actual_departure_at'] ?? '',
            'actual_arrival_at' => $this->manualShipmentForm['actual_arrival_at'] ?? '',
            'buy_amount' => $preferredQuote?->buy_amount !== null ? (string) $preferredQuote->buy_amount : '',
            'sell_amount' => $preferredQuote?->sell_amount !== null ? (string) $preferredQuote->sell_amount : ($opportunity->revenue_potential !== null ? (string) $opportunity->revenue_potential : ''),
            'currency' => $preferredQuote?->currency ?: ($this->manualShipmentForm['currency'] ?? 'AED'),
            'status' => $this->manualShipmentForm['status'] ?? ShipmentJob::STATUS_DRAFT,
            'notes' => $preferredQuote?->notes ?: ($opportunity->notes ?: ''),
        ];
    }

    protected function hydrateManualShipmentFromQuote(Quote $quote): void
    {
        if ($quote->opportunity) {
            $this->hydrateManualShipmentFromOpportunity($quote->opportunity->loadMissing('lead'));
        }

        $lead = $quote->lead ?: $quote->opportunity?->lead;

        $this->manualShipmentForm = [
            ...$this->manualShipmentForm,
            'customer_record_id' => $quote->account_id ? (string) $quote->account_id : ($this->manualShipmentForm['customer_record_id'] ?? ''),
            'opportunity_id' => $quote->opportunity_id ? (string) $quote->opportunity_id : ($this->manualShipmentForm['opportunity_id'] ?? ''),
            'quote_id' => (string) $quote->id,
            'lead_id' => $quote->lead_id ? (string) $quote->lead_id : ($lead?->id ? (string) $lead->id : ''),
            'company_name' => $quote->company_name ?: ($this->manualShipmentForm['company_name'] ?? ''),
            'contact_name' => $quote->contact_name ?: ($lead?->contact_name ?: ($this->manualShipmentForm['contact_name'] ?? '')),
            'contact_email' => $quote->contact_email ?: ($quote->opportunity?->contact_email ?: ($lead?->email ?: ($this->manualShipmentForm['contact_email'] ?? ''))),
            'service_mode' => $quote->service_mode ?: ($this->manualShipmentForm['service_mode'] ?? 'Ocean Freight'),
            'origin' => $quote->origin ?: '',
            'destination' => $quote->destination ?: '',
            'incoterm' => $quote->incoterm ?: '',
            'commodity' => $quote->commodity ?: '',
            'equipment_type' => $quote->equipment_type ?: '',
            'weight_kg' => $quote->weight_kg !== null ? (string) $quote->weight_kg : '',
            'volume_cbm' => $quote->volume_cbm !== null ? (string) $quote->volume_cbm : '',
            'buy_amount' => $quote->buy_amount !== null ? (string) $quote->buy_amount : '',
            'sell_amount' => $quote->sell_amount !== null ? (string) $quote->sell_amount : '',
            'currency' => $quote->currency ?: ($this->manualShipmentForm['currency'] ?? 'AED'),
            'notes' => $quote->notes ?: ($this->manualShipmentForm['notes'] ?? ''),
        ];
    }

    protected function fillShipmentEditForm(ShipmentJob $shipment): void
    {
        $this->shipmentEditForm = [
            'company_name' => $shipment->company_name ?: '',
            'contact_name' => $shipment->contact_name ?: '',
            'contact_email' => $shipment->contact_email ?: '',
            'service_mode' => $shipment->service_mode ?: 'Ocean Freight',
            'origin' => $shipment->origin ?: '',
            'destination' => $shipment->destination ?: '',
            'incoterm' => $shipment->incoterm ?: '',
            'commodity' => $shipment->commodity ?: '',
            'equipment_type' => $shipment->equipment_type ?: '',
            'container_count' => $shipment->container_count !== null ? (string) $shipment->container_count : '',
            'weight_kg' => $shipment->weight_kg !== null ? (string) $shipment->weight_kg : '',
            'volume_cbm' => $shipment->volume_cbm !== null ? (string) $shipment->volume_cbm : '',
            'carrier_name' => $shipment->carrier_name ?: '',
            'vessel_name' => $shipment->vessel_name ?: '',
            'voyage_number' => $shipment->voyage_number ?: '',
            'house_bill_no' => $shipment->house_bill_no ?: '',
            'master_bill_no' => $shipment->master_bill_no ?: '',
            'estimated_departure_at' => $shipment->estimated_departure_at?->format('Y-m-d\TH:i') ?: '',
            'estimated_arrival_at' => $shipment->estimated_arrival_at?->format('Y-m-d\TH:i') ?: '',
            'actual_departure_at' => $shipment->actual_departure_at?->format('Y-m-d\TH:i') ?: '',
            'actual_arrival_at' => $shipment->actual_arrival_at?->format('Y-m-d\TH:i') ?: '',
            'buy_amount' => $shipment->buy_amount !== null ? (string) $shipment->buy_amount : '',
            'sell_amount' => $shipment->sell_amount !== null ? (string) $shipment->sell_amount : '',
            'currency' => $shipment->currency ?: 'AED',
            'status' => $shipment->status ?: ShipmentJob::STATUS_DRAFT,
            'notes' => $shipment->notes ?: '',
        ];
    }

    protected function fillProjectEditForm(Project $project): void
    {
        $this->projectEditForm = [
            'project_name' => $project->project_name ?: '',
            'customer_name' => $project->customer_name ?: '',
            'contact_name' => $project->contact_name ?: '',
            'contact_email' => $project->contact_email ?: '',
            'service_type' => $project->service_type ?: 'Container Conversion',
            'container_type' => $project->container_type ?: '',
            'unit_quantity' => $project->unit_quantity !== null ? (string) $project->unit_quantity : '',
            'scope_summary' => $project->scope_summary ?: '',
            'site_location' => $project->site_location ?: '',
            'target_delivery_date' => $project->target_delivery_date?->format('Y-m-d') ?: '',
            'target_installation_date' => $project->target_installation_date?->format('Y-m-d') ?: '',
            'actual_delivery_date' => $project->actual_delivery_date?->format('Y-m-d') ?: '',
            'actual_installation_date' => $project->actual_installation_date?->format('Y-m-d') ?: '',
            'estimated_value' => $project->estimated_value !== null ? (string) $project->estimated_value : '',
            'status' => $project->status ?: Project::STATUS_DRAFT,
            'notes' => $project->notes ?: '',
        ];
    }

    protected function fillDrawingEditForm(ProjectDrawing $drawing): void
    {
        $this->drawingEditForm = [
            'revision_number' => $drawing->revision_number ?: '',
            'drawing_title' => $drawing->drawing_title ?: '',
            'status' => $drawing->status ?: ProjectDrawing::STATUS_DRAFT,
            'external_url' => $drawing->external_url ?: '',
            'submitted_at' => $drawing->submitted_at?->format('Y-m-d\TH:i') ?: '',
            'approved_at' => $drawing->approved_at?->format('Y-m-d\TH:i') ?: '',
            'notes' => $drawing->notes ?: '',
        ];
    }

    protected function fillDeliveryEditForm(ProjectDeliveryMilestone $delivery): void
    {
        $this->deliveryEditForm = [
            'milestone_label' => $delivery->milestone_label ?: '',
            'sequence' => (string) ($delivery->sequence ?? 0),
            'planned_date' => $delivery->planned_date?->format('Y-m-d') ?: '',
            'actual_date' => $delivery->actual_date?->format('Y-m-d') ?: '',
            'status' => $delivery->status ?: ProjectDeliveryMilestone::STATUS_PENDING,
            'site_location' => $delivery->site_location ?: '',
            'requires_crane' => (bool) $delivery->requires_crane,
            'installation_required' => (bool) $delivery->installation_required,
            'notes' => $delivery->notes ?: '',
        ];
    }

    protected function defaultProjectDeliveryBlueprints(): array
    {
        return [
            ['milestone_label' => 'Design Kickoff', 'sequence' => 10, 'status' => ProjectDeliveryMilestone::STATUS_PENDING],
            ['milestone_label' => 'Drawings Submitted', 'sequence' => 20, 'status' => ProjectDeliveryMilestone::STATUS_PENDING],
            ['milestone_label' => 'Drawings Approved', 'sequence' => 30, 'status' => ProjectDeliveryMilestone::STATUS_PENDING],
            ['milestone_label' => 'Fabrication Start', 'sequence' => 40, 'status' => ProjectDeliveryMilestone::STATUS_PENDING],
            ['milestone_label' => 'Ready For Dispatch', 'sequence' => 50, 'status' => ProjectDeliveryMilestone::STATUS_PENDING],
            ['milestone_label' => 'Delivered To Site', 'sequence' => 60, 'status' => ProjectDeliveryMilestone::STATUS_PENDING],
            ['milestone_label' => 'Installation Complete', 'sequence' => 70, 'status' => ProjectDeliveryMilestone::STATUS_PENDING],
            ['milestone_label' => 'Handover Signed', 'sequence' => 80, 'status' => ProjectDeliveryMilestone::STATUS_PENDING],
        ];
    }

    protected function ensureProjectExecutionDefaults(Project $project): void
    {
        $project->loadMissing(['deliveryMilestones']);

        $existingMilestones = $project->deliveryMilestones->keyBy(function (ProjectDeliveryMilestone $milestone) {
            return Str::lower(trim($milestone->milestone_label));
        });

        foreach ($this->defaultProjectDeliveryBlueprints() as $milestone) {
            $key = Str::lower(trim($milestone['milestone_label']));

            if ($existingMilestones->has($key)) {
                continue;
            }

            ProjectDeliveryMilestone::create([
                'company_id' => $project->company_id,
                'workspace_id' => $project->workspace_id,
                'project_id' => $project->id,
                'assigned_user_id' => $project->assigned_user_id,
                'milestone_label' => $milestone['milestone_label'],
                'sequence' => $milestone['sequence'],
                'status' => $milestone['status'],
                'site_location' => $project->site_location,
                'installation_required' => true,
            ]);
        }
    }

    protected function shipmentMilestoneBlueprints(): array
    {
        return [
            ['event_key' => 'booking_requested', 'label' => 'Booking Requested', 'sequence' => 10],
            ['event_key' => 'booked', 'label' => 'Booked With Carrier', 'sequence' => 20],
            ['event_key' => 'departed', 'label' => 'Departed Origin', 'sequence' => 30],
            ['event_key' => 'arrived', 'label' => 'Arrived Destination', 'sequence' => 40],
            ['event_key' => 'customs_clearance', 'label' => 'Customs Clearance', 'sequence' => 50],
            ['event_key' => 'delivered', 'label' => 'Delivered', 'sequence' => 60],
        ];
    }

    protected function defaultShipmentDocumentBlueprints(): array
    {
        return [
            ShipmentDocument::TYPE_BOOKING_CONFIRMATION,
            ShipmentDocument::TYPE_HOUSE_BILL,
            ShipmentDocument::TYPE_MASTER_BILL,
            ShipmentDocument::TYPE_COMMERCIAL_INVOICE,
            ShipmentDocument::TYPE_DELIVERY_ORDER,
        ];
    }

    protected function ensureShipmentExecutionDefaults(ShipmentJob $shipment): void
    {
        $shipment->loadMissing(['milestones', 'documents', 'bookings', 'invoices']);

        $existingMilestones = $shipment->milestones->keyBy('event_key');

        foreach ($this->shipmentMilestoneBlueprints() as $milestone) {
            if ($existingMilestones->has($milestone['event_key'])) {
                continue;
            }

            ShipmentMilestone::create([
                'company_id' => $shipment->company_id,
                'workspace_id' => $shipment->workspace_id,
                'shipment_job_id' => $shipment->id,
                'event_key' => $milestone['event_key'],
                'label' => $milestone['label'],
                'sequence' => $milestone['sequence'],
                'status' => ShipmentMilestone::STATUS_PENDING,
            ]);
        }

        $existingDocuments = $shipment->documents->keyBy('document_type');

        foreach ($this->defaultShipmentDocumentBlueprints() as $documentType) {
            if ($existingDocuments->has($documentType)) {
                continue;
            }

            ShipmentDocument::create([
                'company_id' => $shipment->company_id,
                'workspace_id' => $shipment->workspace_id,
                'shipment_job_id' => $shipment->id,
                'document_type' => $documentType,
                'document_name' => $documentType,
                'status' => ShipmentDocument::STATUS_MISSING,
            ]);
        }

        $this->syncShipmentExecutionState($shipment->fresh(['milestones', 'documents', 'bookings', 'invoices']));
    }

    protected function syncShipmentExecutionState(ShipmentJob $shipment): void
    {
        $completeKeys = [];
        $activeKey = null;

        if ($shipment->actual_departure_at) {
            $completeKeys[] = 'departed';
        }

        if ($shipment->actual_arrival_at) {
            $completeKeys[] = 'arrived';
        }

        if ($shipment->status === ShipmentJob::STATUS_BOOKING_REQUESTED) {
            $activeKey = 'booking_requested';
        }

        if ($shipment->status === ShipmentJob::STATUS_BOOKED) {
            $completeKeys = [...$completeKeys, 'booking_requested', 'booked'];
        }

        if ($shipment->status === ShipmentJob::STATUS_IN_TRANSIT) {
            $completeKeys = [...$completeKeys, 'booking_requested', 'booked', 'departed'];
            $activeKey = 'arrived';
        }

        if ($shipment->status === ShipmentJob::STATUS_CUSTOMS_CLEARANCE) {
            $completeKeys = [...$completeKeys, 'booking_requested', 'booked', 'departed', 'arrived'];
            $activeKey = 'customs_clearance';
        }

        if ($shipment->status === ShipmentJob::STATUS_DELIVERED) {
            $completeKeys = [...$completeKeys, 'booking_requested', 'booked', 'departed', 'arrived', 'customs_clearance', 'delivered'];
        }

        $completeKeys = array_values(array_unique($completeKeys));

        foreach ($shipment->milestones as $milestone) {
            if (blank($milestone->event_key)) {
                continue;
            }

            if ($milestone->status === ShipmentMilestone::STATUS_EXCEPTION) {
                continue;
            }

            if (in_array($milestone->event_key, $completeKeys, true)) {
                $milestone->forceFill([
                    'status' => ShipmentMilestone::STATUS_COMPLETED,
                    'completed_at' => $milestone->completed_at ?: now(),
                ])->save();

                continue;
            }

            if ($activeKey !== null && $milestone->event_key === $activeKey && $milestone->status !== ShipmentMilestone::STATUS_COMPLETED) {
                $milestone->forceFill([
                    'status' => ShipmentMilestone::STATUS_IN_PROGRESS,
                ])->save();

                continue;
            }

            if ($milestone->status !== ShipmentMilestone::STATUS_COMPLETED) {
                $milestone->forceFill([
                    'status' => ShipmentMilestone::STATUS_PENDING,
                    'completed_at' => null,
                ])->save();
            }
        }

        $bookingConfirmation = $shipment->documents->firstWhere('document_type', ShipmentDocument::TYPE_BOOKING_CONFIRMATION);
        if ($bookingConfirmation && $bookingConfirmation->status === ShipmentDocument::STATUS_MISSING && $shipment->bookings->contains(fn (Booking $booking) => filled($booking->carrier_confirmation_ref))) {
            $bookingConfirmation->forceFill([
                'status' => ShipmentDocument::STATUS_RECEIVED,
                'uploaded_at' => $bookingConfirmation->uploaded_at ?: now(),
            ])->save();
        }

        $houseBill = $shipment->documents->firstWhere('document_type', ShipmentDocument::TYPE_HOUSE_BILL);
        if ($houseBill && filled($shipment->house_bill_no) && $houseBill->status === ShipmentDocument::STATUS_MISSING) {
            $houseBill->forceFill([
                'status' => ShipmentDocument::STATUS_RECEIVED,
                'reference_number' => $houseBill->reference_number ?: $shipment->house_bill_no,
                'uploaded_at' => $houseBill->uploaded_at ?: now(),
            ])->save();
        }

        $masterBill = $shipment->documents->firstWhere('document_type', ShipmentDocument::TYPE_MASTER_BILL);
        if ($masterBill && filled($shipment->master_bill_no) && $masterBill->status === ShipmentDocument::STATUS_MISSING) {
            $masterBill->forceFill([
                'status' => ShipmentDocument::STATUS_RECEIVED,
                'reference_number' => $masterBill->reference_number ?: $shipment->master_bill_no,
                'uploaded_at' => $masterBill->uploaded_at ?: now(),
            ])->save();
        }

        $commercialInvoice = $shipment->documents->firstWhere('document_type', ShipmentDocument::TYPE_COMMERCIAL_INVOICE);
        if ($commercialInvoice && $shipment->invoices->isNotEmpty() && $commercialInvoice->status === ShipmentDocument::STATUS_MISSING) {
            $commercialInvoice->forceFill([
                'status' => ShipmentDocument::STATUS_RECEIVED,
                'uploaded_at' => $commercialInvoice->uploaded_at ?: now(),
            ])->save();
        }

        $deliveryOrder = $shipment->documents->firstWhere('document_type', ShipmentDocument::TYPE_DELIVERY_ORDER);
        if ($deliveryOrder && $shipment->status === ShipmentJob::STATUS_DELIVERED && $deliveryOrder->status === ShipmentDocument::STATUS_MISSING) {
            $deliveryOrder->forceFill([
                'status' => ShipmentDocument::STATUS_RECEIVED,
                'uploaded_at' => $deliveryOrder->uploaded_at ?: now(),
            ])->save();
        }
    }

    protected function shipmentTimelineRows(ShipmentJob $shipment)
    {
        $rows = collect([
            [
                'at' => $shipment->created_at,
                'title' => 'Shipment job created',
                'detail' => $shipment->job_number.' opened for '.($shipment->company_name ?: 'Unknown company'),
                'tone' => 'neutral',
            ],
        ]);

        foreach ($shipment->milestones as $milestone) {
            if ($milestone->status === ShipmentMilestone::STATUS_PENDING && blank($milestone->planned_at)) {
                continue;
            }

            $rows->push([
                'at' => $milestone->completed_at ?: $milestone->planned_at ?: $milestone->created_at,
                'title' => $milestone->label,
                'detail' => $milestone->status.($milestone->notes ? ' · '.$milestone->notes : ''),
                'tone' => match ($milestone->status) {
                    ShipmentMilestone::STATUS_COMPLETED => 'success',
                    ShipmentMilestone::STATUS_EXCEPTION => 'danger',
                    ShipmentMilestone::STATUS_IN_PROGRESS => 'info',
                    default => 'neutral',
                },
            ]);
        }

        foreach ($shipment->documents as $document) {
            if ($document->status === ShipmentDocument::STATUS_MISSING && blank($document->reference_number) && blank($document->external_url) && blank($document->uploaded_at)) {
                continue;
            }

            $rows->push([
                'at' => $document->uploaded_at ?: $document->created_at,
                'title' => $document->document_name,
                'detail' => $document->document_type.' · '.$document->status.($document->reference_number ? ' · '.$document->reference_number : ''),
                'tone' => match ($document->status) {
                    ShipmentDocument::STATUS_APPROVED => 'success',
                    ShipmentDocument::STATUS_MISSING => 'warning',
                    default => 'neutral',
                },
            ]);
        }

        foreach ($shipment->bookings as $booking) {
            $rows->push([
                'at' => $booking->confirmed_etd ?: $booking->created_at,
                'title' => 'Booking '.$booking->booking_number,
                'detail' => $booking->status.($booking->carrier?->name ? ' · '.$booking->carrier->name : ''),
                'tone' => 'info',
            ]);
        }

        foreach ($shipment->jobCostings as $costing) {
            $rows->push([
                'at' => $costing->created_at,
                'title' => 'Job costing '.$costing->costing_number,
                'detail' => $costing->status.' · '.$costing->currency.' '.number_format((float) $costing->margin_amount, 0),
                'tone' => 'neutral',
            ]);
        }

        foreach ($shipment->invoices as $invoice) {
            $rows->push([
                'at' => $invoice->posted_at ?: $invoice->issue_date ?: $invoice->created_at,
                'title' => 'Invoice '.$invoice->invoice_number,
                'detail' => $invoice->status.' · '.$invoice->currency.' '.number_format((float) $invoice->total_amount, 0),
                'tone' => $invoice->posted_at ? 'success' : 'neutral',
            ]);
        }

        return $rows
            ->filter(fn (array $row) => $row['at'] !== null)
            ->sortByDesc(fn (array $row) => Carbon::parse($row['at'])->timestamp)
            ->values();
    }

    protected function projectTimelineRows(Project $project)
    {
        $rows = collect([
            [
                'at' => $project->created_at,
                'title' => 'Project created',
                'detail' => $project->project_number.' opened for '.($project->customer_name ?: 'Unknown customer'),
            ],
        ]);

        foreach ($project->drawings as $drawing) {
            $rows->push([
                'at' => $drawing->approved_at ?: $drawing->submitted_at ?: $drawing->created_at,
                'title' => 'Drawing '.$drawing->revision_number,
                'detail' => $drawing->status.' · '.$drawing->drawing_title,
            ]);
        }

        foreach ($project->deliveryMilestones as $milestone) {
            $rows->push([
                'at' => $milestone->actual_date ?: $milestone->planned_date ?: $milestone->created_at,
                'title' => $milestone->milestone_label,
                'detail' => $milestone->status.($milestone->site_location ? ' · '.$milestone->site_location : ''),
            ]);
        }

        return $rows
            ->filter(fn (array $row) => $row['at'] !== null)
            ->sortByDesc(fn (array $row) => Carbon::parse($row['at'])->timestamp)
            ->values();
    }

    public function projectStatusClasses(string $status): string
    {
        return match ($status) {
            Project::STATUS_DRAWINGS_APPROVED, Project::STATUS_READY_FOR_DELIVERY, Project::STATUS_DELIVERED, Project::STATUS_INSTALLED, Project::STATUS_CLOSED => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            Project::STATUS_DESIGN_REVIEW, Project::STATUS_DRAWINGS_SUBMITTED, Project::STATUS_FABRICATION => 'border-sky-200 bg-sky-50 text-sky-700',
            Project::STATUS_CANCELLED => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-zinc-200 bg-zinc-50 text-zinc-600',
        };
    }

    public function drawingStatusClasses(string $status): string
    {
        return match ($status) {
            ProjectDrawing::STATUS_APPROVED => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            ProjectDrawing::STATUS_SUBMITTED => 'border-sky-200 bg-sky-50 text-sky-700',
            ProjectDrawing::STATUS_REVISION_REQUESTED => 'border-amber-200 bg-amber-50 text-amber-700',
            ProjectDrawing::STATUS_ARCHIVED => 'border-zinc-200 bg-zinc-100 text-zinc-500',
            default => 'border-zinc-200 bg-zinc-50 text-zinc-600',
        };
    }

    public function deliveryStatusClasses(string $status): string
    {
        return match ($status) {
            ProjectDeliveryMilestone::STATUS_COMPLETED => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            ProjectDeliveryMilestone::STATUS_IN_PROGRESS => 'border-sky-200 bg-sky-50 text-sky-700',
            ProjectDeliveryMilestone::STATUS_DELAYED, ProjectDeliveryMilestone::STATUS_CANCELLED => 'border-rose-200 bg-rose-50 text-rose-700',
            ProjectDeliveryMilestone::STATUS_SCHEDULED => 'border-amber-200 bg-amber-50 text-amber-700',
            default => 'border-zinc-200 bg-zinc-50 text-zinc-600',
        };
    }

    public function shipmentMilestoneStatusClasses(string $status): string
    {
        return match ($status) {
            ShipmentMilestone::STATUS_COMPLETED => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            ShipmentMilestone::STATUS_IN_PROGRESS => 'border-sky-200 bg-sky-50 text-sky-700',
            ShipmentMilestone::STATUS_EXCEPTION => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-zinc-200 bg-zinc-50 text-zinc-600',
        };
    }

    public function shipmentDocumentStatusClasses(string $status): string
    {
        return match ($status) {
            ShipmentDocument::STATUS_APPROVED => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            ShipmentDocument::STATUS_RECEIVED => 'border-sky-200 bg-sky-50 text-sky-700',
            ShipmentDocument::STATUS_SENT => 'border-amber-200 bg-amber-50 text-amber-700',
            ShipmentDocument::STATUS_MISSING => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-zinc-200 bg-zinc-50 text-zinc-600',
        };
    }

    protected function draftShipmentFromWonOpportunity(Opportunity $opportunity): ShipmentJob
    {
        $existingShipment = ShipmentJob::query()
            ->where('workspace_id', $opportunity->workspace_id)
            ->where('opportunity_id', $opportunity->id)
            ->latest('id')
            ->first();

        if ($existingShipment) {
            return $existingShipment;
        }

        $quote = $opportunity->quotes
            ->sortByDesc(fn (Quote $quote) => ($quote->status === Quote::STATUS_ACCEPTED ? 1000000 : 0) + ($quote->quoted_at?->timestamp ?? 0) + $quote->id)
            ->first();

        $shipment = ShipmentJob::create([
            'company_id' => $opportunity->company_id,
            'workspace_id' => $opportunity->workspace_id,
            'opportunity_id' => $opportunity->id,
            'quote_id' => $quote?->id,
            'lead_id' => $opportunity->lead_id,
            'assigned_user_id' => auth()->id() ?: $opportunity->assigned_user_id,
            'job_number' => $this->nextShipmentJobNumber($opportunity->workspace),
            'company_name' => $quote?->company_name ?: $opportunity->company_name ?: $opportunity->lead?->company_name,
            'contact_name' => $quote?->contact_name ?: $opportunity->lead?->contact_name,
            'contact_email' => $quote?->contact_email ?: $opportunity->contact_email ?: $opportunity->lead?->email,
            'service_mode' => $quote?->service_mode ?: $opportunity->required_service ?: $opportunity->lead?->service,
            'origin' => $quote?->origin,
            'destination' => $quote?->destination,
            'incoterm' => $quote?->incoterm,
            'commodity' => $quote?->commodity,
            'equipment_type' => $quote?->equipment_type,
            'weight_kg' => $quote?->weight_kg,
            'volume_cbm' => $quote?->volume_cbm,
            'buy_amount' => $quote?->buy_amount,
            'sell_amount' => $quote?->sell_amount ?: $opportunity->revenue_potential,
            'margin_amount' => $this->shipmentMarginFromPayload([
                'buy_amount' => $quote?->buy_amount,
                'sell_amount' => $quote?->sell_amount ?: $opportunity->revenue_potential,
            ]),
            'currency' => $quote?->currency ?: 'AED',
            'status' => ShipmentJob::STATUS_DRAFT,
            'notes' => $quote?->notes ?: $opportunity->notes,
        ]);

        $this->ensureShipmentExecutionDefaults($shipment);

        return $shipment;
    }

    protected function draftProjectFromAwardedOpportunity(Opportunity $opportunity): Project
    {
        $existingProject = Project::query()
            ->where('workspace_id', $opportunity->workspace_id)
            ->where('opportunity_id', $opportunity->id)
            ->latest('id')
            ->first();

        if ($existingProject) {
            return $existingProject;
        }

        $account = $opportunity->account;
        $primaryContact = $account?->contacts->sortByDesc('last_activity_at')->first();

        $project = Project::create([
            'company_id' => $opportunity->company_id,
            'workspace_id' => $opportunity->workspace_id,
            'account_id' => $opportunity->account_id,
            'contact_id' => $primaryContact?->id,
            'opportunity_id' => $opportunity->id,
            'lead_id' => $opportunity->lead_id,
            'assigned_user_id' => auth()->id() ?: $opportunity->assigned_user_id,
            'project_number' => $this->nextProjectNumber($opportunity->workspace),
            'project_name' => ($opportunity->company_name ?: 'Container').' Conversion Project',
            'customer_name' => $opportunity->company_name ?: ($account?->name ?: 'Unknown customer'),
            'contact_name' => $opportunity->lead?->contact_name ?: $primaryContact?->full_name,
            'contact_email' => $opportunity->contact_email ?: ($opportunity->lead?->email ?: $primaryContact?->email),
            'service_type' => $opportunity->required_service ?: ($opportunity->lead?->service ?: 'Container Conversion'),
            'scope_summary' => $opportunity->notes,
            'estimated_value' => $opportunity->revenue_potential,
            'status' => Project::STATUS_DRAFT,
            'notes' => $opportunity->notes,
        ]);

        $this->ensureProjectExecutionDefaults($project);

        return $project;
    }

    protected function draftCostingFromShipment(ShipmentJob $shipment): JobCosting
    {
        $existingCosting = JobCosting::query()
            ->where('workspace_id', $shipment->workspace_id)
            ->where('shipment_job_id', $shipment->id)
            ->latest('id')
            ->first();

        if ($existingCosting) {
            return $existingCosting;
        }

        $workspace = $shipment->workspace;

        $costing = JobCosting::create([
            'company_id' => $shipment->company_id,
            'workspace_id' => $shipment->workspace_id,
            'shipment_job_id' => $shipment->id,
            'quote_id' => $shipment->quote_id,
            'opportunity_id' => $shipment->opportunity_id,
            'lead_id' => $shipment->lead_id,
            'assigned_user_id' => auth()->id() ?: $shipment->assigned_user_id,
            'costing_number' => $this->nextCostingNumber($workspace),
            'customer_name' => $shipment->company_name ?: 'Unknown customer',
            'service_mode' => $shipment->service_mode,
            'currency' => $shipment->currency ?: 'AED',
            'status' => JobCosting::STATUS_DRAFT,
            'notes' => $shipment->notes,
            ...$this->costingTotalsFromLines($this->costingLinesFromShipment($shipment)),
        ]);

        $this->syncCostingLines($costing, $this->costingLinesFromShipment($shipment));
        $this->applyCostingShipmentConnection($costing->fresh('lines'));

        return $costing;
    }

    protected function resetManualOpportunityForm(): void
    {
        $this->editingOpportunityId = null;
        $this->manualOpportunityForm = [
            'lead_id' => '',
            'company_name' => '',
            'contact_email' => '',
            'lead_source' => 'Email',
            'required_service' => 'Container Conversion',
            'revenue_potential' => '',
            'project_timeline_days' => '',
            'sales_stage' => Opportunity::STAGE_INITIAL_CONTACT,
            'notes' => '',
        ];

    }

    protected function resetManualQuoteForm(): void
    {
        $this->editingQuoteId = null;
        $this->manualQuoteForm = [
            'customer_record_id' => '',
            'opportunity_id' => '',
            'rate_card_id' => '',
            'lead_id' => '',
            'company_name' => '',
            'contact_name' => '',
            'contact_email' => '',
            'service_mode' => 'Ocean Freight',
            'origin' => '',
            'destination' => '',
            'incoterm' => '',
            'commodity' => '',
            'equipment_type' => '',
            'weight_kg' => '',
            'volume_cbm' => '',
            'buy_amount' => '',
            'sell_amount' => '',
            'currency' => 'AED',
            'status' => Quote::STATUS_DRAFT,
            'valid_until' => '',
            'notes' => '',
        ];
    }

    protected function resetManualRateForm(): void
    {
        $this->editingRateId = null;
        $this->manualRateForm = [
            'carrier_id' => '',
            'customer_name' => '',
            'service_mode' => RateCard::MODE_OCEAN,
            'origin' => '',
            'destination' => '',
            'via_port' => '',
            'incoterm' => '',
            'commodity' => '',
            'equipment_type' => '',
            'transit_days' => '',
            'buy_amount' => '',
            'sell_amount' => '',
            'currency' => 'AED',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addMonth()->toDateString(),
            'is_active' => true,
            'notes' => '',
        ];
    }

    protected function resetManualShipmentForm(): void
    {
        $this->editingShipmentId = null;
        $this->manualShipmentForm = [
            'customer_record_id' => '',
            'opportunity_id' => '',
            'quote_id' => '',
            'lead_id' => '',
            'company_name' => '',
            'contact_name' => '',
            'contact_email' => '',
            'service_mode' => 'Ocean Freight',
            'origin' => '',
            'destination' => '',
            'incoterm' => '',
            'commodity' => '',
            'equipment_type' => '',
            'container_count' => '',
            'weight_kg' => '',
            'volume_cbm' => '',
            'carrier_name' => '',
            'vessel_name' => '',
            'voyage_number' => '',
            'house_bill_no' => '',
            'master_bill_no' => '',
            'estimated_departure_at' => '',
            'estimated_arrival_at' => '',
            'actual_departure_at' => '',
            'actual_arrival_at' => '',
            'buy_amount' => '',
            'sell_amount' => '',
            'currency' => 'AED',
            'status' => ShipmentJob::STATUS_DRAFT,
            'notes' => '',
        ];

        $this->manualProjectForm = [
            'customer_record_id' => '',
            'opportunity_id' => '',
            'lead_id' => '',
            'project_name' => '',
            'customer_name' => '',
            'contact_name' => '',
            'contact_email' => '',
            'service_type' => 'Container Conversion',
            'container_type' => '',
            'unit_quantity' => '',
            'scope_summary' => '',
            'site_location' => '',
            'target_delivery_date' => '',
            'target_installation_date' => '',
            'estimated_value' => '',
            'status' => Project::STATUS_DRAFT,
            'notes' => '',
        ];

        $this->manualDrawingForm = [
            'project_id' => '',
            'revision_number' => 'REV-1',
            'drawing_title' => '',
            'status' => ProjectDrawing::STATUS_DRAFT,
            'external_url' => '',
            'submitted_at' => '',
            'approved_at' => '',
            'notes' => '',
        ];

        $this->manualDeliveryForm = [
            'project_id' => '',
            'milestone_label' => '',
            'sequence' => '1',
            'planned_date' => '',
            'actual_date' => '',
            'status' => ProjectDeliveryMilestone::STATUS_PENDING,
            'site_location' => '',
            'requires_crane' => false,
            'installation_required' => false,
            'notes' => '',
        ];

        $this->manualCarrierForm = [
            'name' => '',
            'mode' => '',
            'code' => '',
            'scac_code' => '',
            'iata_code' => '',
            'contact_name' => '',
            'contact_email' => '',
            'contact_phone' => '',
            'website' => '',
            'service_lanes' => '',
            'notes' => '',
            'is_active' => true,
        ];

        $this->manualBookingForm = [
            'shipment_job_id' => '',
            'carrier_id' => '',
            'quote_id' => '',
            'opportunity_id' => '',
            'lead_id' => '',
            'customer_name' => '',
            'contact_name' => '',
            'contact_email' => '',
            'service_mode' => 'Ocean Freight',
            'origin' => '',
            'destination' => '',
            'incoterm' => '',
            'commodity' => '',
            'equipment_type' => '',
            'container_count' => '',
            'weight_kg' => '',
            'volume_cbm' => '',
            'requested_etd' => '',
            'requested_eta' => '',
            'confirmed_etd' => '',
            'confirmed_eta' => '',
            'carrier_confirmation_ref' => '',
            'status' => Booking::STATUS_DRAFT,
            'notes' => '',
        ];

        $this->resetManualCarrierForm();
        $this->resetManualBookingForm();
        $this->resetManualCostingForm();
        $this->resetManualInvoiceForm();
    }

    protected function resetManualProjectForm(): void
    {
        $this->editingProjectId = null;
        $this->manualProjectForm = [
            'customer_record_id' => '',
            'opportunity_id' => '',
            'lead_id' => '',
            'project_name' => '',
            'customer_name' => '',
            'contact_name' => '',
            'contact_email' => '',
            'service_type' => 'Container Conversion',
            'container_type' => '',
            'unit_quantity' => '',
            'scope_summary' => '',
            'site_location' => '',
            'target_delivery_date' => '',
            'target_installation_date' => '',
            'estimated_value' => '',
            'status' => Project::STATUS_DRAFT,
            'notes' => '',
        ];
    }

    protected function resetManualDrawingForm(): void
    {
        $this->editingDrawingId = null;
        $this->manualDrawingForm = [
            'project_id' => '',
            'revision_number' => 'REV-1',
            'drawing_title' => '',
            'status' => ProjectDrawing::STATUS_DRAFT,
            'external_url' => '',
            'submitted_at' => '',
            'approved_at' => '',
            'notes' => '',
        ];
    }

    protected function resetManualDeliveryForm(): void
    {
        $this->editingDeliveryId = null;
        $this->manualDeliveryForm = [
            'project_id' => '',
            'milestone_label' => '',
            'sequence' => '1',
            'planned_date' => '',
            'actual_date' => '',
            'status' => ProjectDeliveryMilestone::STATUS_PENDING,
            'site_location' => '',
            'requires_crane' => false,
            'installation_required' => false,
            'notes' => '',
        ];
    }

    protected function resetManualCarrierForm(): void
    {
        $this->editingCarrierId = null;
        $this->manualCarrierForm = [
            'name' => '',
            'mode' => '',
            'code' => '',
            'scac_code' => '',
            'iata_code' => '',
            'contact_name' => '',
            'contact_email' => '',
            'contact_phone' => '',
            'website' => '',
            'service_lanes' => '',
            'notes' => '',
            'is_active' => true,
        ];
    }

    protected function resetManualBookingForm(): void
    {
        $this->editingBookingId = null;
        $this->manualBookingForm = [
            'shipment_job_id' => '',
            'carrier_id' => '',
            'quote_id' => '',
            'opportunity_id' => '',
            'lead_id' => '',
            'customer_name' => '',
            'contact_name' => '',
            'contact_email' => '',
            'service_mode' => 'Ocean Freight',
            'origin' => '',
            'destination' => '',
            'incoterm' => '',
            'commodity' => '',
            'equipment_type' => '',
            'container_count' => '',
            'weight_kg' => '',
            'volume_cbm' => '',
            'requested_etd' => '',
            'requested_eta' => '',
            'confirmed_etd' => '',
            'confirmed_eta' => '',
            'carrier_confirmation_ref' => '',
            'status' => Booking::STATUS_DRAFT,
            'notes' => '',
        ];
    }

    protected function resetManualCostingForm(): void
    {
        $this->editingCostingId = null;
        $this->manualCostingForm = [
            'shipment_job_id' => '',
            'quote_id' => '',
            'opportunity_id' => '',
            'lead_id' => '',
            'customer_name' => '',
            'service_mode' => '',
            'currency' => 'AED',
            'status' => JobCosting::STATUS_DRAFT,
            'notes' => '',
            'lines' => $this->blankCostingLines(),
        ];
    }

    protected function resetManualInvoiceForm(): void
    {
        $this->editingInvoiceId = null;
        $this->manualInvoiceForm = [
            'shipment_job_id' => '',
            'booking_id' => '',
            'job_costing_id' => '',
            'quote_id' => '',
            'opportunity_id' => '',
            'lead_id' => '',
            'invoice_type' => Invoice::TYPE_ACCOUNTS_RECEIVABLE,
            'bill_to_name' => '',
            'contact_email' => '',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'currency' => 'AED',
            'subtotal_amount' => '',
            'tax_amount' => '0',
            'paid_amount' => '0',
            'total_amount' => '',
            'balance_amount' => '',
            'status' => Invoice::STATUS_DRAFT,
            'notes' => '',
            'lines' => $this->blankInvoiceLines(),
        ];
    }

    protected function resetShipmentMilestoneForm(): void
    {
        $this->shipmentMilestoneForm = [
            'label' => '',
            'planned_at' => '',
            'status' => ShipmentMilestone::STATUS_PENDING,
            'notes' => '',
        ];
    }

    protected function resetShipmentDocumentForm(): void
    {
        $this->shipmentDocumentForm = [
            'document_type' => ShipmentDocument::TYPE_OTHER,
            'document_name' => '',
            'reference_number' => '',
            'external_url' => '',
            'status' => ShipmentDocument::STATUS_RECEIVED,
            'uploaded_at' => now()->format('Y-m-d\TH:i'),
            'notes' => '',
        ];
    }

    protected function defaultCollaborationForm(): array
    {
        return [
            'type' => CollaborationEntry::TYPE_NOTE,
            'body' => '',
            'recipient_user_id' => '',
        ];
    }

    protected function resetCollaborationForm(?string $recordType = null): void
    {
        if ($recordType === null) {
            foreach (array_keys($this->collaborationForms ?: [
                'lead' => [],
                'opportunity' => [],
                'quote' => [],
                'shipment' => [],
                'booking' => [],
                'costing' => [],
                'invoice' => [],
                'contact' => [],
                'customer' => [],
            ]) as $key) {
                $this->collaborationForms[$key] = $this->defaultCollaborationForm();
            }

            return;
        }

        $this->collaborationForms[$recordType] = $this->defaultCollaborationForm();
    }

    protected function blankCostingLine(): array
    {
        return [
            'line_type' => JobCostingLine::TYPE_COST,
            'charge_code' => '',
            'description' => '',
            'vendor_name' => '',
            'quantity' => '1',
            'unit_amount' => '',
            'is_billable' => true,
            'notes' => '',
        ];
    }

    protected function blankCostingLines(): array
    {
        return [$this->blankCostingLine()];
    }

    protected function blankInvoiceLine(): array
    {
        return [
            'job_costing_line_id' => '',
            'charge_code' => '',
            'description' => '',
            'quantity' => '1',
            'unit_amount' => '',
            'notes' => '',
        ];
    }

    protected function blankInvoiceLines(): array
    {
        return [$this->blankInvoiceLine()];
    }

    protected function costingLinesFromShipment(ShipmentJob $shipment): array
    {
        $lines = [];

        if ($shipment->buy_amount !== null && (float) $shipment->buy_amount > 0) {
            $lines[] = [
                'line_type' => JobCostingLine::TYPE_COST,
                'charge_code' => 'BUY-FRT',
                'description' => 'Freight buy',
                'vendor_name' => $shipment->carrier_name ?: '',
                'quantity' => '1',
                'unit_amount' => (string) $shipment->buy_amount,
                'is_billable' => true,
                'notes' => '',
            ];
        }

        if ($shipment->sell_amount !== null && (float) $shipment->sell_amount > 0) {
            $lines[] = [
                'line_type' => JobCostingLine::TYPE_REVENUE,
                'charge_code' => 'SELL-FRT',
                'description' => 'Freight sell',
                'vendor_name' => $shipment->company_name ?: '',
                'quantity' => '1',
                'unit_amount' => (string) $shipment->sell_amount,
                'is_billable' => true,
                'notes' => '',
            ];
        }

        return $lines !== [] ? $lines : $this->blankCostingLines();
    }

    public function render()
    {
        $workspaces = $this->accessibleWorkspaces();
        $workspace = $this->resolveCurrentWorkspace($workspaces);
        $this->workspaceId = $workspace?->id;

        $companies = $this->visibleCompanies($workspaces);
        $roles = collect();
        $permissions = collect();

        $sheetSources = collect();
        $hasSheetSources = false;
        $sheetSourceStats = [
            'total' => 0,
            'active' => 0,
            'synced' => 0,
            'failed' => 0,
        ];
        $workspaceUsers = collect();
        $canManageAccess = false;
        $canViewWorkspaceTools = false;
        $canViewSources = false;
        $leads = Lead::query()->whereRaw('1 = 0')->paginate(
            $this->leadPerPage,
            ['*'],
            'leadsPage',
        );
        $opportunities = Opportunity::query()->whereRaw('1 = 0')->paginate(
            $this->opportunityPerPage,
            ['*'],
            'opportunitiesPage',
        );
        $contacts = Contact::query()->whereRaw('1 = 0')->paginate(
            $this->contactPerPage,
            ['*'],
            'contactsPage',
        );
        $customers = Account::query()->whereRaw('1 = 0')->paginate(
            $this->customerPerPage,
            ['*'],
            'customersPage',
        );
        $rates = RateCard::query()->whereRaw('1 = 0')->paginate(
            $this->ratePerPage,
            ['*'],
            'ratesPage',
        );
        $quotes = Quote::query()->whereRaw('1 = 0')->paginate(
            $this->quotePerPage,
            ['*'],
            'quotesPage',
        );
        $shipments = ShipmentJob::query()->whereRaw('1 = 0')->paginate(
            $this->shipmentPerPage,
            ['*'],
            'shipmentsPage',
        );
        $carriers = Carrier::query()->whereRaw('1 = 0')->paginate(
            $this->carrierPerPage,
            ['*'],
            'carriersPage',
        );
        $bookings = Booking::query()->whereRaw('1 = 0')->paginate(
            $this->bookingPerPage,
            ['*'],
            'bookingsPage',
        );
        $costings = JobCosting::query()->whereRaw('1 = 0')->paginate(
            $this->costingPerPage,
            ['*'],
            'costingsPage',
        );
        $invoices = Invoice::query()->whereRaw('1 = 0')->paginate(
            $this->invoicePerPage,
            ['*'],
            'invoicesPage',
        );
        $selectedLead = null;
        $selectedOpportunity = null;
        $selectedContact = null;
        $selectedCustomer = null;
        $selectedRate = null;
        $selectedQuote = null;
        $selectedShipment = null;
        $selectedCarrier = null;
        $selectedBooking = null;
        $selectedCosting = null;
        $selectedInvoice = null;
        $selectedLeadCollaboration = collect();
        $selectedOpportunityCollaboration = collect();
        $selectedQuoteCollaboration = collect();
        $selectedShipmentCollaboration = collect();
        $selectedBookingCollaboration = collect();
        $selectedCostingCollaboration = collect();
        $selectedInvoiceCollaboration = collect();
        $selectedContactCollaboration = collect();
        $selectedCustomerCollaboration = collect();
        $selectedProjectCollaboration = collect();
        $selectedShipmentTimeline = collect();
        $selectedProjectTimeline = collect();
        $leadInsights = [];
        $opportunityInsights = [];
        $contactInsights = [];
        $customerInsights = [];
        $latestReport = null;
        $monthlyReports = collect();
        $kpis = [];
        $leadOptions = collect();
        $quoteOptions = collect();
        $rateCardOptions = collect();
        $quoteCustomerOptions = collect();
        $quoteOpportunityOptions = collect();
        $shipmentCustomerOptions = collect();
        $shipmentOpportunityOptions = collect();
        $shipmentQuoteOptions = collect();
        $projectCustomerOptions = collect();
        $projectOpportunityOptions = collect();
        $drawingProjectOptions = collect();
        $deliveryProjectOptions = collect();
        $carrierOptions = collect();
        $bookingShipmentOptions = collect();
        $invoiceBookingOptions = collect();
        $costingShipmentOptions = collect();
        $invoiceShipmentOptions = collect();
        $invoiceCostingOptions = collect();
        $projects = collect();
        $drawings = collect();
        $deliveryMilestones = collect();
        $selectedProject = null;
        $selectedDrawing = null;
        $selectedDelivery = null;
        $selectedBookingInvoices = collect();
        $workspaceNotifications = collect();
        $unreadWorkspaceNotificationCount = 0;
        $analyticsKpis = [];
        $analyticsBreakdownRows = collect();
        $analyticsMonthlyRows = collect();
        $analyticsSnapshot = [];
        $analyticsSqlChartRows = collect();
        $analyticsAdsChartRows = collect();
        $analyticsWonCustomers = collect();
        $analyticsDealSummary = [];
        $analyticsEfficiency = [];
        $analyticsAvailableMonths = collect();
        $segmentDefinitions = collect();
        $segmentMetricCatalog = CustomerSegmentationService::metricCatalog();
        $segmentOperatorCatalog = CustomerSegmentationService::operatorCatalog();
        $segmentColorOptions = CustomerSegmentDefinition::COLORS;

        if ($workspace) {
            $canManageAccess = $this->canManageWorkspaceAccess($workspace);
            $canViewWorkspaceTools = true;
            $canViewSources = $canManageAccess || auth()->user()->hasRole(['admin', 'manager']);
            $segmentation = app(CustomerSegmentationService::class);

            $availableTabs = [
                ...$this->availableTabsForWorkspace($workspace, $canManageAccess, $canViewWorkspaceTools, $canViewSources),
                'manual-lead' => 'Add Lead',
                'manual-opportunity' => 'Add Opportunity',
                'manual-rate' => 'New Rate',
                'manual-quote' => 'New Quote',
                'manual-shipment' => 'New Shipment',
                'manual-project' => 'New Project',
                'manual-drawing' => 'New Drawing',
                'manual-delivery' => 'New Delivery Milestone',
                'manual-carrier' => 'New Carrier',
                'manual-booking' => 'New Booking',
                'manual-costing' => 'New Job Costing',
                'manual-invoice' => 'New Invoice',
            ];

            if ($canViewSources) {
                $availableTabs['sources'] = 'Sources';
            }

            if ($canManageAccess) {
                $availableTabs['access'] = 'Access';
            }

            if (! array_key_exists($this->activeTab, $availableTabs)) {
                $this->activeTab = 'leads';
            }

            $needsLeads = in_array($this->activeTab, ['leads', 'manual-lead', 'manual-opportunity'], true) || $this->selectedLeadId !== null;
            $needsOpportunities = in_array($this->activeTab, ['opportunities', 'manual-opportunity', 'manual-project'], true) || $this->selectedOpportunityId !== null;
            $needsContacts = $this->activeTab === 'contacts' || $this->selectedContactId !== null;
            $needsCustomers = in_array($this->activeTab, ['customers', 'manual-quote', 'manual-shipment', 'manual-project'], true)
                || $this->selectedCustomerId !== null
                || $this->customerSegmentFilter !== '';
            $needsRates = in_array($this->activeTab, ['rates', 'manual-rate', 'manual-quote'], true) || $this->selectedRateId !== null;
            $needsQuotes = in_array($this->activeTab, ['quotes', 'manual-quote', 'manual-shipment'], true) || $this->selectedQuoteId !== null;
            $needsShipments = in_array($this->activeTab, ['shipments', 'manual-shipment', 'manual-booking', 'manual-costing', 'manual-invoice'], true) || $this->selectedShipmentId !== null;
            $needsProjects = in_array($this->activeTab, ['projects', 'manual-project', 'manual-drawing', 'manual-delivery'], true) || $this->selectedProjectId !== null;
            $needsDrawings = in_array($this->activeTab, ['drawings', 'manual-drawing'], true) || $this->selectedDrawingId !== null;
            $needsDelivery = in_array($this->activeTab, ['delivery_tracking', 'manual-delivery'], true) || $this->selectedDeliveryId !== null;
            $needsCarriers = in_array($this->activeTab, ['carriers', 'manual-carrier', 'manual-rate', 'manual-booking'], true) || $this->selectedCarrierId !== null || $this->selectedBookingId !== null;
            $needsBookings = in_array($this->activeTab, ['bookings', 'manual-booking', 'manual-invoice'], true) || $this->selectedBookingId !== null || $this->invoiceBookingFilter !== '';
            $needsCostings = in_array($this->activeTab, ['costings', 'manual-costing', 'manual-invoice'], true) || $this->selectedCostingId !== null;
            $needsInvoices = in_array($this->activeTab, ['invoices', 'manual-invoice'], true) || $this->selectedInvoiceId !== null || $this->invoiceBookingFilter !== '' || $this->selectedBookingId !== null;
            $needsAnalytics = $this->activeTab === 'analytics';
            $needsSources = $this->activeTab === 'sources' && $canViewSources;
            $needsAccess = $this->activeTab === 'access' && $canManageAccess;
            $needsSettings = $this->activeTab === 'settings';
            $needsWorkspaceUsers = $needsAccess
                || $this->selectedLeadId !== null
                || $this->selectedOpportunityId !== null
                || $this->selectedQuoteId !== null
                || $this->selectedShipmentId !== null
                || $this->selectedProjectId !== null
                || $this->selectedBookingId !== null
                || $this->selectedCostingId !== null
                || $this->selectedInvoiceId !== null
                || $this->selectedContactId !== null
                || $this->selectedCustomerId !== null;

            $needsSegmentationSync = $this->activeTab === 'customers'
                || ($this->activeTab === 'settings' && $this->settingsTab === 'segmentations')
                || $this->selectedCustomerId !== null
                || $this->customerSegmentFilter !== '';

            if ($needsSegmentationSync) {
                $segmentation->syncWorkspaceIfStale($workspace);
            } else {
                $segmentation->ensureDefaultSegments($workspace);
            }

            if (($needsSettings && $this->settingsTab === 'segmentations') || $needsCustomers) {
                $segmentDefinitions = $workspace->segmentDefinitions()
                    ->with('rules')
                    ->orderByDesc('priority')
                    ->orderBy('id')
                    ->get();
            }

            if ($needsLeads) {
                $leads = $this->applyLeadSorting($this->buildLeadQuery($workspace))
                    ->paginate($this->leadPerPage, ['*'], 'leadsPage');
            }

            if ($needsOpportunities) {
                $opportunities = $this->applyOpportunitySorting($this->buildOpportunityQuery($workspace))
                    ->paginate($this->opportunityPerPage, ['*'], 'opportunitiesPage');
            }

            if ($needsContacts) {
                $contacts = $this->applyContactSorting($this->buildContactsQuery($workspace))
                    ->paginate($this->contactPerPage, ['*'], 'contactsPage');
            }

            if ($needsCustomers) {
                $customers = $this->applyCustomerSorting($this->buildCustomersQuery($workspace))
                    ->paginate($this->customerPerPage, ['*'], 'customersPage');
            }

            if ($needsRates) {
                $rates = $this->applyRateSorting($this->buildRateQuery($workspace))
                    ->paginate($this->ratePerPage, ['*'], 'ratesPage');
            }

            if ($needsQuotes) {
                $quotes = $this->applyQuoteSorting($this->buildQuoteQuery($workspace))
                    ->paginate($this->quotePerPage, ['*'], 'quotesPage');
            }

            if ($needsShipments) {
                $shipments = $this->applyShipmentSorting($this->buildShipmentQuery($workspace))
                    ->paginate($this->shipmentPerPage, ['*'], 'shipmentsPage');
            }

            if ($needsProjects) {
                $projects = $this->applyProjectSorting($this->buildProjectQuery($workspace))
                    ->paginate($this->projectPerPage, ['*'], 'projectsPage');
            }

            if ($needsDrawings) {
                $drawings = $this->applyDrawingSorting($this->buildDrawingQuery($workspace))
                    ->paginate($this->drawingPerPage, ['*'], 'drawingsPage');
            }

            if ($needsDelivery) {
                $deliveryMilestones = $this->applyDeliverySorting($this->buildDeliveryQuery($workspace))
                    ->paginate($this->deliveryPerPage, ['*'], 'deliveryPage');
            }

            if ($needsCarriers) {
                $carriers = $this->applyCarrierSorting($this->buildCarrierQuery($workspace))
                    ->paginate($this->carrierPerPage, ['*'], 'carriersPage');
            }

            if ($needsBookings) {
                $bookings = $this->applyBookingSorting($this->buildBookingQuery($workspace))
                    ->paginate($this->bookingPerPage, ['*'], 'bookingsPage');
            }

            if ($needsCostings) {
                $costings = $this->applyCostingSorting($this->buildCostingQuery($workspace))
                    ->paginate($this->costingPerPage, ['*'], 'costingsPage');
            }

            if ($needsInvoices) {
                $invoices = $this->applyInvoiceSorting($this->buildInvoiceQuery($workspace))
                    ->paginate($this->invoicePerPage, ['*'], 'invoicesPage');
            }

            if ($needsSources) {
                $sheetSources = $workspace->sheetSources()->latest()->get();
                $hasSheetSources = $sheetSources->isNotEmpty();
                $sheetSourceStats = [
                    'total' => $sheetSources->count(),
                    'active' => $sheetSources->where('is_active', true)->count(),
                    'synced' => $sheetSources->where('sync_status', 'synced')->count(),
                    'failed' => $sheetSources->where('sync_status', 'failed')->count(),
                ];
            } else {
                $hasSheetSources = $workspace->sheetSources()->exists();
            }

            if ($needsWorkspaceUsers) {
                $workspaceUsers = $workspace->users()->with(['roles.permissions', 'userPermissions'])->orderBy('name')->get();
            }

            $unreadWorkspaceNotificationCount = WorkspaceNotification::query()
                ->where('workspace_id', $workspace->id)
                ->where('user_id', auth()->id())
                ->where('is_read', false)
                ->count();

            if ($this->showNotifications) {
                $workspaceNotifications = WorkspaceNotification::query()
                    ->with('actor')
                    ->where('workspace_id', $workspace->id)
                    ->where('user_id', auth()->id())
                    ->latest()
                    ->limit(8)
                    ->get();
            }

            if ($needsAnalytics) {
                $monthlyReports = $workspace->monthlyReports()->orderByDesc('month_start')->limit(6)->get();
                $latestReport = $monthlyReports->first();
            }

            if ($this->activeTab === 'manual-opportunity') {
                $leadOptions = Lead::query()
                    ->where('workspace_id', $workspace->id)
                    ->orderByDesc('submission_date')
                    ->orderByDesc('created_at')
                    ->limit(100)
                    ->get(['id', 'company_name', 'lead_id', 'external_key']);
            }

            if ($this->activeTab === 'manual-quote') {
                $quoteCustomerOptions = $this->quoteCustomerOptions($workspace);
                $quoteOpportunityOptions = $this->quoteOpportunityOptions(
                    $workspace,
                    $this->selectedManualQuoteCustomer($workspace),
                );
                $rateCardOptions = $this->quoteRateCardOptions(
                    $workspace,
                    $this->selectedManualQuoteCustomer($workspace),
                );
            }

            if ($this->activeTab === 'manual-shipment') {
                $shipmentCustomerOptions = $this->shipmentCustomerOptions($workspace);
                $shipmentOpportunityOptions = $this->shipmentOpportunityOptions(
                    $workspace,
                    $this->selectedManualShipmentCustomer($workspace),
                );
                $shipmentQuoteOptions = $this->shipmentQuoteOptions(
                    $workspace,
                    $this->selectedManualShipmentCustomer($workspace),
                    $this->selectedManualShipmentOpportunity($workspace),
                );
            }

            if ($this->activeTab === 'manual-project') {
                $projectCustomerOptions = $this->projectCustomerOptions($workspace);
                $projectOpportunityOptions = $this->projectOpportunityOptions(
                    $workspace,
                    $this->selectedManualProjectCustomer($workspace),
                );
            }

            if ($this->activeTab === 'manual-drawing') {
                $drawingProjectOptions = $this->projectOptions($workspace);
            }

            if ($this->activeTab === 'manual-delivery') {
                $deliveryProjectOptions = $this->projectOptions($workspace);
            }

            if (in_array($this->activeTab, ['manual-rate', 'manual-booking'], true) || $this->selectedBookingId !== null || $this->selectedRateId !== null) {
                $carrierOptions = Carrier::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'mode']);
            }

            if ($this->activeTab === 'manual-booking') {
                $bookingShipmentOptions = ShipmentJob::query()
                    ->where('workspace_id', $workspace->id)
                    ->orderByDesc('estimated_departure_at')
                    ->orderByDesc('created_at')
                    ->get(['id', 'job_number', 'company_name']);
            }

            if ($this->activeTab === 'manual-invoice' || $this->selectedBookingId !== null) {
                $invoiceBookingOptions = Booking::query()
                    ->where('workspace_id', $workspace->id)
                    ->orderByDesc('requested_etd')
                    ->orderByDesc('created_at')
                    ->get(['id', 'booking_number', 'customer_name', 'shipment_job_id']);
            }

            if ($this->activeTab === 'manual-costing') {
                $costingShipmentOptions = $this->costingShipmentOptions($workspace);
            }

            if ($this->activeTab === 'manual-invoice') {
                $invoiceShipmentOptions = $this->invoiceShipmentOptions($workspace);
                $invoiceCostingOptions = $this->invoiceCostingOptions(
                    $workspace,
                    $this->selectedManualInvoiceShipment($workspace),
                );
            }

            if ($needsAccess) {
                $roles = Role::query()->orderByDesc('level')->get();
                $permissions = Permission::query()->orderBy('name')->get();
            }

            $leadSummary = Lead::query()
                ->where('workspace_id', $workspace->id)
                ->selectRaw(
                    'COUNT(*) as total_count, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as qualified_count',
                    [Lead::STATUS_SALES_QUALIFIED],
                )
                ->first();

            $opportunitySummary = Opportunity::query()
                ->where('workspace_id', $workspace->id)
                ->selectRaw(
                    'COUNT(*) as total_count, COALESCE(SUM(CASE WHEN sales_stage = ? THEN revenue_potential ELSE 0 END), 0) as won_revenue',
                    [Opportunity::STAGE_CLOSED_WON],
                )
                ->first();

            $kpis = [
                [
                    'label' => 'Total Leads',
                    'value' => (int) ($leadSummary?->total_count ?? 0),
                    'detail' => 'Across all sources',
                ],
                [
                    'label' => 'Sales Qualified',
                    'value' => (int) ($leadSummary?->qualified_count ?? 0),
                    'detail' => 'Ready for deal work',
                ],
                [
                    'label' => 'Open Opportunities',
                    'value' => (int) ($opportunitySummary?->total_count ?? 0),
                    'detail' => 'Pipeline records',
                ],
                [
                    'label' => 'Closed Won Revenue',
                    'value' => number_format((float) ($opportunitySummary?->won_revenue ?? 0), 0),
                    'detail' => 'AED won',
                ],
            ];

            if ($needsAnalytics) {
                $analyticsLeadBase = $this->applyAnalyticsRange(
                    Lead::query()->where('workspace_id', $workspace->id),
                    'submission_date',
                );
                $analyticsOpportunityBase = $this->applyAnalyticsRange(
                    Opportunity::query()->where('workspace_id', $workspace->id),
                    'submission_date',
                );
                $analyticsReportBase = $this->applyAnalyticsRange(
                    $workspace->monthlyReports(),
                    'month_start',
                );
                $analyticsAvailableMonths = $this->analyticsAvailableMonths($workspace);

                if ($this->analyticsMonth === '') {
                    $this->analyticsMonth = $analyticsAvailableMonths->keys()->first() ?? $this->defaultAnalyticsMonth();
                }

                $analyticsReportRows = (clone $analyticsReportBase)
                    ->orderByDesc('month_start')
                    ->limit(6)
                    ->get()
                    ->sortBy('month_start')
                    ->values();

                $analyticsLeadCount = (clone $analyticsLeadBase)->count();
                $analyticsQualifiedCount = (clone $analyticsLeadBase)
                    ->where('status', Lead::STATUS_SALES_QUALIFIED)
                    ->count();
                $analyticsOpportunityCount = (clone $analyticsOpportunityBase)->count();
                $analyticsPotentialRevenue = (float) (clone $analyticsOpportunityBase)->sum('revenue_potential');
                $analyticsWonRevenue = (float) (clone $analyticsOpportunityBase)
                    ->where('sales_stage', Opportunity::STAGE_CLOSED_WON)
                    ->sum('revenue_potential');
                $analyticsWonCount = (clone $analyticsOpportunityBase)
                    ->where('sales_stage', Opportunity::STAGE_CLOSED_WON)
                    ->count();

                $analyticsKpis = [
                    [
                        'label' => 'Leads In Range',
                        'value' => number_format($analyticsLeadCount),
                        'detail' => $this->analyticsRangeLabel(),
                    ],
                    [
                        'label' => 'Qualified Rate',
                        'value' => $analyticsLeadCount > 0
                            ? number_format(($analyticsQualifiedCount / $analyticsLeadCount) * 100, 1).'%' : '0%',
                        'detail' => number_format($analyticsQualifiedCount).' sales qualified leads',
                    ],
                    [
                        'label' => 'Opportunities',
                        'value' => number_format($analyticsOpportunityCount),
                        'detail' => 'In the selected reporting window',
                    ],
                    [
                        'label' => 'Won Revenue',
                        'value' => 'AED '.number_format($analyticsWonRevenue, 0),
                        'detail' => number_format($analyticsWonCount).' closed-won deals',
                    ],
                ];

                $analyticsBreakdownRows = match ($this->analyticsBreakdown) {
                    'service' => (clone $analyticsLeadBase)
                        ->selectRaw('COALESCE(service, ?) as label, COUNT(*) as total', ['Unknown'])
                        ->groupBy('service')
                        ->orderByDesc('total')
                        ->limit(8)
                        ->get(),
                    'status' => (clone $analyticsLeadBase)
                        ->selectRaw('COALESCE(status, ?) as label, COUNT(*) as total', ['Unknown'])
                        ->groupBy('status')
                        ->orderByDesc('total')
                        ->get(),
                    'stage' => (clone $analyticsOpportunityBase)
                        ->selectRaw('COALESCE(sales_stage, ?) as label, COUNT(*) as total, COALESCE(SUM(revenue_potential), 0) as revenue', ['Unknown'])
                        ->groupBy('sales_stage')
                        ->orderByDesc('total')
                        ->get(),
                    default => (clone $analyticsLeadBase)
                        ->selectRaw('COALESCE(lead_source, ?) as label, COUNT(*) as total', ['Unknown'])
                        ->groupBy('lead_source')
                        ->orderByDesc('total')
                        ->limit(8)
                        ->get(),
                };

                if ($this->analyticsBreakdown === 'status') {
                    $analyticsBreakdownRows = $analyticsBreakdownRows->map(function ($row) use ($workspace) {
                        $row->label = $row->label === 'Unknown'
                            ? $row->label
                            : $this->leadStatusLabel($row->label, $workspace);

                        return $row;
                    });
                }

                if ($this->analyticsBreakdown === 'stage') {
                    $analyticsBreakdownRows = $analyticsBreakdownRows->map(function ($row) use ($workspace) {
                        $row->label = $row->label === 'Unknown'
                            ? $row->label
                            : $this->opportunityStageLabel($row->label, $workspace);

                        return $row;
                    });
                }

                $analyticsMonthlyRows = $analyticsReportRows->sortByDesc('month_start')->values();

                $analyticsSnapshot = [
                    'range_label' => $this->analyticsRangeLabel(),
                    'top_source' => (clone $analyticsLeadBase)
                        ->selectRaw('COALESCE(lead_source, ?) as label, COUNT(*) as total', ['Unknown'])
                        ->groupBy('lead_source')
                        ->orderByDesc('total')
                        ->first(),
                    'top_service' => (clone $analyticsLeadBase)
                        ->selectRaw('COALESCE(service, ?) as label, COUNT(*) as total', ['Unknown'])
                        ->groupBy('service')
                        ->orderByDesc('total')
                        ->first(),
                    'avg_revenue' => (float) (clone $analyticsOpportunityBase)->avg('revenue_potential'),
                ];

                $analyticsSqlChartRows = $analyticsReportRows->map(fn ($report) => [
                    'label' => $report->year_month,
                    'sqls' => (int) $report->total_opportunities_count,
                    'closed_won' => (int) $report->closed_won_count,
                    'won_revenue' => (float) $report->won_revenue_potential,
                ]);

                $analyticsAdsChartRows = $analyticsReportRows->map(function ($report) {
                    $spend = (float) $report->google_ads_cost;
                    $leads = (int) $report->google_ads_leads;
                    $cpl = $leads > 0 ? $spend / $leads : (float) $report->cost_per_conversion;

                    return [
                        'label' => $report->year_month,
                        'spend' => $spend,
                        'leads' => $leads,
                        'cpl' => $cpl,
                    ];
                });

                $analyticsWonCustomers = (clone $analyticsOpportunityBase)
                    ->where('sales_stage', Opportunity::STAGE_CLOSED_WON)
                    ->whereNotNull('company_name')
                    ->orderByDesc('revenue_potential')
                    ->limit(5)
                    ->get(['company_name', 'revenue_potential']);

                $analyticsDealSummary = [
                    'potential_value' => $analyticsPotentialRevenue,
                    'converted_value' => $analyticsWonRevenue,
                    'total_deals' => $analyticsOpportunityCount,
                    'won_leads' => $analyticsWonCount,
                ];

                $adsSpend = (float) $analyticsReportRows->sum('total_ads_cost');
                $adsRevenue = (float) $analyticsReportRows->sum('won_revenue_potential');
                $googleAdsSpend = (float) $analyticsReportRows->sum('google_ads_cost');
                $googleAdsLeads = (int) $analyticsReportRows->sum('google_ads_leads');
                $romi = $adsSpend > 0 ? (($adsRevenue - $adsSpend) / $adsSpend) * 100 : null;
                $roas = $adsSpend > 0 ? $adsRevenue / $adsSpend : null;

                $analyticsEfficiency = [
                    'ads_spend' => $adsSpend,
                    'google_ads_spend' => $googleAdsSpend,
                    'google_ads_leads' => $googleAdsLeads,
                    'revenue' => $adsRevenue,
                    'romi' => $romi,
                    'roas' => $roas,
                    'romi_band' => $this->romiBand($romi),
                ];
            }

            $selectedLead = $this->selectedLeadId
                ? Lead::query()
                    ->with(['assignedUser', 'sheetSource'])
                    ->withCount('opportunities')
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedLeadId)
                : null;

            $selectedOpportunity = $this->selectedOpportunityId
                ? Opportunity::query()
                    ->with(['lead', 'assignedUser', 'sheetSource'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedOpportunityId)
                : null;

            $selectedContact = $this->selectedContactId
                ? Contact::query()
                    ->with([
                        'assignedUser',
                        'account',
                        'leads' => fn ($query) => $query->orderByDesc('submission_date')->orderByDesc('created_at')->limit(6),
                        'opportunities' => fn ($query) => $query->orderByDesc('submission_date')->orderByDesc('created_at')->limit(6),
                        'quotes' => fn ($query) => $query->orderByDesc('quoted_at')->orderByDesc('created_at')->limit(6),
                        'shipmentJobs' => fn ($query) => $query->orderByDesc('estimated_departure_at')->orderByDesc('created_at')->limit(6),
                        'projects' => fn ($query) => $query->orderByDesc('target_delivery_date')->orderByDesc('created_at')->limit(6),
                        'bookings' => fn ($query) => $query->orderByDesc('requested_etd')->orderByDesc('created_at')->limit(6),
                        'invoices' => fn ($query) => $query->orderByDesc('issue_date')->orderByDesc('created_at')->limit(6),
                    ])
                    ->withCount(['leads', 'opportunities', 'quotes', 'shipmentJobs', 'projects', 'bookings', 'invoices'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedContactId)
                : null;

            $selectedCustomer = $this->selectedCustomerId
                ? Account::query()
                    ->with([
                        'assignedUser',
                        'contacts' => fn ($query) => $query->orderByDesc('last_activity_at')->orderBy('full_name')->limit(8),
                        'leads' => fn ($query) => $query->orderByDesc('submission_date')->orderByDesc('created_at')->limit(6),
                        'opportunities' => fn ($query) => $query->orderByDesc('submission_date')->orderByDesc('created_at')->limit(6),
                        'quotes' => fn ($query) => $query->orderByDesc('quoted_at')->orderByDesc('created_at')->limit(6),
                        'shipmentJobs' => fn ($query) => $query->orderByDesc('estimated_departure_at')->orderByDesc('created_at')->limit(6),
                        'projects' => fn ($query) => $query->orderByDesc('target_delivery_date')->orderByDesc('created_at')->limit(6),
                        'bookings' => fn ($query) => $query->orderByDesc('requested_etd')->orderByDesc('created_at')->limit(6),
                        'invoices' => fn ($query) => $query->orderByDesc('issue_date')->orderByDesc('created_at')->limit(6),
                        'segmentAssignments.segmentDefinition',
                        'currentMetricSnapshot',
                    ])
                    ->withCount(['contacts', 'leads', 'opportunities', 'quotes', 'shipmentJobs', 'projects', 'bookings', 'invoices'])
                    ->withSum('opportunities as opportunity_revenue_sum', 'revenue_potential')
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedCustomerId)
                : null;

            $selectedRate = $this->selectedRateId
                ? RateCard::query()
                    ->with(['carrier', 'assignedUser'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedRateId)
                : null;

            $selectedQuote = $this->selectedQuoteId
                ? Quote::query()
                    ->with(['lead', 'opportunity', 'assignedUser', 'rateCard'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedQuoteId)
                : null;

            $selectedShipment = $this->selectedShipmentId
                ? ShipmentJob::query()
                    ->with(['lead', 'opportunity', 'quote', 'assignedUser', 'milestones', 'documents', 'bookings.carrier', 'jobCostings', 'invoices'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedShipmentId)
                : null;
            $selectedProject = $this->selectedProjectId
                ? Project::query()
                    ->with([
                        'assignedUser',
                        'account.contacts',
                        'contact',
                        'lead',
                        'opportunity',
                        'drawings' => fn ($query) => $query->orderByDesc('submitted_at')->orderByDesc('id')->limit(10),
                        'deliveryMilestones' => fn ($query) => $query->orderBy('sequence')->orderBy('id')->limit(12),
                    ])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedProjectId)
                : null;
            $selectedDrawing = $this->selectedDrawingId
                ? ProjectDrawing::query()
                    ->with(['project', 'assignedUser'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedDrawingId)
                : null;
            $selectedDelivery = $this->selectedDeliveryId
                ? ProjectDeliveryMilestone::query()
                    ->with(['project', 'assignedUser'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedDeliveryId)
                : null;
            $selectedCarrier = $this->selectedCarrierId
                ? Carrier::query()
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedCarrierId)
                : null;
            $selectedBooking = $this->selectedBookingId
                ? Booking::query()
                    ->with(['carrier', 'shipmentJob', 'quote', 'assignedUser'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedBookingId)
                : null;
            $selectedCosting = $this->selectedCostingId
                ? JobCosting::query()
                    ->with(['lines', 'shipmentJob', 'quote', 'assignedUser'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedCostingId)
                : null;
            $selectedInvoice = $this->selectedInvoiceId
                ? Invoice::query()
                    ->with(['lines', 'shipmentJob', 'booking', 'jobCosting.lines', 'quote', 'assignedUser', 'postedByUser'])
                    ->where('workspace_id', $workspace->id)
                    ->find($this->selectedInvoiceId)
                : null;

            $selectedBookingCollaboration = $selectedBooking
                ? $this->collaborationEntriesFor($selectedBooking)
                : collect();
            $selectedCostingCollaboration = $selectedCosting
                ? $this->collaborationEntriesFor($selectedCosting)
                : collect();
            $selectedInvoiceCollaboration = $selectedInvoice
                ? $this->collaborationEntriesFor($selectedInvoice)
                : collect();
            $selectedContactCollaboration = $selectedContact
                ? $this->collaborationEntriesFor($selectedContact)
                : collect();
            $selectedCustomerCollaboration = $selectedCustomer
                ? $this->collaborationEntriesFor($selectedCustomer)
                : collect();

            $selectedBookingInvoices = $selectedBooking
                ? Invoice::query()
                    ->with(['shipmentJob'])
                    ->where('workspace_id', $workspace->id)
                    ->where(function ($query) use ($selectedBooking) {
                        $query->where('booking_id', $selectedBooking->id);

                        if ($selectedBooking->shipment_job_id) {
                            $query->orWhere('shipment_job_id', $selectedBooking->shipment_job_id);
                        }
                    })
                    ->orderByDesc('issue_date')
                    ->orderByDesc('created_at')
                    ->get()
                : collect();

            $enrichment = app(WorkspaceEnrichmentService::class);

            $leadInsights = $selectedLead
                ? $enrichment->contactInsights($selectedLead)
                : [];
            $selectedLeadCollaboration = $selectedLead
                ? $this->collaborationEntriesFor($selectedLead)
                : collect();

            $opportunityInsights = $selectedOpportunity && $selectedOpportunity->lead
                ? $enrichment->customerInsights($selectedOpportunity)
                : [];
            $selectedOpportunityCollaboration = $selectedOpportunity
                ? $this->collaborationEntriesFor($selectedOpportunity)
                : collect();

            $contactInsights = $selectedContact
                ? $enrichment->contactInsights($selectedContact)
                : [];

            $customerInsights = $selectedCustomer
                ? $enrichment->customerInsights($selectedCustomer)
                : [];

            if ($selectedShipment) {
                $this->ensureShipmentExecutionDefaults($selectedShipment);
                $selectedShipment = $selectedShipment->fresh(['lead', 'opportunity', 'quote', 'assignedUser', 'milestones', 'documents', 'bookings.carrier', 'jobCostings', 'invoices']);
            }

            if ($selectedProject) {
                $this->ensureProjectExecutionDefaults($selectedProject);
                $selectedProject = $selectedProject->fresh(['assignedUser', 'account.contacts', 'contact', 'lead', 'opportunity', 'drawings', 'deliveryMilestones']);
            }

            $selectedQuoteCollaboration = $selectedQuote
                ? $this->collaborationEntriesFor($selectedQuote)
                : collect();
            $selectedShipmentCollaboration = $selectedShipment
                ? $this->collaborationEntriesFor($selectedShipment)
                : collect();
            $selectedProjectCollaboration = $selectedProject
                ? $this->collaborationEntriesFor($selectedProject)
                : collect();

            $selectedShipmentTimeline = $selectedShipment
                ? $this->shipmentTimelineRows($selectedShipment)
                : collect();
            $selectedProjectTimeline = $selectedProject
                ? $this->projectTimelineRows($selectedProject)
                : collect();
        }

        return view('livewire.crm-dashboard', [
            'analyticsAdsChartRows' => $analyticsAdsChartRows,
            'analyticsAvailableMonths' => $analyticsAvailableMonths,
            'analyticsBreakdownRows' => $analyticsBreakdownRows,
            'analyticsDealSummary' => $analyticsDealSummary,
            'analyticsEfficiency' => $analyticsEfficiency,
            'analyticsKpis' => $analyticsKpis,
            'analyticsMonthlyRows' => $analyticsMonthlyRows,
            'analyticsSnapshot' => $analyticsSnapshot,
            'analyticsSqlChartRows' => $analyticsSqlChartRows,
            'analyticsWonCustomers' => $analyticsWonCustomers,
            'availableSourceTypes' => SheetSource::availableTypesForWorkspace($workspace),
            'bookingShipmentOptions' => $bookingShipmentOptions,
            'bookingStatusOptions' => Booking::STATUSES,
            'bookings' => $bookings,
            'canManageAccess' => $canManageAccess,
            'canViewSources' => $canViewSources,
            'canViewWorkspaceTools' => $canViewWorkspaceTools,
            'carrierModeOptions' => Carrier::MODES,
            'carrierOptions' => $carrierOptions,
            'carrierStatusOptions' => [true => 'Active', false => 'Inactive'],
            'carriers' => $carriers,
            'leadInsights' => $leadInsights,
            'opportunityInsights' => $opportunityInsights,
            'contactInsights' => $contactInsights,
            'contacts' => $contacts,
            'companies' => $companies,
            'costingShipmentOptions' => $costingShipmentOptions,
            'costingStatusOptions' => JobCosting::STATUSES,
            'costings' => $costings,
            'currentWorkspace' => $workspace,
            'currentWorkspaceTemplateDescription' => $workspace?->templateDescription() ?? data_get(Workspace::templateDefinitionFor(Workspace::defaultTemplateKey()), 'description', ''),
            'currentWorkspaceTemplateModules' => $workspace?->templateModules() ?? Workspace::defaultTemplateModules(),
            'currentWorkspaceTemplateName' => $workspace?->templateName() ?? data_get(Workspace::templateDefinitionFor(Workspace::defaultTemplateKey()), 'name', 'General Maritime'),
            'currentWorkspaceExtraModules' => $workspace
                ? collect($workspace->templateModules())
                    ->reject(fn (string $module) => array_key_exists($module, $this->coreTabDefinitions()))
                    ->reject(fn (string $module) => in_array($module, ['sources', 'access', 'settings', 'exports'], true))
                    ->values()
                : collect(),
            'customerInsights' => $customerInsights,
            'customers' => $customers,
            'disqualificationReasons' => $workspace ? $this->disqualificationReasonOptions($workspace) : Workspace::defaultDisqualificationReasons(),
            'kpis' => $kpis,
            'latestReport' => $latestReport,
            'leads' => $leads,
            'leadServices' => $workspace ? $this->leadServiceOptions($workspace) : Workspace::defaultLeadServices(),
            'leadSources' => $workspace ? $this->leadSourceOptions($workspace) : Workspace::defaultLeadSources(),
            'leadStatusOptions' => $workspace ? $this->leadStatusOptions($workspace) : Workspace::defaultLeadStatusLabels(),
            'leadOptions' => $leadOptions,
            'monthlyReports' => $monthlyReports,
            'invoiceCostingOptions' => $invoiceCostingOptions,
            'invoiceBookingOptions' => $invoiceBookingOptions,
            'invoiceShipmentOptions' => $invoiceShipmentOptions,
            'invoiceStatusOptions' => Invoice::STATUSES,
            'invoiceTypeOptions' => Invoice::TYPES,
            'invoices' => $invoices,
            'drawingProjectOptions' => $drawingProjectOptions,
            'drawingStatusOptions' => ProjectDrawing::STATUSES,
            'drawings' => $drawings,
            'hasSheetSources' => $hasSheetSources,
            'opportunities' => $opportunities,
            'opportunityStageOptions' => $workspace ? $this->opportunityStageOptions($workspace) : Workspace::defaultOpportunityStageLabels(),
            'permissions' => $permissions,
            'opportunityOptions' => $opportunityOptions ?? collect(),
            'projectCustomerOptions' => $projectCustomerOptions,
            'projectOpportunityOptions' => $projectOpportunityOptions,
            'projectStatusOptions' => Project::STATUSES,
            'projects' => $projects,
            'rateCardOptions' => $rateCardOptions,
            'rateModeOptions' => RateCard::MODES,
            'rates' => $rates,
            'quotes' => $quotes,
            'quoteOptions' => $quoteOptions,
            'quoteCustomerOptions' => $quoteCustomerOptions,
            'quoteOpportunityOptions' => $quoteOpportunityOptions,
            'quoteStatusOptions' => Quote::STATUSES,
            'roles' => $roles,
            'selectedLead' => $selectedLead,
            'selectedLeadCollaboration' => $selectedLeadCollaboration,
            'selectedOpportunity' => $selectedOpportunity,
            'selectedOpportunityCollaboration' => $selectedOpportunityCollaboration,
            'selectedRate' => $selectedRate,
            'selectedQuote' => $selectedQuote,
            'selectedQuoteCollaboration' => $selectedQuoteCollaboration,
            'selectedShipment' => $selectedShipment,
            'selectedShipmentCollaboration' => $selectedShipmentCollaboration,
            'selectedShipmentTimeline' => $selectedShipmentTimeline,
            'selectedProject' => $selectedProject,
            'selectedProjectCollaboration' => $selectedProjectCollaboration,
            'selectedProjectTimeline' => $selectedProjectTimeline,
            'selectedDrawing' => $selectedDrawing,
            'selectedDelivery' => $selectedDelivery,
            'selectedContact' => $selectedContact,
            'selectedContactCollaboration' => $selectedContactCollaboration,
            'selectedCustomer' => $selectedCustomer,
            'selectedCustomerCollaboration' => $selectedCustomerCollaboration,
            'selectedCarrier' => $selectedCarrier,
            'selectedBooking' => $selectedBooking,
            'selectedBookingInvoices' => $selectedBookingInvoices,
            'selectedBookingCollaboration' => $selectedBookingCollaboration,
            'selectedCosting' => $selectedCosting,
            'selectedCostingCollaboration' => $selectedCostingCollaboration,
            'selectedInvoice' => $selectedInvoice,
            'selectedInvoiceCollaboration' => $selectedInvoiceCollaboration,
            'sheetSourceStats' => $sheetSourceStats,
            'sheetSources' => $sheetSources,
            'shipmentCustomerOptions' => $shipmentCustomerOptions,
            'shipmentOpportunityOptions' => $shipmentOpportunityOptions,
            'shipmentQuoteOptions' => $shipmentQuoteOptions,
            'shipmentStatusOptions' => ShipmentJob::STATUSES,
            'shipmentMilestoneStatusOptions' => ShipmentMilestone::STATUSES,
            'shipmentDocumentTypeOptions' => ShipmentDocument::TYPES,
            'shipmentDocumentStatusOptions' => ShipmentDocument::STATUSES,
            'shipments' => $shipments,
            'deliveryMilestones' => $deliveryMilestones,
            'deliveryProjectOptions' => $deliveryProjectOptions,
            'deliveryStatusOptions' => ProjectDeliveryMilestone::STATUSES,
            'segmentColorOptions' => $segmentColorOptions,
            'segmentDefinitions' => $segmentDefinitions,
            'segmentMetricCatalog' => $segmentMetricCatalog,
            'segmentOperatorCatalog' => $segmentOperatorCatalog,
            'templateModuleMeta' => $this->templateModuleMeta(),
            'tabs' => $workspace
                ? $this->availableTabsForWorkspace($workspace, $canManageAccess, $canViewWorkspaceTools, $canViewSources)
                : $this->coreTabDefinitions(),
            'workspaceNotifications' => $workspaceNotifications,
            'unreadWorkspaceNotificationCount' => $unreadWorkspaceNotificationCount,
            'workspaceUsers' => $workspaceUsers,
            'workspaces' => $workspaces,
            'workspaceTemplates' => Workspace::workspaceTemplates(),
        ]);
    }

    protected function resetForms(): void
    {
        $this->companyForm = [
            'name' => '',
            'industry' => 'Maritime',
            'contact_email' => '',
            'contact_phone' => '',
            'timezone' => 'Asia/Dubai',
        ];

        $this->workspaceForm = [
            'company_id' => '',
            'name' => '',
            'description' => '',
            'template_key' => Workspace::defaultTemplateKey(),
        ];

        $this->workspaceSettingsForm = $this->defaultWorkspaceSettingsForm();
        $this->notificationSettingsForm = WorkspaceMembership::defaultNotificationPreferences();

        $this->sourceForm = [
            'workspace_id' => '',
            'type' => SheetSource::TYPE_LEADS,
            'name' => '',
            'url' => '',
            'description' => '',
            'source_kind' => SheetSource::SOURCE_KIND_GOOGLE_SHEET_CSV,
            'is_active' => true,
            ...$this->defaultSourceConnectionFields(),
        ];

        $this->editingSourceForm = [];

        $this->userForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'job_title' => '',
            'role' => 'sales',
            'permission_ids' => [],
        ];

        $this->roleForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'level' => 3,
            'permission_ids' => [],
        ];

        $this->permissionForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'model' => 'User',
        ];

        $this->editingWorkspaceUserForm = [];

        $this->manualLeadForm = [
            'contact_name' => '',
            'company_name' => '',
            'email' => '',
            'phone' => '',
            'service' => 'Container Conversion',
            'lead_source' => 'Email',
            'status' => Lead::STATUS_IN_PROGRESS,
            'lead_value' => '',
            'notes' => '',
        ];

        $this->manualOpportunityForm = [
            'lead_id' => '',
            'company_name' => '',
            'contact_email' => '',
            'lead_source' => 'Email',
            'required_service' => 'Container Conversion',
            'revenue_potential' => '',
            'project_timeline_days' => '',
            'sales_stage' => Opportunity::STAGE_INITIAL_CONTACT,
            'notes' => '',
        ];

        $this->manualQuoteForm = [
            'customer_record_id' => '',
            'opportunity_id' => '',
            'lead_id' => '',
            'company_name' => '',
            'contact_name' => '',
            'contact_email' => '',
            'service_mode' => 'Ocean Freight',
            'origin' => '',
            'destination' => '',
            'incoterm' => '',
            'commodity' => '',
            'equipment_type' => '',
            'weight_kg' => '',
            'volume_cbm' => '',
            'buy_amount' => '',
            'sell_amount' => '',
            'currency' => 'AED',
            'status' => Quote::STATUS_DRAFT,
            'valid_until' => '',
            'notes' => '',
        ];

        $this->manualShipmentForm = [
            'customer_record_id' => '',
            'opportunity_id' => '',
            'quote_id' => '',
            'lead_id' => '',
            'company_name' => '',
            'contact_name' => '',
            'contact_email' => '',
            'service_mode' => 'Ocean Freight',
            'origin' => '',
            'destination' => '',
            'incoterm' => '',
            'commodity' => '',
            'equipment_type' => '',
            'container_count' => '',
            'weight_kg' => '',
            'volume_cbm' => '',
            'carrier_name' => '',
            'vessel_name' => '',
            'voyage_number' => '',
            'house_bill_no' => '',
            'master_bill_no' => '',
            'estimated_departure_at' => '',
            'estimated_arrival_at' => '',
            'actual_departure_at' => '',
            'actual_arrival_at' => '',
            'buy_amount' => '',
            'sell_amount' => '',
            'currency' => 'AED',
            'status' => ShipmentJob::STATUS_DRAFT,
            'notes' => '',
        ];

        $this->shipmentMilestoneForm = [
            'label' => '',
            'planned_at' => '',
            'status' => ShipmentMilestone::STATUS_PENDING,
            'notes' => '',
        ];

        $this->shipmentDocumentForm = [
            'document_type' => ShipmentDocument::TYPE_OTHER,
            'document_name' => '',
            'reference_number' => '',
            'external_url' => '',
            'status' => ShipmentDocument::STATUS_RECEIVED,
            'uploaded_at' => now()->format('Y-m-d\TH:i'),
            'notes' => '',
        ];

        $this->collaborationForms = [
            'lead' => $this->defaultCollaborationForm(),
            'opportunity' => $this->defaultCollaborationForm(),
            'quote' => $this->defaultCollaborationForm(),
            'shipment' => $this->defaultCollaborationForm(),
            'project' => $this->defaultCollaborationForm(),
            'booking' => $this->defaultCollaborationForm(),
            'costing' => $this->defaultCollaborationForm(),
            'invoice' => $this->defaultCollaborationForm(),
            'contact' => $this->defaultCollaborationForm(),
            'customer' => $this->defaultCollaborationForm(),
        ];
    }

    protected function primeForms(?Workspace $workspace): void
    {
        if (! $workspace) {
            $this->workspaceSettingsForm = $this->defaultWorkspaceSettingsForm();
            $this->notificationSettingsForm = WorkspaceMembership::defaultNotificationPreferences();

            return;
        }

        $this->workspaceForm['company_id'] = $workspace->company_id;
        $this->workspaceForm['template_key'] = $workspace->templateKey();
        $this->sourceForm['workspace_id'] = $workspace->id;
        $this->workspaceSettingsForm = $this->workspaceSettingsFormFor($workspace);
        $this->notificationSettingsForm = $this->notificationSettingsFormFor($workspace);
    }

    protected function accessibleWorkspaces(): EloquentCollection
    {
        return auth()->user()->isAdmin()
            ? Workspace::query()->with('company')->orderBy('name')->get()
            : auth()->user()->workspaces()->with('company')->orderBy('name')->get();
    }

    protected function visibleCompanies(EloquentCollection $workspaces): EloquentCollection
    {
        if (auth()->user()->isAdmin()) {
            return Company::query()->orderBy('name')->get();
        }

        $companyIds = $workspaces->pluck('company_id')->unique()->values();

        return Company::query()->whereIn('id', $companyIds)->orderBy('name')->get();
    }

    protected function currentWorkspace(): ?Workspace
    {
        return $this->resolveCurrentWorkspace($this->accessibleWorkspaces());
    }

    protected function currentWorkspaceOrFail(): Workspace
    {
        $workspace = $this->currentWorkspace();

        abort_if(! $workspace, 404);

        return $workspace;
    }

    protected function currentWorkspaceMembership(?Workspace $workspace = null): ?WorkspaceMembership
    {
        $workspace ??= $this->currentWorkspace();

        if (! $workspace) {
            return null;
        }

        return WorkspaceMembership::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', auth()->id())
            ->first();
    }

    protected function resolveCurrentWorkspace(EloquentCollection $workspaces): ?Workspace
    {
        if ($workspaces->isEmpty()) {
            return null;
        }

        $workspace = $workspaces->firstWhere('id', (int) $this->workspaceId)
            ?? $workspaces->firstWhere('id', (int) auth()->user()->default_workspace_id)
            ?? $workspaces->first();

        return $workspace;
    }

    protected function ensureWorkspaceVisible(int $workspaceId): void
    {
        abort_unless($this->accessibleWorkspaces()->pluck('id')->contains($workspaceId), 403);
    }

    protected function ensureAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    protected function ensureWorkspaceOwner(): void
    {
        $workspace = $this->currentWorkspaceOrFail();

        abort_unless(auth()->user()->ownsWorkspace($workspace->id), 403);
    }

    protected function ensureWorkspaceManager(): void
    {
        abort_unless(auth()->user()->hasRole(['admin', 'manager']), 403);
    }

    protected function canBootstrapWorkspace(): bool
    {
        return $this->accessibleWorkspaces()->isEmpty();
    }

    protected function bootstrapWorkspaceOwner(Workspace $workspace): void
    {
        $user = auth()->user();

        $managerRole = Role::query()->firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Workspace manager', 'level' => 6],
        );

        if (! $user->hasRole('manager') && ! $user->hasRole('admin')) {
            $user->attachRole($managerRole);
        }

        $user->forceFill([
            'company_id' => $workspace->company_id,
            'default_workspace_id' => $workspace->id,
            'is_active' => $user->is_active ?? true,
        ])->save();

        $workspace->users()->syncWithoutDetaching([
            $user->id => [
                'job_title' => $user->job_title ?: 'Workspace owner',
                'is_owner' => true,
            ],
        ]);
    }

    protected function canManageWorkspaceAccess(?Workspace $workspace = null): bool
    {
        $workspace ??= $this->currentWorkspace();

        if (! $workspace) {
            return false;
        }

        return auth()->user()->ownsWorkspace($workspace->id);
    }

    protected function buildLeadQuery(Workspace $workspace)
    {
        $query = Lead::query()
            ->with(['assignedUser'])
            ->withCount('opportunities')
            ->where('workspace_id', $workspace->id);

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('contact_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('lead_id', 'like', "%{$search}%");
            });
        }

        if ($this->leadStatusFilter !== '') {
            $query->where('status', $this->leadStatusFilter);
        }

        if ($this->leadSourceFilter !== '') {
            $query->where('lead_source', $this->leadSourceFilter);
        }

        return $query;
    }

    protected function quoteCustomerOptions(Workspace $workspace)
    {
        return Account::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('name')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'primary_email', 'latest_service']);
    }

    protected function quoteOpportunityOptions(Workspace $workspace, ?Account $customer = null)
    {
        $query = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('submission_date')
            ->orderByDesc('created_at');

        if ($customer) {
            $query->where('account_id', $customer->id);
        }

        return $query->get(['id', 'company_name', 'contact_email', 'external_key', 'lead_id']);
    }

    protected function quoteRateCardOptions(Workspace $workspace, ?Account $customer = null)
    {
        $query = RateCard::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderByDesc('valid_until')
            ->orderByDesc('created_at');

        if ($customer && filled($customer->name)) {
            $query->where(function ($builder) use ($customer) {
                $builder->where('customer_name', $customer->name)
                    ->orWhereNull('customer_name');
            });
        }

        return $query->get(['id', 'rate_code', 'customer_name', 'service_mode', 'origin', 'destination', 'currency', 'sell_amount']);
    }

    protected function buildRateQuery(Workspace $workspace)
    {
        $query = RateCard::query()
            ->with(['carrier', 'assignedUser'])
            ->where('workspace_id', $workspace->id);

        $search = trim($this->rateSearch);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('rate_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhere('commodity', 'like', "%{$search}%");
            });
        }

        if ($this->rateModeFilter !== '') {
            $query->where('service_mode', $this->rateModeFilter);
        }

        return $query;
    }

    protected function buildOpportunityQuery(Workspace $workspace)
    {
        $query = Opportunity::query()
            ->with(['assignedUser', 'lead'])
            ->where('workspace_id', $workspace->id);

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('external_key', 'like', "%{$search}%");
            });
        }

        if ($this->opportunityStageFilter !== '') {
            $query->where('sales_stage', $this->opportunityStageFilter);
        }

        return $query;
    }

    protected function buildQuoteQuery(Workspace $workspace)
    {
        $query = Quote::query()
            ->with(['assignedUser', 'lead', 'opportunity', 'rateCard'])
            ->where('workspace_id', $workspace->id);

        $search = trim($this->quoteSearch);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('quote_number', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        if ($this->quoteStatusFilter !== '') {
            $query->where('status', $this->quoteStatusFilter);
        }

        return $query;
    }

    protected function shipmentCustomerOptions(Workspace $workspace)
    {
        return Account::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('name')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'primary_email', 'latest_service']);
    }

    protected function selectedManualQuoteCustomer(Workspace $workspace): ?Account
    {
        $customerId = (int) data_get($this->manualQuoteForm, 'customer_record_id');

        if ($customerId < 1) {
            return null;
        }

        $account = Account::query()
            ->where('workspace_id', $workspace->id)
            ->find($customerId);

        if ($account) {
            return $account;
        }

        $opportunity = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->find($customerId);

        return $opportunity?->account;
    }

    protected function shipmentOpportunityOptions(Workspace $workspace, ?Account $customer = null)
    {
        $query = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('submission_date')
            ->orderByDesc('created_at');

        if ($customer) {
            $query->where('account_id', $customer->id);
        }

        return $query->get(['id', 'company_name', 'contact_email', 'external_key', 'lead_id']);
    }

    protected function shipmentQuoteOptions(Workspace $workspace, ?Account $customer = null, ?Opportunity $opportunity = null)
    {
        $query = Quote::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('quoted_at')
            ->orderByDesc('created_at');

        if ($opportunity) {
            $query->where('opportunity_id', $opportunity->id);
        } elseif ($customer) {
            $query->where('account_id', $customer->id);
        }

        return $query->get(['id', 'quote_number', 'company_name', 'opportunity_id']);
    }

    protected function costingShipmentOptions(Workspace $workspace)
    {
        return ShipmentJob::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('estimated_departure_at')
            ->orderByDesc('created_at')
            ->get(['id', 'job_number', 'company_name']);
    }

    protected function invoiceShipmentOptions(Workspace $workspace)
    {
        return ShipmentJob::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('estimated_departure_at')
            ->orderByDesc('created_at')
            ->get(['id', 'job_number', 'company_name']);
    }

    protected function invoiceCostingOptions(Workspace $workspace, ?ShipmentJob $shipment = null)
    {
        $query = JobCosting::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('created_at');

        if ($shipment) {
            $query->where('shipment_job_id', $shipment->id);
        }

        return $query->get(['id', 'costing_number', 'customer_name', 'shipment_job_id']);
    }

    protected function selectedManualShipmentCustomer(Workspace $workspace): ?Account
    {
        $customerId = (int) data_get($this->manualShipmentForm, 'customer_record_id');

        if ($customerId < 1) {
            return null;
        }

        $account = Account::query()
            ->where('workspace_id', $workspace->id)
            ->find($customerId);

        if ($account) {
            return $account;
        }

        $opportunity = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->find($customerId);

        return $opportunity?->account;
    }

    protected function selectedManualInvoiceShipment(Workspace $workspace): ?ShipmentJob
    {
        $shipmentId = (int) data_get($this->manualInvoiceForm, 'shipment_job_id');

        if ($shipmentId < 1) {
            return null;
        }

        return ShipmentJob::query()
            ->where('workspace_id', $workspace->id)
            ->find($shipmentId);
    }

    protected function selectedManualShipmentOpportunity(Workspace $workspace): ?Opportunity
    {
        $opportunityId = (int) data_get($this->manualShipmentForm, 'opportunity_id');

        if ($opportunityId < 1) {
            return null;
        }

        return Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->find($opportunityId);
    }

    protected function selectedManualProjectCustomer(Workspace $workspace): ?Account
    {
        $customerId = (int) data_get($this->manualProjectForm, 'customer_record_id');

        if ($customerId < 1) {
            return null;
        }

        return Account::query()
            ->where('workspace_id', $workspace->id)
            ->find($customerId);
    }

    protected function selectedManualProjectOpportunity(Workspace $workspace): ?Opportunity
    {
        $opportunityId = (int) data_get($this->manualProjectForm, 'opportunity_id');

        if ($opportunityId < 1) {
            return null;
        }

        return Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->find($opportunityId);
    }

    protected function buildShipmentQuery(Workspace $workspace)
    {
        $query = ShipmentJob::query()
            ->with(['assignedUser', 'lead', 'opportunity', 'quote'])
            ->where('workspace_id', $workspace->id);

        $search = trim($this->shipmentSearch);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('job_number', 'like', "%{$search}%")
                    ->orWhere('external_reference', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhere('carrier_name', 'like', "%{$search}%")
                    ->orWhere('vessel_name', 'like', "%{$search}%")
                    ->orWhere('house_bill_no', 'like', "%{$search}%")
                    ->orWhere('master_bill_no', 'like', "%{$search}%");
            });
        }

        if ($this->shipmentStatusFilter !== '') {
            $query->where('status', $this->shipmentStatusFilter);
        }

        return $query;
    }

    protected function buildProjectQuery(Workspace $workspace)
    {
        $query = Project::query()
            ->with(['assignedUser', 'account', 'contact', 'opportunity'])
            ->withCount(['drawings', 'deliveryMilestones'])
            ->where('workspace_id', $workspace->id);

        $search = trim($this->projectSearch);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('project_number', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('site_location', 'like', "%{$search}%")
                    ->orWhere('container_type', 'like', "%{$search}%");
            });
        }

        if ($this->projectStatusFilter !== '') {
            $query->where('status', $this->projectStatusFilter);
        }

        return $query;
    }

    protected function buildDrawingQuery(Workspace $workspace)
    {
        $query = ProjectDrawing::query()
            ->with(['project', 'assignedUser'])
            ->where('workspace_id', $workspace->id);

        $search = trim($this->drawingSearch);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('revision_number', 'like', "%{$search}%")
                    ->orWhere('drawing_title', 'like', "%{$search}%")
                    ->orWhereHas('project', fn ($projectQuery) => $projectQuery
                        ->where('project_number', 'like', "%{$search}%")
                        ->orWhere('project_name', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%"));
            });
        }

        if ($this->drawingStatusFilter !== '') {
            $query->where('status', $this->drawingStatusFilter);
        }

        return $query;
    }

    protected function buildDeliveryQuery(Workspace $workspace)
    {
        $query = ProjectDeliveryMilestone::query()
            ->with(['project', 'assignedUser'])
            ->where('workspace_id', $workspace->id);

        $search = trim($this->deliverySearch);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('milestone_label', 'like', "%{$search}%")
                    ->orWhere('site_location', 'like', "%{$search}%")
                    ->orWhereHas('project', fn ($projectQuery) => $projectQuery
                        ->where('project_number', 'like', "%{$search}%")
                        ->orWhere('project_name', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%"));
            });
        }

        if ($this->deliveryStatusFilter !== '') {
            $query->where('status', $this->deliveryStatusFilter);
        }

        return $query;
    }

    protected function buildCarrierQuery(Workspace $workspace)
    {
        $query = Carrier::query()
            ->withCount('bookings')
            ->where('workspace_id', $workspace->id);

        $search = trim($this->carrierSearch);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('scac_code', 'like', "%{$search}%")
                    ->orWhere('iata_code', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('service_lanes', 'like', "%{$search}%");
            });
        }

        if ($this->carrierModeFilter !== '') {
            $query->where('mode', $this->carrierModeFilter);
        }

        return $query;
    }

    protected function buildBookingQuery(Workspace $workspace)
    {
        $query = Booking::query()
            ->with(['carrier', 'shipmentJob', 'quote'])
            ->where('workspace_id', $workspace->id);

        $search = trim($this->bookingSearch);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('booking_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhere('carrier_confirmation_ref', 'like', "%{$search}%");
            });
        }

        if ($this->bookingStatusFilter !== '') {
            $query->where('status', $this->bookingStatusFilter);
        }

        return $query;
    }

    protected function buildCostingQuery(Workspace $workspace)
    {
        $query = JobCosting::query()
            ->with(['shipmentJob', 'quote'])
            ->withCount('lines')
            ->where('workspace_id', $workspace->id);

        $search = trim($this->costingSearch);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('costing_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('service_mode', 'like', "%{$search}%");
            });
        }

        if ($this->costingStatusFilter !== '') {
            $query->where('status', $this->costingStatusFilter);
        }

        return $query;
    }

    protected function buildInvoiceQuery(Workspace $workspace)
    {
        $query = Invoice::query()
            ->with(['shipmentJob', 'booking', 'jobCosting', 'quote'])
            ->withCount('lines')
            ->where('workspace_id', $workspace->id);

        $search = trim($this->invoiceSearch);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('bill_to_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        if ($this->invoiceStatusFilter !== '') {
            $query->where('status', $this->invoiceStatusFilter);
        }

        if ($this->invoiceBookingFilter !== '') {
            $selectedBooking = Booking::query()
                ->where('workspace_id', $workspace->id)
                ->find((int) $this->invoiceBookingFilter);

            if ($selectedBooking) {
                $query->where(function ($builder) use ($selectedBooking) {
                    $builder->where('booking_id', $selectedBooking->id);

                    if ($selectedBooking->shipment_job_id) {
                        $builder->orWhere('shipment_job_id', $selectedBooking->shipment_job_id);
                    }
                });
            }
        }

        return $query;
    }

    protected function buildContactsQuery(Workspace $workspace)
    {
        $query = Contact::query()
            ->with('account')
            ->withCount(['leads', 'opportunities', 'quotes', 'shipmentJobs', 'projects', 'bookings', 'invoices'])
            ->where('workspace_id', $workspace->id)
            ->whereDoesntHave('opportunities');

        $contactSearch = trim($this->contactSearch);

        if ($contactSearch !== '') {
            $query->where(function ($builder) use ($contactSearch) {
                $builder->where('full_name', 'like', "%{$contactSearch}%")
                    ->orWhere('email', 'like', "%{$contactSearch}%")
                    ->orWhere('phone', 'like', "%{$contactSearch}%")
                    ->orWhereHas('account', fn ($accountQuery) => $accountQuery->where('name', 'like', "%{$contactSearch}%"));
            });
        }

        return $query;
    }

    protected function buildCustomersQuery(Workspace $workspace)
    {
        $query = Account::query()
            ->with([
                'segmentAssignments.segmentDefinition',
            ])
            ->withCount(['contacts', 'leads', 'opportunities', 'quotes', 'shipmentJobs', 'projects', 'bookings', 'invoices'])
            ->withMin('contacts as primary_contact_name', 'full_name')
            ->withSum('opportunities as opportunity_revenue_sum', 'revenue_potential')
            ->where('workspace_id', $workspace->id)
            ->whereHas('opportunities');

        $customerSearch = trim($this->customerSearch);

        if ($customerSearch !== '') {
            $query->where(function ($builder) use ($customerSearch) {
                $builder->where('name', 'like', "%{$customerSearch}%")
                    ->orWhere('primary_email', 'like', "%{$customerSearch}%")
                    ->orWhere('latest_service', 'like', "%{$customerSearch}%")
                    ->orWhereHas('contacts', fn ($contactQuery) => $contactQuery
                        ->where('full_name', 'like', "%{$customerSearch}%")
                        ->orWhere('email', 'like', "%{$customerSearch}%"));
            });
        }

        if ($this->customerSegmentFilter !== '') {
            $segmentId = (int) $this->customerSegmentFilter;

            $query->whereHas('segmentAssignments', fn ($assignmentQuery) => $assignmentQuery
                ->where('segment_definition_id', $segmentId));
        }

        return $query;
    }

    protected function projectCustomerOptions(Workspace $workspace)
    {
        return Account::query()
            ->where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get(['id', 'name', 'primary_email']);
    }

    protected function projectOpportunityOptions(Workspace $workspace, ?Account $customer = null)
    {
        $query = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('submission_date')
            ->orderByDesc('created_at');

        if ($customer) {
            $query->where('account_id', $customer->id);
        }

        return $query->get(['id', 'account_id', 'company_name', 'external_key', 'sales_stage']);
    }

    protected function projectOptions(Workspace $workspace)
    {
        return Project::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('target_delivery_date')
            ->orderByDesc('created_at')
            ->get(['id', 'project_number', 'project_name', 'customer_name']);
    }

    protected function workspaceExportFilename(Workspace $workspace, string $type): string
    {
        return Str::slug($workspace->name).'-'.$type.'-'.now()->format('Ymd-His').'.csv';
    }

    protected function streamCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function defaultWorkspaceSettingsForm(): array
    {
        $templateKey = Workspace::defaultTemplateKey();

        return [
            'template_key' => $templateKey,
            'lead_status_labels' => Workspace::defaultLeadStatusLabels($templateKey),
            'opportunity_stage_labels' => Workspace::defaultOpportunityStageLabels($templateKey),
            'disqualification_reasons' => Workspace::defaultDisqualificationReasons($templateKey),
            'lead_sources' => Workspace::defaultLeadSources($templateKey),
            'lead_services' => Workspace::defaultLeadServices($templateKey),
            'segment_definitions' => $this->defaultSegmentDefinitionsForTemplate($templateKey),
        ];
    }

    protected function workspaceSettingsFormFor(Workspace $workspace): array
    {
        $segmentation = app(CustomerSegmentationService::class);
        $segmentation->ensureDefaultSegments($workspace);
        $segmentDefinitions = $workspace->segmentDefinitions()->with('rules')->orderByDesc('priority')->orderBy('id')->get();

        return [
            'template_key' => $workspace->templateKey(),
            'lead_status_labels' => $workspace->leadStatusLabels(),
            'opportunity_stage_labels' => $workspace->opportunityStageLabels(),
            'disqualification_reasons' => $workspace->disqualificationReasons(),
            'lead_sources' => $workspace->leadSourcesCatalog(),
            'lead_services' => $workspace->leadServicesCatalog(),
            'segment_definitions' => $segmentDefinitions
                ->map(fn (CustomerSegmentDefinition $segmentDefinition) => $this->segmentDefinitionFormData($segmentDefinition))
                ->all(),
        ];
    }

    protected function notificationSettingsFormFor(Workspace $workspace): array
    {
        return $this->currentWorkspaceMembership($workspace)?->notificationPreferences()
            ?? WorkspaceMembership::defaultNotificationPreferences();
    }

    protected function defaultSegmentDefinitionsForTemplate(string $templateKey): array
    {
        return collect(CustomerSegmentationService::presetDefinitionsForTemplate($templateKey))
            ->map(function (array $definition): array {
                return [
                    'id' => null,
                    'name' => (string) ($definition['name'] ?? 'New Segment'),
                    'description' => (string) ($definition['description'] ?? ''),
                    'color' => (string) ($definition['color'] ?? CustomerSegmentDefinition::COLOR_SKY),
                    'priority' => (int) ($definition['priority'] ?? 0),
                    'is_active' => true,
                    'rules' => collect($definition['rules'] ?? [])
                        ->map(fn (array $rule) => [
                            'metric_key' => (string) ($rule['metric_key'] ?? 'inquiries_90d'),
                            'operator' => (string) ($rule['operator'] ?? CustomerSegmentRule::OPERATOR_GREATER_THAN_OR_EQUAL),
                            'threshold_value' => (string) ($rule['threshold_value'] ?? '1'),
                        ])
                        ->values()
                        ->all() ?: [$this->emptySegmentRuleForm()],
                ];
            })
            ->values()
            ->all();
    }

    protected function segmentDefinitionFormData(CustomerSegmentDefinition $segmentDefinition): array
    {
        return [
            'id' => $segmentDefinition->id,
            'name' => $segmentDefinition->name,
            'description' => $segmentDefinition->description ?? '',
            'color' => $segmentDefinition->color,
            'priority' => $segmentDefinition->priority,
            'is_active' => $segmentDefinition->is_active,
            'rules' => $segmentDefinition->rules
                ->map(fn (CustomerSegmentRule $rule) => [
                    'metric_key' => $rule->metric_key,
                    'operator' => $rule->operator,
                    'threshold_value' => (string) $rule->threshold_value,
                ])
                ->values()
                ->all() ?: [$this->emptySegmentRuleForm()],
        ];
    }

    protected function emptySegmentDefinitionForm(): array
    {
        return [
            'id' => null,
            'name' => '',
            'description' => '',
            'color' => CustomerSegmentDefinition::COLOR_SKY,
            'priority' => 50,
            'is_active' => true,
            'rules' => [$this->emptySegmentRuleForm()],
        ];
    }

    protected function emptySegmentRuleForm(): array
    {
        return [
            'metric_key' => 'inquiries_90d',
            'operator' => CustomerSegmentRule::OPERATOR_GREATER_THAN_OR_EQUAL,
            'threshold_value' => '1',
        ];
    }

    protected function normalizeSegmentDefinitions(array $definitions): array
    {
        $usedSlugs = [];

        return collect($definitions)
            ->map(function (array $definition) use (&$usedSlugs): array {
                $baseSlug = Str::slug((string) ($definition['name'] ?? 'segment'));
                $baseSlug = $baseSlug !== '' ? $baseSlug : 'segment';
                $slug = $baseSlug;
                $suffix = 2;

                while (in_array($slug, $usedSlugs, true)) {
                    $slug = $baseSlug.'-'.$suffix;
                    $suffix++;
                }

                $usedSlugs[] = $slug;

                return [
                    'name' => trim((string) $definition['name']),
                    'slug' => $slug,
                    'description' => trim((string) ($definition['description'] ?? '')) ?: null,
                    'color' => in_array($definition['color'] ?? '', CustomerSegmentDefinition::COLORS, true)
                        ? $definition['color']
                        : CustomerSegmentDefinition::COLOR_SKY,
                    'priority' => max(0, (int) ($definition['priority'] ?? 0)),
                    'is_active' => (bool) ($definition['is_active'] ?? false),
                    'rules' => collect($definition['rules'] ?? [])
                        ->map(fn (array $rule) => [
                            'metric_key' => (string) $rule['metric_key'],
                            'operator' => (string) $rule['operator'],
                            'threshold_value' => (float) $rule['threshold_value'],
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function sanitizeList(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => is_string($value) ? trim($value) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function sanitizeLabelMap(array $values, array $allowedKeys): array
    {
        return collect($values)
            ->only($allowedKeys)
            ->map(fn ($value) => is_string($value) ? trim($value) : '')
            ->filter()
            ->all();
    }

    public function leadStatusOptions(?Workspace $workspace = null): array
    {
        return ($workspace ?? $this->currentWorkspace())?->leadStatusLabels()
            ?? Workspace::defaultLeadStatusLabels(Workspace::defaultTemplateKey());
    }

    public function opportunityStageOptions(?Workspace $workspace = null): array
    {
        return ($workspace ?? $this->currentWorkspace())?->opportunityStageLabels()
            ?? Workspace::defaultOpportunityStageLabels(Workspace::defaultTemplateKey());
    }

    public function segmentBadgeClasses(?string $color): string
    {
        return match ($color) {
            CustomerSegmentDefinition::COLOR_EMERALD => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            CustomerSegmentDefinition::COLOR_AMBER => 'bg-amber-100 text-amber-800 ring-amber-200',
            CustomerSegmentDefinition::COLOR_ROSE => 'bg-rose-100 text-rose-800 ring-rose-200',
            CustomerSegmentDefinition::COLOR_VIOLET => 'bg-violet-100 text-violet-800 ring-violet-200',
            default => 'bg-sky-100 text-sky-800 ring-sky-200',
        };
    }

    public function disqualificationReasonOptions(?Workspace $workspace = null): array
    {
        return ($workspace ?? $this->currentWorkspace())?->disqualificationReasons()
            ?? Workspace::defaultDisqualificationReasons(Workspace::defaultTemplateKey());
    }

    public function leadSourceOptions(?Workspace $workspace = null): array
    {
        $workspace ??= $this->currentWorkspace();

        $catalog = $workspace?->leadSourcesCatalog() ?? Workspace::defaultLeadSources(Workspace::defaultTemplateKey());
        $existing = $workspace
            ? Lead::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('lead_source')
                ->pluck('lead_source')
                ->all()
            : [];

        return collect($catalog)
            ->merge($existing)
            ->map(fn ($value) => is_string($value) ? trim($value) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function leadServiceOptions(?Workspace $workspace = null): array
    {
        $workspace ??= $this->currentWorkspace();

        $catalog = $workspace?->leadServicesCatalog() ?? Workspace::defaultLeadServices(Workspace::defaultTemplateKey());
        $existingLeads = $workspace
            ? Lead::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('service')
                ->pluck('service')
                ->all()
            : [];
        $existingOpportunities = $workspace
            ? Opportunity::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('required_service')
                ->pluck('required_service')
                ->all()
            : [];

        return collect($catalog)
            ->merge($existingLeads)
            ->merge($existingOpportunities)
            ->map(fn ($value) => is_string($value) ? trim($value) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function leadStatusLabel(string $status, ?Workspace $workspace = null): string
    {
        return $this->leadStatusOptions($workspace)[$status] ?? $status;
    }

    public function opportunityStageLabel(string $stage, ?Workspace $workspace = null): string
    {
        return $this->opportunityStageOptions($workspace)[$stage] ?? $stage;
    }

    protected function uniqueSlug(string $modelClass, string $value, array $extraWhere = []): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $iteration = 2;

        while ($modelClass::query()->where($extraWhere)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$iteration}";
            $iteration++;
        }

        return $slug;
    }

    protected function applyLeadSorting($query)
    {
        return match ($this->leadSort) {
            'oldest' => $query->orderBy('submission_date')->orderBy('created_at'),
            'company_asc' => $query->orderBy('company_name')->orderByDesc('submission_date'),
            'company_desc' => $query->orderByDesc('company_name')->orderByDesc('submission_date'),
            'value_desc' => $query->orderByDesc('lead_value')->orderByDesc('submission_date'),
            'value_asc' => $query->orderBy('lead_value')->orderByDesc('submission_date'),
            default => $query->orderByDesc('submission_date')->orderByDesc('created_at'),
        };
    }

    protected function applyOpportunitySorting($query)
    {
        return match ($this->opportunitySort) {
            'oldest' => $query->orderBy('submission_date')->orderBy('created_at'),
            'company_asc' => $query->orderBy('company_name')->orderByDesc('submission_date'),
            'company_desc' => $query->orderByDesc('company_name')->orderByDesc('submission_date'),
            'revenue_desc' => $query->orderByDesc('revenue_potential')->orderByDesc('submission_date'),
            'revenue_asc' => $query->orderBy('revenue_potential')->orderByDesc('submission_date'),
            default => $query->orderByDesc('submission_date')->orderByDesc('created_at'),
        };
    }

    protected function applyContactSorting($query)
    {
        return match ($this->contactSort) {
            'oldest' => $query->orderBy('last_activity_at')->orderBy('created_at'),
            'name_asc' => $query->orderBy('full_name')->orderByDesc('last_activity_at'),
            'name_desc' => $query->orderByDesc('full_name')->orderByDesc('last_activity_at'),
            'company_asc' => $query->orderBy(
                Account::query()->select('name')->whereColumn('accounts.id', 'contacts.account_id')->limit(1)
            )->orderByDesc('last_activity_at'),
            'company_desc' => $query->orderByDesc(
                Account::query()->select('name')->whereColumn('accounts.id', 'contacts.account_id')->limit(1)
            )->orderByDesc('last_activity_at'),
            default => $query->orderByDesc('last_activity_at')->orderByDesc('created_at'),
        };
    }

    protected function applyCustomerSorting($query)
    {
        return match ($this->customerSort) {
            'oldest' => $query->orderBy('last_activity_at')->orderBy('created_at'),
            'company_asc' => $query->orderBy('name')->orderByDesc('last_activity_at'),
            'company_desc' => $query->orderByDesc('name')->orderByDesc('last_activity_at'),
            'value_desc' => $query->orderByDesc('opportunity_revenue_sum')->orderByDesc('last_activity_at'),
            'value_asc' => $query->orderBy('opportunity_revenue_sum')->orderByDesc('last_activity_at'),
            default => $query->orderByDesc('last_activity_at')->orderByDesc('created_at'),
        };
    }

    protected function applyRateSorting($query)
    {
        return match ($this->rateSort) {
            'oldest' => $query->orderBy('created_at'),
            'customer_asc' => $query->orderBy('customer_name')->orderByDesc('created_at'),
            'customer_desc' => $query->orderByDesc('customer_name')->orderByDesc('created_at'),
            'sell_desc' => $query->orderByDesc('sell_amount')->orderByDesc('created_at'),
            'sell_asc' => $query->orderBy('sell_amount')->orderByDesc('created_at'),
            'expiry_asc' => $query->orderBy('valid_until')->orderByDesc('created_at'),
            default => $query->orderByDesc('valid_until')->orderByDesc('created_at'),
        };
    }

    protected function applyQuoteSorting($query)
    {
        return match ($this->quoteSort) {
            'oldest' => $query->orderBy('quoted_at')->orderBy('created_at'),
            'company_asc' => $query->orderBy('company_name')->orderByDesc('quoted_at'),
            'company_desc' => $query->orderByDesc('company_name')->orderByDesc('quoted_at'),
            'sell_desc' => $query->orderByDesc('sell_amount')->orderByDesc('quoted_at'),
            'sell_asc' => $query->orderBy('sell_amount')->orderByDesc('quoted_at'),
            default => $query->orderByDesc('quoted_at')->orderByDesc('created_at'),
        };
    }

    protected function applyShipmentSorting($query)
    {
        return match ($this->shipmentSort) {
            'oldest' => $query->orderBy('estimated_departure_at')->orderBy('created_at'),
            'company_asc' => $query->orderBy('company_name')->orderByDesc('estimated_departure_at'),
            'company_desc' => $query->orderByDesc('company_name')->orderByDesc('estimated_departure_at'),
            'eta_desc' => $query->orderByDesc('estimated_arrival_at')->orderByDesc('created_at'),
            'eta_asc' => $query->orderBy('estimated_arrival_at')->orderByDesc('created_at'),
            default => $query->orderByDesc('estimated_departure_at')->orderByDesc('created_at'),
        };
    }

    protected function applyProjectSorting($query)
    {
        return match ($this->projectSort) {
            'oldest' => $query->orderBy('target_delivery_date')->orderBy('created_at'),
            'customer_asc' => $query->orderBy('customer_name')->orderByDesc('target_delivery_date'),
            'customer_desc' => $query->orderByDesc('customer_name')->orderByDesc('target_delivery_date'),
            'value_desc' => $query->orderByDesc('estimated_value')->orderByDesc('target_delivery_date'),
            'value_asc' => $query->orderBy('estimated_value')->orderByDesc('target_delivery_date'),
            default => $query->orderByDesc('target_delivery_date')->orderByDesc('created_at'),
        };
    }

    protected function applyDrawingSorting($query)
    {
        return match ($this->drawingSort) {
            'oldest' => $query->orderBy('submitted_at')->orderBy('created_at'),
            'project_asc' => $query->orderBy(
                Project::query()->select('project_name')->whereColumn('projects.id', 'project_drawings.project_id')->limit(1)
            )->orderByDesc('submitted_at'),
            'project_desc' => $query->orderByDesc(
                Project::query()->select('project_name')->whereColumn('projects.id', 'project_drawings.project_id')->limit(1)
            )->orderByDesc('submitted_at'),
            default => $query->orderByDesc('submitted_at')->orderByDesc('created_at'),
        };
    }

    protected function applyDeliverySorting($query)
    {
        return match ($this->deliverySort) {
            'oldest' => $query->orderBy('planned_date')->orderBy('sequence')->orderBy('created_at'),
            'project_asc' => $query->orderBy(
                Project::query()->select('project_name')->whereColumn('projects.id', 'project_delivery_milestones.project_id')->limit(1)
            )->orderBy('sequence'),
            'project_desc' => $query->orderByDesc(
                Project::query()->select('project_name')->whereColumn('projects.id', 'project_delivery_milestones.project_id')->limit(1)
            )->orderBy('sequence'),
            default => $query->orderByDesc('planned_date')->orderBy('sequence')->orderByDesc('created_at'),
        };
    }

    protected function applyCarrierSorting($query)
    {
        return match ($this->carrierSort) {
            'name_desc' => $query->orderByDesc('name'),
            'mode_asc' => $query->orderBy('mode')->orderBy('name'),
            'bookings_desc' => $query->orderByDesc('bookings_count')->orderBy('name'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderBy('name'),
        };
    }

    protected function applyBookingSorting($query)
    {
        return match ($this->bookingSort) {
            'oldest' => $query->orderBy('requested_etd')->orderBy('created_at'),
            'customer_asc' => $query->orderBy('customer_name')->orderByDesc('requested_etd'),
            'customer_desc' => $query->orderByDesc('customer_name')->orderByDesc('requested_etd'),
            'status_asc' => $query->orderBy('status')->orderByDesc('requested_etd'),
            default => $query->orderByDesc('requested_etd')->orderByDesc('created_at'),
        };
    }

    protected function applyCostingSorting($query)
    {
        return match ($this->costingSort) {
            'oldest' => $query->orderBy('created_at'),
            'customer_asc' => $query->orderBy('customer_name')->orderByDesc('created_at'),
            'customer_desc' => $query->orderByDesc('customer_name')->orderByDesc('created_at'),
            'margin_desc' => $query->orderByDesc('margin_amount')->orderByDesc('created_at'),
            'margin_asc' => $query->orderBy('margin_amount')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }

    protected function applyInvoiceSorting($query)
    {
        return match ($this->invoiceSort) {
            'oldest' => $query->orderBy('issue_date')->orderBy('created_at'),
            'customer_asc' => $query->orderBy('bill_to_name')->orderByDesc('issue_date'),
            'customer_desc' => $query->orderByDesc('bill_to_name')->orderByDesc('issue_date'),
            'amount_desc' => $query->orderByDesc('total_amount')->orderByDesc('issue_date'),
            'amount_asc' => $query->orderBy('total_amount')->orderByDesc('issue_date'),
            default => $query->orderByDesc('issue_date')->orderByDesc('created_at'),
        };
    }

    protected function applyAnalyticsRange($query, string $column)
    {
        [$start, $end] = $this->analyticsBounds();

        if (! $start || ! $end) {
            return $query;
        }

        return $query
            ->whereDate($column, '>=', $start->toDateString())
            ->whereDate($column, '<=', $end->toDateString());
    }

    protected function analyticsRangeLabel(): string
    {
        return match ($this->analyticsRange) {
            'last_month' => 'Last month',
            '30' => 'Last 30 days',
            '60' => 'Last 60 days',
            '90' => 'Last 90 days',
            'month' => $this->analyticsMonthLabel(),
            default => 'All time',
        };
    }

    protected function analyticsAvailableMonths(Workspace $workspace)
    {
        $reportMonths = $workspace->monthlyReports()
            ->whereNotNull('month_start')
            ->orderByDesc('month_start')
            ->pluck('month_start')
            ->map(fn ($date) => Carbon::parse($date)->startOfMonth());

        $leadMonths = Lead::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('submission_date')
            ->pluck('submission_date')
            ->map(fn ($date) => Carbon::parse($date)->startOfMonth());

        $opportunityMonths = Opportunity::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('submission_date')
            ->pluck('submission_date')
            ->map(fn ($date) => Carbon::parse($date)->startOfMonth());

        return $reportMonths
            ->merge($leadMonths)
            ->merge($opportunityMonths)
            ->unique(fn ($date) => $date->format('Y-m'))
            ->sortByDesc(fn ($date) => $date->format('Y-m'))
            ->mapWithKeys(fn ($date) => [$date->format('Y-m') => $date->format('F Y')]);
    }

    protected function analyticsBounds(): array
    {
        return match ($this->analyticsRange) {
            'last_month' => [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ],
            '30' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
            '60' => [now()->subDays(60)->startOfDay(), now()->endOfDay()],
            '90' => [now()->subDays(90)->startOfDay(), now()->endOfDay()],
            'month' => $this->monthBounds($this->analyticsMonth),
            default => [null, null],
        };
    }

    protected function monthBounds(string $month): array
    {
        if ($month === '') {
            return [null, null];
        }

        $parsed = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return [$parsed->copy()->startOfMonth(), $parsed->copy()->endOfMonth()];
    }

    protected function analyticsMonthLabel(): string
    {
        if ($this->analyticsMonth === '') {
            return 'Specific month';
        }

        return Carbon::createFromFormat('Y-m', $this->analyticsMonth)->format('F Y');
    }

    protected function defaultAnalyticsMonth(): string
    {
        return now()->subMonthNoOverflow()->format('Y-m');
    }

    protected function romiBand(?float $romi): array
    {
        if ($romi === null) {
            return [
                'label' => 'No spend data',
                'classes' => 'bg-zinc-100 text-zinc-600',
            ];
        }

        return match (true) {
            $romi < 100 => [
                'label' => '0% - 100%: Low ROMI',
                'classes' => 'bg-rose-100 text-rose-700',
            ],
            $romi < 300 => [
                'label' => '100% - 300%: Moderate ROMI',
                'classes' => 'bg-amber-100 text-amber-700',
            ],
            $romi < 500 => [
                'label' => '300% - 500%: High ROMI',
                'classes' => 'bg-emerald-100 text-emerald-700',
            ],
            default => [
                'label' => '500% and above: Excellent ROMI',
                'classes' => 'bg-sky-100 text-sky-700',
            ],
        };
    }

    protected function friendlySyncError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        return match (true) {
            str_contains($message, 'Google OAuth client ID and secret must be saved first.') => 'This Google Sheets source cannot sync yet. An admin must first save the Google OAuth client ID and secret in Admin > Data Sources.',
            str_contains($message, 'Google is not connected for this company.') => 'This Google Sheets source cannot sync yet. An admin must connect the company Google account in Admin > Data Sources first.',
            str_contains($message, 'Google access expired and no refresh token is available.') => 'Google access has expired for this company. An admin needs to reconnect Google in Admin > Data Sources.',
            str_contains($message, 'CargoWise') => Str::limit($message, 220),
            default => Str::limit($message, 220),
        };
    }

    protected function defaultSourceConnectionFields(): array
    {
        return [
            'cargo_auth_mode' => 'basic',
            'cargo_username' => '',
            'cargo_password' => '',
            'cargo_token' => '',
            'cargo_format' => 'json',
            'cargo_data_path' => '',
        ];
    }

    protected function sourceConnectionFieldsFromSource(SheetSource $source): array
    {
        return [
            'cargo_auth_mode' => data_get($source->mapping, 'cargowise.auth_mode', 'basic'),
            'cargo_username' => data_get($source->mapping, 'cargowise.username', ''),
            'cargo_password' => data_get($source->mapping, 'cargowise.password', ''),
            'cargo_token' => data_get($source->mapping, 'cargowise.token', ''),
            'cargo_format' => data_get($source->mapping, 'cargowise.format', 'json'),
            'cargo_data_path' => data_get($source->mapping, 'cargowise.data_path', ''),
        ];
    }

    protected function sourceMappingFromForm(array $validated, ?SheetSource $source = null): ?array
    {
        $mapping = $source?->mapping ?? [];

        if (($validated['source_kind'] ?? null) !== SheetSource::SOURCE_KIND_CARGOWISE_API) {
            unset($mapping['cargowise']);

            return $mapping === [] ? null : $mapping;
        }

        $mapping['cargowise'] = [
            'endpoint' => $validated['url'],
            'auth_mode' => $validated['cargo_auth_mode'] ?: 'basic',
            'username' => $validated['cargo_username'] ?: '',
            'password' => $validated['cargo_password'] ?: '',
            'token' => $validated['cargo_token'] ?: '',
            'format' => $validated['cargo_format'] ?: 'json',
            'data_path' => $validated['cargo_data_path'] ?: '',
        ];

        return $mapping;
    }

    public function leadStatusClasses(string $status): string
    {
        return match ($status) {
            Lead::STATUS_SALES_QUALIFIED => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            Lead::STATUS_DISQUALIFIED => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }

    public function displayedLeadStatus(Lead $lead): string
    {
        if ($this->pendingDisqualificationLeadId === $lead->id) {
            return Lead::STATUS_DISQUALIFIED;
        }

        return $lead->status;
    }

    public function showsDisqualificationReasonSelector(Lead $lead): bool
    {
        return $this->pendingDisqualificationLeadId === $lead->id
            || $lead->status === Lead::STATUS_DISQUALIFIED;
    }

    public function leadScore(Lead $lead): array
    {
        $cacheKey = (string) ($lead->id ?: $lead->external_key ?: spl_object_id($lead));

        return $this->leadScoreCache[$cacheKey] ??= app(LeadScoringService::class)->score($lead);
    }

    public function leadScoreClasses(int $score): string
    {
        return match (true) {
            $score >= 80 => 'bg-emerald-100 text-emerald-800',
            $score >= 60 => 'bg-sky-100 text-sky-800',
            $score >= 40 => 'bg-amber-100 text-amber-800',
            default => 'bg-zinc-100 text-zinc-700',
        };
    }

    public function opportunityStageClasses(string $stage): string
    {
        return match ($stage) {
            Opportunity::STAGE_CLOSED_WON => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            Opportunity::STAGE_CLOSED_LOST, Opportunity::STAGE_NO_RESPONSE => 'border-rose-200 bg-rose-50 text-rose-700',
            Opportunity::STAGE_PROPOSAL_SENT, Opportunity::STAGE_DRAWINGS_SUBMITTED, Opportunity::STAGE_DECISION_MAKING => 'border-sky-200 bg-sky-50 text-sky-800',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }

    public function quoteStatusClasses(string $status): string
    {
        return match ($status) {
            Quote::STATUS_ACCEPTED => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            Quote::STATUS_DECLINED, Quote::STATUS_EXPIRED => 'border-rose-200 bg-rose-50 text-rose-700',
            Quote::STATUS_SENT => 'border-sky-200 bg-sky-50 text-sky-800',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }

    public function shipmentStatusClasses(string $status): string
    {
        return match ($status) {
            ShipmentJob::STATUS_DELIVERED => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            ShipmentJob::STATUS_CANCELLED => 'border-rose-200 bg-rose-50 text-rose-700',
            ShipmentJob::STATUS_BOOKED, ShipmentJob::STATUS_IN_TRANSIT => 'border-sky-200 bg-sky-50 text-sky-800',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }

    public function carrierModeClasses(?string $mode): string
    {
        return match ($mode) {
            Carrier::MODE_OCEAN => 'border-sky-200 bg-sky-50 text-sky-800',
            Carrier::MODE_AIR => 'border-violet-200 bg-violet-50 text-violet-800',
            Carrier::MODE_ROAD => 'border-amber-200 bg-amber-50 text-amber-800',
            Carrier::MODE_RAIL => 'border-zinc-200 bg-zinc-100 text-zinc-700',
            Carrier::MODE_MULTIMODAL => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            default => 'border-zinc-200 bg-zinc-50 text-zinc-700',
        };
    }

    public function bookingStatusClasses(string $status): string
    {
        return match ($status) {
            Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            Booking::STATUS_CANCELLED => 'border-rose-200 bg-rose-50 text-rose-700',
            Booking::STATUS_IN_TRANSIT => 'border-sky-200 bg-sky-50 text-sky-800',
            Booking::STATUS_ROLLED => 'border-orange-200 bg-orange-50 text-orange-700',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }

    public function costingStatusClasses(string $status): string
    {
        return match ($status) {
            JobCosting::STATUS_READY_TO_INVOICE, JobCosting::STATUS_FINALIZED, JobCosting::STATUS_CLOSED => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            JobCosting::STATUS_IN_PROGRESS => 'border-sky-200 bg-sky-50 text-sky-800',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }

    public function invoiceStatusClasses(string $status): string
    {
        return match ($status) {
            Invoice::STATUS_PAID => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            Invoice::STATUS_PARTIALLY_PAID => 'border-sky-200 bg-sky-50 text-sky-800',
            Invoice::STATUS_OVERDUE, Invoice::STATUS_CANCELLED => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }

    public function sourceStatusClasses(?string $status): string
    {
        return match ($status) {
            'synced' => 'bg-emerald-100 text-emerald-800',
            'failed' => 'bg-rose-100 text-rose-700',
            'syncing' => 'bg-amber-100 text-amber-700',
            default => 'bg-zinc-100 text-zinc-600',
        };
    }

    protected function nextQuoteNumber(Workspace $workspace): string
    {
        $nextId = ((int) Quote::query()->where('workspace_id', $workspace->id)->max('id')) + 1;

        return 'QT-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    protected function nextRateCode(Workspace $workspace): string
    {
        $nextId = ((int) RateCard::query()->where('workspace_id', $workspace->id)->max('id')) + 1;

        return 'RT-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    protected function nextShipmentJobNumber(Workspace $workspace): string
    {
        $nextId = ((int) ShipmentJob::query()->where('workspace_id', $workspace->id)->max('id')) + 1;

        return 'SJ-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    protected function nextProjectNumber(Workspace $workspace): string
    {
        $nextId = ((int) Project::query()->where('workspace_id', $workspace->id)->max('id')) + 1;

        return 'PRJ-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    protected function nextBookingNumber(Workspace $workspace): string
    {
        $nextId = ((int) Booking::query()->where('workspace_id', $workspace->id)->max('id')) + 1;

        return 'BK-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    protected function nextCostingNumber(Workspace $workspace): string
    {
        $nextId = ((int) JobCosting::query()->where('workspace_id', $workspace->id)->max('id')) + 1;

        return 'JC-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    protected function nextInvoiceNumber(Workspace $workspace, string $type): string
    {
        $nextId = ((int) Invoice::query()->where('workspace_id', $workspace->id)->max('id')) + 1;
        $prefix = $type === Invoice::TYPE_ACCOUNTS_PAYABLE ? 'AP' : 'AR';

        return $prefix.'-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    protected function applyBookingShipmentConnection(Booking $booking): void
    {
        if (! $booking->shipment_job_id) {
            return;
        }

        $shipment = ShipmentJob::query()
            ->where('workspace_id', $booking->workspace_id)
            ->find($booking->shipment_job_id);

        if (! $shipment) {
            return;
        }

        $booking->loadMissing('carrier');

        $shipment->forceFill([
            'carrier_name' => $booking->carrier?->name ?: $shipment->carrier_name,
            'estimated_departure_at' => $booking->confirmed_etd ?: $booking->requested_etd ?: $shipment->estimated_departure_at,
            'estimated_arrival_at' => $booking->confirmed_eta ?: $booking->requested_eta ?: $shipment->estimated_arrival_at,
            'status' => match ($booking->status) {
                Booking::STATUS_REQUESTED => ShipmentJob::STATUS_BOOKING_REQUESTED,
                Booking::STATUS_CONFIRMED => ShipmentJob::STATUS_BOOKED,
                Booking::STATUS_ROLLED => ShipmentJob::STATUS_BOOKING_REQUESTED,
                Booking::STATUS_IN_TRANSIT => ShipmentJob::STATUS_IN_TRANSIT,
                Booking::STATUS_COMPLETED => ShipmentJob::STATUS_DELIVERED,
                Booking::STATUS_CANCELLED => ShipmentJob::STATUS_CANCELLED,
                default => $shipment->status ?: ShipmentJob::STATUS_DRAFT,
            },
        ])->save();
    }

    protected function quoteMarginFromPayload(array $payload): ?float
    {
        $buy = data_get($payload, 'buy_amount');
        $sell = data_get($payload, 'sell_amount');

        if ($buy === null || $buy === '' || $sell === null || $sell === '') {
            return null;
        }

        return (float) $sell - (float) $buy;
    }

    protected function shipmentMarginFromPayload(array $payload): ?float
    {
        $buy = data_get($payload, 'buy_amount');
        $sell = data_get($payload, 'sell_amount');

        if ($buy === null || $buy === '' || $sell === null || $sell === '') {
            return null;
        }

        return (float) $sell - (float) $buy;
    }

    protected function costingTotalsFromLines(array $lines): array
    {
        $totalCost = 0.0;
        $totalSell = 0.0;

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?: 0);
            $unitAmount = (float) ($line['unit_amount'] ?: 0);
            $lineTotal = $quantity > 0 ? $quantity * $unitAmount : $unitAmount;

            if (($line['line_type'] ?? null) === JobCostingLine::TYPE_REVENUE) {
                $totalSell += $lineTotal;
            } else {
                $totalCost += $lineTotal;
            }
        }

        $margin = $totalSell - $totalCost;

        return [
            'total_cost_amount' => $totalCost,
            'total_sell_amount' => $totalSell,
            'margin_amount' => $margin,
            'margin_percent' => $totalSell > 0 ? round(($margin / $totalSell) * 100, 2) : null,
        ];
    }

    protected function invoiceLinesFromCosting(?JobCosting $costing, string $invoiceType): array
    {
        if (! $costing) {
            return $this->blankInvoiceLines();
        }

        $sourceLines = $costing->lines
            ->filter(function (JobCostingLine $line) use ($invoiceType) {
                if ($invoiceType === Invoice::TYPE_ACCOUNTS_PAYABLE) {
                    return $line->line_type === JobCostingLine::TYPE_COST;
                }

                return $line->line_type === JobCostingLine::TYPE_REVENUE && $line->is_billable;
            })
            ->values();

        if ($sourceLines->isEmpty()) {
            return $this->blankInvoiceLines();
        }

        return $sourceLines->map(fn (JobCostingLine $line) => [
            'job_costing_line_id' => (string) $line->id,
            'charge_code' => $line->charge_code ?: '',
            'description' => $line->description ?: '',
            'quantity' => $line->quantity !== null ? (string) $line->quantity : '1',
            'unit_amount' => $line->unit_amount !== null ? (string) $line->unit_amount : '',
            'notes' => $line->notes ?: '',
        ])->all();
    }

    protected function invoiceLineSubtotal(array $lines): float
    {
        $subtotal = 0.0;

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitAmount = (float) ($line['unit_amount'] ?? 0);
            $lineTotal = $quantity > 0 ? $quantity * $unitAmount : $unitAmount;
            $subtotal += $lineTotal;
        }

        return round($subtotal, 2);
    }

    protected function syncCostingLines(JobCosting $costing, array $lines): void
    {
        $costing->lines()->delete();

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?: 0);
            $unitAmount = (float) ($line['unit_amount'] ?: 0);
            $totalAmount = $quantity > 0 ? $quantity * $unitAmount : $unitAmount;

            $costing->lines()->create([
                'line_type' => $line['line_type'],
                'charge_code' => $line['charge_code'] ?: null,
                'description' => $line['description'],
                'vendor_name' => $line['vendor_name'] ?: null,
                'quantity' => $quantity > 0 ? $quantity : 1,
                'unit_amount' => $unitAmount,
                'total_amount' => $totalAmount,
                'is_billable' => (bool) ($line['is_billable'] ?? true),
                'notes' => $line['notes'] ?: null,
            ]);
        }
    }

    protected function syncInvoiceLines(Invoice $invoice, array $lines): void
    {
        $invoice->lines()->delete();

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitAmount = (float) ($line['unit_amount'] ?? 0);
            $totalAmount = $quantity > 0 ? $quantity * $unitAmount : $unitAmount;

            $invoice->lines()->create([
                'job_costing_line_id' => filled($line['job_costing_line_id'] ?? null) ? $line['job_costing_line_id'] : null,
                'charge_code' => $line['charge_code'] ?: null,
                'description' => $line['description'],
                'quantity' => $quantity > 0 ? $quantity : 1,
                'unit_amount' => $unitAmount,
                'total_amount' => $totalAmount,
                'notes' => $line['notes'] ?: null,
            ]);
        }
    }

    protected function applyInvoiceLineTotalsToForm(string $formKey): void
    {
        $lines = data_get($this->{$formKey}, 'lines', []);
        $subtotal = $this->invoiceLineSubtotal($lines);
        $tax = (float) data_get($this->{$formKey}, 'tax_amount', 0);
        $paid = (float) data_get($this->{$formKey}, 'paid_amount', 0);
        $total = $subtotal + $tax;

        data_set($this->{$formKey}, 'subtotal_amount', $subtotal > 0 ? (string) $subtotal : '');
        data_set($this->{$formKey}, 'total_amount', $total > 0 ? (string) $total : '');
        data_set($this->{$formKey}, 'balance_amount', $total > 0 ? (string) max($total - $paid, 0) : '');
    }

    protected function applyCostingShipmentConnection(JobCosting $costing): void
    {
        if (! $costing->shipment_job_id) {
            return;
        }

        $shipment = ShipmentJob::query()
            ->where('workspace_id', $costing->workspace_id)
            ->find($costing->shipment_job_id);

        if (! $shipment) {
            return;
        }

        $shipment->forceFill([
            'buy_amount' => $costing->total_cost_amount,
            'sell_amount' => $costing->total_sell_amount,
            'margin_amount' => $costing->margin_amount,
            'currency' => $costing->currency ?: $shipment->currency,
        ])->save();
    }

    protected function syncCostingInvoiceState(?JobCosting $costing): void
    {
        if (! $costing) {
            return;
        }

        $costing->loadMissing('invoices');

        $hasPostedInvoice = $costing->invoices->contains(fn (Invoice $invoice) => $invoice->posted_at !== null);
        $hasDraftInvoice = $costing->invoices->contains(fn (Invoice $invoice) => $invoice->status === Invoice::STATUS_DRAFT);

        $targetStatus = match (true) {
            $hasPostedInvoice => JobCosting::STATUS_FINALIZED,
            $hasDraftInvoice => JobCosting::STATUS_READY_TO_INVOICE,
            default => $costing->status,
        };

        if ($targetStatus && $costing->status !== $targetStatus) {
            $costing->forceFill(['status' => $targetStatus])->save();
        }
    }

    protected function flash(string $message): void
    {
        session()->flash('status', $message);
    }

    protected function collaborationRecordFor(string $recordType, int $recordId, Workspace $workspace): Model
    {
        return match ($recordType) {
            'lead' => Lead::query()->where('workspace_id', $workspace->id)->findOrFail($recordId),
            'opportunity' => Opportunity::query()->where('workspace_id', $workspace->id)->findOrFail($recordId),
            'quote' => Quote::query()->where('workspace_id', $workspace->id)->findOrFail($recordId),
            'shipment' => ShipmentJob::query()->where('workspace_id', $workspace->id)->findOrFail($recordId),
            'project' => Project::query()->where('workspace_id', $workspace->id)->findOrFail($recordId),
            'booking' => Booking::query()->where('workspace_id', $workspace->id)->findOrFail($recordId),
            'costing' => JobCosting::query()->where('workspace_id', $workspace->id)->findOrFail($recordId),
            'invoice' => Invoice::query()->where('workspace_id', $workspace->id)->findOrFail($recordId),
            'contact' => Contact::query()->where('workspace_id', $workspace->id)->findOrFail($recordId),
            'customer' => Account::query()->where('workspace_id', $workspace->id)->findOrFail($recordId),
            default => abort(404),
        };
    }

    protected function collaborationRecordLabel(string $recordType): string
    {
        return match ($recordType) {
            'lead' => 'lead',
            'opportunity' => 'opportunity',
            'quote' => 'quote',
            'shipment' => 'shipment',
            'project' => 'project',
            'booking' => 'booking',
            'costing' => 'job costing',
            'invoice' => 'invoice',
            'contact' => 'contact',
            'customer' => 'customer',
            default => 'record',
        };
    }

    protected function collaborationEntriesFor(Model $record): EloquentCollection
    {
        return CollaborationEntry::query()
            ->with(['user', 'recipientUser'])
            ->where('workspace_id', $record->workspace_id)
            ->where('notable_type', $record::class)
            ->where('notable_id', $record->getKey())
            ->latest()
            ->limit(12)
            ->get();
    }

    protected function coreTabDefinitions(): array
    {
        return [
            'leads' => 'Leads',
            'opportunities' => 'Opportunities',
            'contacts' => 'Contacts',
            'customers' => 'Customers',
        ];
    }

    protected function templateModuleMeta(): array
    {
        return [
            'rates' => [
                'label' => 'Rates',
                'description' => 'Maintain lane-based buy and sell rates, transit days, and validity windows for quoting.',
            ],
            'quotes' => [
                'label' => 'Quotes',
                'description' => 'Build freight quotes, compare buy and sell rates, and keep revisions in one place.',
            ],
            'shipments' => [
                'label' => 'Shipments',
                'description' => 'Track booked shipments, milestones, ETD and ETA handoff from the sales pipeline.',
            ],
            'carriers' => [
                'label' => 'Carriers',
                'description' => 'Manage preferred carriers, service lanes, and rate relationships for forwarding teams.',
            ],
            'projects' => [
                'label' => 'Projects',
                'description' => 'Track conversion projects from scope through delivery and installation.',
            ],
            'drawings' => [
                'label' => 'Drawings',
                'description' => 'Collect technical drawings, revisions, and engineering review stages.',
            ],
            'delivery_tracking' => [
                'label' => 'Delivery Tracking',
                'description' => 'Monitor fabrication and final delivery milestones for container projects.',
            ],
            'vessel_calls' => [
                'label' => 'Vessel Calls',
                'description' => 'Manage vessel ETA, ETD, port calls, and requisition-linked customer demand.',
            ],
            'supply_orders' => [
                'label' => 'Supply Orders',
                'description' => 'Handle chandling order capture, requisitions, and delivered supply lists.',
            ],
            'delivery_tasks' => [
                'label' => 'Delivery Tasks',
                'description' => 'Coordinate urgent port deliveries, boarding tasks, and delivery completion.',
            ],
            'bookings' => [
                'label' => 'Bookings',
                'description' => 'Track liner booking requests, confirmations, and customer shipping allocations.',
            ],
            'costings' => [
                'label' => 'Job Costing',
                'description' => 'Track buy, sell, and margin per shipment job.',
            ],
            'invoices' => [
                'label' => 'Invoices',
                'description' => 'Manage AR and AP invoices linked to freight jobs and costings.',
            ],
            'sailings' => [
                'label' => 'Sailings',
                'description' => 'Expose sailings, schedules, and vessel coverage linked to the commercial workflow.',
            ],
            'customer_accounts' => [
                'label' => 'Customer Accounts',
                'description' => 'Manage liner account structures, contract rates, and booking activity.',
            ],
            'fleet' => [
                'label' => 'Fleet',
                'description' => 'Organize managed vessels, owners, and fleet-level service relationships.',
            ],
            'technical_management' => [
                'label' => 'Technical Management',
                'description' => 'Track technical management proposals, reviews, and vessel handover work.',
            ],
            'crewing' => [
                'label' => 'Crewing',
                'description' => 'Manage crewing opportunities, owner needs, and manning-related workflows.',
            ],
            'inventory' => [
                'label' => 'Inventory',
                'description' => 'Track container stock, availability, grades, and unit allocation.',
            ],
            'leasing' => [
                'label' => 'Leasing',
                'description' => 'Handle lease enquiries, term discussions, and contract progression.',
            ],
            'depots' => [
                'label' => 'Depots',
                'description' => 'Monitor depot partners, location coverage, and depot-linked container flows.',
            ],
        ];
    }

    protected function availableTabsForWorkspace(Workspace $workspace, bool $canManageAccess, bool $canViewWorkspaceTools, bool $canViewSources): array
    {
        $tabs = $this->coreTabDefinitions();

        foreach ($workspace->templateModules() as $module) {
            if (array_key_exists($module, $tabs)) {
                continue;
            }

            if (in_array($module, ['sources', 'access', 'settings', 'exports'], true)) {
                continue;
            }

            $tabs[$module] = data_get(
                $this->templateModuleMeta(),
                $module.'.label',
                Str::of($module)->replace('_', ' ')->title()->toString(),
            );
        }

        $tabs['analytics'] = 'Analytics';

        if ($canViewWorkspaceTools) {
            $tabs['settings'] = 'Settings';
        }

        return $tabs;
    }
}
