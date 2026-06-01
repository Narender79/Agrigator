<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trim extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trim';

    protected $fillable = [
        'make_id',
        'model_id',
        'trim_org_name',
        'trim_name',
        'upcoming',
        'status',
        'launchDate',
        'is_new',
        'is_discontinue',
        'expectedDate',
        'engine_emission_norm',
        'country_code',
    ];

    protected $casts = [
        'upcoming' => 'integer',
        'is_new' => 'integer',
        'is_discontinue' => 'integer',
        'launchDate' => 'date',
        'expectedDate' => 'date',
    ];

    /**
     * Get the parent make associated with this trim.
     */
    public function make()
    {
        return $this->belongsTo(Make::class, 'make_id');
    }

    /**
     * Get the parent model associated with this trim.
     */
    public function model()
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    /**
     * Get the insurer mapping configurations for this trim.
     */
    public function mappings()
    {
        return $this->hasMany(TrimMapping::class, 'dr_trim_id');
    }
}
