<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountMetricSnapshot extends Model
{
    protected $fillable = [
        'company_id',
        'workspace_id',
        'account_id',
        'snapshot_key',
        'inquiries_30d',
        'inquiries_90d',
        'quotes_30d',
        'quotes_90d',
        'shipments_30d',
        'shipments_90d',
        'shipments_prev_90d',
        'bookings_90d',
        'won_opportunities_180d',
        'revenue_365d',
        'lifetime_inquiries',
        'lifetime_shipments',
        'days_since_last_inquiry',
        'days_since_last_quote',
        'days_since_last_shipment',
        'days_since_last_booking',
        'last_inquiry_at',
        'last_quote_at',
        'last_shipment_at',
        'last_booking_at',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'inquiries_30d' => 'integer',
            'inquiries_90d' => 'integer',
            'quotes_30d' => 'integer',
            'quotes_90d' => 'integer',
            'shipments_30d' => 'integer',
            'shipments_90d' => 'integer',
            'shipments_prev_90d' => 'integer',
            'bookings_90d' => 'integer',
            'won_opportunities_180d' => 'integer',
            'revenue_365d' => 'decimal:2',
            'lifetime_inquiries' => 'integer',
            'lifetime_shipments' => 'integer',
            'days_since_last_inquiry' => 'integer',
            'days_since_last_quote' => 'integer',
            'days_since_last_shipment' => 'integer',
            'days_since_last_booking' => 'integer',
            'last_inquiry_at' => 'datetime',
            'last_quote_at' => 'datetime',
            'last_shipment_at' => 'datetime',
            'last_booking_at' => 'datetime',
            'evaluated_at' => 'datetime',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
