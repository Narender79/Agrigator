<?php

namespace App\Models\Insurance;

use MongoDB\Laravel\Eloquent\Model;

class QuoteReqRes extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'insurance_quotes';

    protected $fillable = [
        'session_id',
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
        'price_offered' => 'float',
        'insurer_id' => 'integer',
        'man_year' => 'integer',
    ];
}
