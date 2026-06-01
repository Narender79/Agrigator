<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'model';

    protected $fillable = [
        'category_id',
        'make_id',
        'model_org_name',
        'model_name',
        'image_path',
        'year_of_launch_globally',
        'is_sold_globally',
        'total_units_sold_globally',
        'global_ranking',
        'any_history_of_recall_globally',
        'closest_competitor_globally',
        'year_of_launch_in_india',
        'total_units_sold_in_india',
        'india_ranking',
        'any_history_of_recall_in_india',
        'closest_competitor_in_india',
        'total_dealerships_in_india',
        'best_selling_color',
        'last_model_update_year',
    ];

    protected $casts = [
        'is_sold_globally' => 'integer',
        'any_history_of_recall_globally' => 'integer',
        'any_history_of_recall_in_india' => 'integer',
    ];

    /**
     * Get the parent make associated with this model.
     */
    public function make()
    {
        return $this->belongsTo(Make::class, 'make_id');
    }

    /**
     * Get the category this model belongs to.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the trims associated with this model.
     */
    public function trims()
    {
        return $this->hasMany(Trim::class, 'model_id');
    }

    /**
     * Get the insurer mapping configurations for this model.
     */
    public function mappings()
    {
        return $this->hasMany(ModelMapping::class, 'dr_model_id');
    }
}
