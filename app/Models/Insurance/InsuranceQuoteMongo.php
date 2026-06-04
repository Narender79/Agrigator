<?php

namespace App\Models\Insurance;

use MongoDB\Laravel\Eloquent\Model;

class InsuranceQuoteMongo extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'insurance_quotes';

    protected $fillable = [
        'insurer_id',
        'proposer_type',
        'proposer_pincode',
        'vehicle_make',
        'vehicle_model',
        'vehicle_variant',
        'man_year',
        'price_offered',
        'status',
        'request_payload',
        'insurer_response_payload',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'insurer_response_payload' => 'array',
        'price_offered' => 'float',
        'insurer_id' => 'integer',
        'man_year' => 'integer',
    ];
}
