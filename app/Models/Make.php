<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Make extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'make';

    protected $fillable = [
        'make_org_name',
        'make_name',
        'image_path',
        'is_popular',
        'year_of_founding',
        'founder',
        'total_units_sold_globally',
        'global_ranking',
        'any_history_of_recall_globally',
        'best_selling_model_globally',
        'top_global_competitor',
        'year_of_launch_in_india',
        'total_units_sold_in_india',
        'india_ranking',
        'any_history_of_recall_in_india',
        'best_selling_model_in_india',
        'closest_competitor_in_india',
        'total_dealerships_in_india',
        'ev_offerings',
        'url_india',
    ];

    protected $casts = [
        'is_popular' => 'integer',
        'any_history_of_recall_globally' => 'integer',
        'any_history_of_recall_in_india' => 'integer',
    ];

    /**
     * Get the models associated with this make.
     */
    public function models()
    {
        return $this->hasMany(VehicleModel::class, 'make_id');
    }

    /**
     * Get the insurer mapping configurations for this make.
     */
    public function mappings()
    {
        return $this->hasMany(MakeMapping::class, 'dr_make_id');
    }
}
