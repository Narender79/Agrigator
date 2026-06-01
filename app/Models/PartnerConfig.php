<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerConfig extends Model
{
    use HasFactory;
    protected $table = 'partner_config';
    protected $fillable = [
        'partner_id',
        'webhook_url',
        'callback_secret',
        'config_type',
    ];
    /**
     * Get the partner owning this config.
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
