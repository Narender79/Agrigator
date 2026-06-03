<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_company_id',
        'product_name',
        'product_code',
        'pol_plan_id',
        'pol_plan_variant',
        'policy_type',
        'status',
    ];
}
