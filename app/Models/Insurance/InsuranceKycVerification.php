<?php

namespace App\Models\Insurance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceKycVerification extends Model
{
    use HasFactory;

    protected $tabel = 'insurance_kyc_verifications';

    protected $fillable = [
        'user_id',
        'session_id',
        'proposal_no',
        'quote_id',
        'id_type',
        'id_number_masked',
        'id_number_hash',
        'customer_name',
        'ckyc_success',
        'trace_id',
        'status',
    ];

    /**
     * Get the user that performed this KYC verification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
