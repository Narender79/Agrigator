<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Model;

class UsersInsurance extends Model
{
    protected $table = 'users_insurance';

    protected $fillable = [
        'insurance_unique_id',
        'user_id',
        'email',
        'name',
        'phone',
        'vehicleType',
        'make',
        'model',
        'trim',
        'year',
        'quote_id',
        'quote_no',
        'product_id',
        'product_group_id',
        'proposal_id',
        'proposal_no',
        'document_id',
        'nstp_id',
        'payment_id',
        'payment_status',
        'insurance_payment_id',
        'insuranc_payment_ref_no',
        'droom_payment_id',
        'gateway_txn_id',
        'insurance_txn_start_time',
        'pol_plan_id',
        'pol_plan_variant',
        'policy_id',
        'policy_no',
        'policy_start_date',
        'net_premium',
        'registration_no',
        'policy_url',
        'city',
        'platform',
        'status',
        'source',
        'utm_source',
        'quotes_mail',
        'proposal_mail',
        'aggregator_name',
    ];
}
