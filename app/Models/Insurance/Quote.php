<?php

namespace App\Models\Insurance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    use HasFactory;

    protected $table = 'quotes';

    protected $fillable = [
        'user_id',
        'insurer_id',
        'quote_request_session_id',
        'vehicle_id',
        'product_id',
        'coverage_type',
        'idv',
        'idv_type',
        'policy_term_months',
        'selected_add_ons',
        'ncb_percentage',
        'ncb_verified',
        'previous_policy_expiry',
        'is_new_vehicle',
        'own_damage_premium',
        'third_party_premium',
        'add_on_premium',
        'pa_cover_premium',
        'ncb_discount',
        'voluntary_deductible',
        'voluntary_deductible_discount',
        'anti_theft_discount',
        'aai_discount',
        'net_premium',
        'gst',
        'total_premium',
        'status',
        'valid_until',
        'insurer_quote_ref',
        'source_channel',
    ];

    protected $casts = [
        'selected_add_ons' => 'array',
        'ncb_verified' => 'boolean',
        'is_new_vehicle' => 'boolean',
        'previous_policy_expiry' => 'date',
        'valid_until' => 'datetime',
        'idv' => 'float',
        'own_damage_premium' => 'float',
        'third_party_premium' => 'float',
        'add_on_premium' => 'float',
        'pa_cover_premium' => 'float',
        'ncb_discount' => 'float',
        'voluntary_deductible' => 'float',
        'voluntary_deductible_discount' => 'float',
        'anti_theft_discount' => 'float',
        'aai_discount' => 'float',
        'net_premium' => 'float',
        'gst' => 'float',
        'total_premium' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class);
    }
}
