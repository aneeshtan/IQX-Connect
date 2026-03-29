<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentDocument extends Model
{
    public const TYPE_BOOKING_CONFIRMATION = 'Booking Confirmation';

    public const TYPE_HOUSE_BILL = 'House Bill';

    public const TYPE_MASTER_BILL = 'Master Bill';

    public const TYPE_COMMERCIAL_INVOICE = 'Commercial Invoice';

    public const TYPE_PACKING_LIST = 'Packing List';

    public const TYPE_CUSTOMS = 'Customs Document';

    public const TYPE_DELIVERY_ORDER = 'Delivery Order';

    public const TYPE_OTHER = 'Other';

    public const TYPES = [
        self::TYPE_BOOKING_CONFIRMATION,
        self::TYPE_HOUSE_BILL,
        self::TYPE_MASTER_BILL,
        self::TYPE_COMMERCIAL_INVOICE,
        self::TYPE_PACKING_LIST,
        self::TYPE_CUSTOMS,
        self::TYPE_DELIVERY_ORDER,
        self::TYPE_OTHER,
    ];

    public const STATUS_MISSING = 'Missing';

    public const STATUS_RECEIVED = 'Received';

    public const STATUS_SENT = 'Sent';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_ARCHIVED = 'Archived';

    public const STATUSES = [
        self::STATUS_MISSING,
        self::STATUS_RECEIVED,
        self::STATUS_SENT,
        self::STATUS_APPROVED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'company_id',
        'workspace_id',
        'shipment_job_id',
        'document_type',
        'document_name',
        'reference_number',
        'external_url',
        'status',
        'uploaded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function shipmentJob(): BelongsTo
    {
        return $this->belongsTo(ShipmentJob::class);
    }
}
