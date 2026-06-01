<?php

namespace App\Models;

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

    protected $casts = [
        'license_expiry' => 'date',
    ];

    /**
     * Get the login user account associated with the insurer.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Get the integrations configured for this insurer.
     */
    public function integrations()
    {
        return $this->hasMany(InsurerIntegration::class);
    }
    /**
     * Get the insurance policies configured for this insurer.
     */
    public function policies()
    {
        return $this->hasMany(InsurancePolicy::class, 'insurance_company_id');
    }
}
