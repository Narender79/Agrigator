<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerCredential extends Model
{
    use HasFactory;
    protected $fillable = [
        'partner_id',
        'api_key',
        'api_secret_hash',
        'status',
        'rate_limit_tier',
    ];
    /**
     * Get the partner owning this credential.
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
