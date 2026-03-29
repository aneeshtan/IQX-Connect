<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSegmentRule extends Model
{
    public const OPERATOR_GREATER_THAN = 'gt';

    public const OPERATOR_GREATER_THAN_OR_EQUAL = 'gte';

    public const OPERATOR_LESS_THAN = 'lt';

    public const OPERATOR_LESS_THAN_OR_EQUAL = 'lte';

    public const OPERATOR_EQUAL = 'eq';

    public const OPERATORS = [
        self::OPERATOR_GREATER_THAN,
        self::OPERATOR_GREATER_THAN_OR_EQUAL,
        self::OPERATOR_LESS_THAN,
        self::OPERATOR_LESS_THAN_OR_EQUAL,
        self::OPERATOR_EQUAL,
    ];

    protected $fillable = [
        'segment_definition_id',
        'metric_key',
        'operator',
        'threshold_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'threshold_value' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function segmentDefinition(): BelongsTo
    {
        return $this->belongsTo(CustomerSegmentDefinition::class, 'segment_definition_id');
    }
}
