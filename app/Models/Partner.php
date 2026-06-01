<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_name',
        'partner_type',
        'contact_email',
        'contact_person_name',
        'contact_person_mobile',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the user account representing this partner admin.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Get the KYC document configuration for the partner.
     */
    public function kyc()
    {
        return $this->hasOne(PartnerKyc::class);
    }
    /**
     * Get the credentials associated with the partner.
     */
    public function credentials()
    {
        return $this->hasMany(PartnerCredential::class);
    }
    /**
     * Get the webhook config associated with the partner.
     */
    public function config()
    {
        return $this->hasOne(PartnerConfig::class);
    }
}
