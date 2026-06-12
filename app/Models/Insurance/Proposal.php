<?php

namespace App\Models\Insurance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'proposals';

    protected $fillable = [
        'user_id',
        'quote_id',
        'policy_id',
        'salutation',
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'dob',
        'age',
        'gender',
        'marital_status',
        'nationality',
        'occupation',
        'email',
        'mobile',
        'address_line_1',
        'address_line_2',
        'pincode',
        'city',
        'state',
        'nominee_name',
        'nominee_relation',
        'nominee_age',
        'appointee_name',
        'appointee_relation',
        'vehicle_chassis',
        'vehicle_engine',
        'pre_insurer_name',
        'pre_insurer_no',
        'insurer_proposal_ref',
        'insurer_quote_ref',
        'status',
    ];

    protected $casts = [
        'dob' => 'date',
        'age' => 'integer',
        'nominee_age' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
