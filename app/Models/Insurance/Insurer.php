<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Insurer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'insurers';

    protected $fillable = [
        'user_id',
        'company_name',
        'regulatory_reg_no',
        'license_expiry',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'license_expiry' => 'date',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }
}