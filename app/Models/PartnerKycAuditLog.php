<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerKycAuditLog extends Model
{
    use HasFactory;
    protected $table = 'partner_kyc_audit_log';

    protected $fillable = [
        'partner_id',
        'action',
        'performed_by',
        'remarks',
    ];
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
