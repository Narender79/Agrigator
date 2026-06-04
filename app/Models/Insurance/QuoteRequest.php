<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteRequest extends Model
{
    use HasFactory;

    protected $table = 'quote_requests';

    protected $fillable = [
        'session_id',
        'vehicle_data',
        'status',
        'insurer_count',
        'expires_at',
    ];

    protected $casts = [
        'vehicle_data' => 'array',
        'expires_at' => 'datetime',
    ];
}
