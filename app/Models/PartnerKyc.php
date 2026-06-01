<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerKyc extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'partner_kyc';
    protected $fillable = [
        'partner_id',
        'gstin',
        'pan_number',
        'bank_account_no',
        'bank_ifsc',
        'cancelled_cheque_path',
        'status',
        'created_by',
        'updated_by',
    ];
    /**
     * Get the partner owning this KYC configuration.
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
