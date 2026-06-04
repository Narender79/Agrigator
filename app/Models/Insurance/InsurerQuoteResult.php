<?php

namespace App\Models\Insurance;

use MongoDB\Laravel\Eloquent\Model;

class InsurerQuoteResult extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'insurer_quote_results';

    protected $fillable = [
        'session_id',
        'insurer_slug',
        'insurer_name',
        'status', // success, error, timeout
        'premium_amount',
        'idv',
        'coverage_details',
        'request_payload', // insurer-specific raw request
        'raw_response',    // insurer-specific raw response
        'error_message',
        'response_ms',
    ];

    protected $casts = [
        'coverage_details' => 'array',
        'request_payload'  => 'array',
        'raw_response'     => 'array',
        'premium_amount'   => 'float',
        'idv'              => 'float',
        'response_ms'      => 'integer',
    ];
}
