<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsurerIntegration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'insurer_integrations';

    protected $fillable = [
        'insurer_id',
        'api_base_url',
        'auth_type',
        'client_id',
        'client_secret',
        'environment',
        'other_attributes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'other_attributes' => 'array',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }
}