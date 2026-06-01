<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelMapping extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'model_mapping';
    protected $fillable = [
        'insurer_id',
        'category_id',
        'dr_make_id',
        'dr_model_id',
        'logo_path',
        'insurer_model_id',
        'insurer_model_name',
        'status',
        'created_by',
        'updated_by',
    ];
    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relationship to the Droom canonical Model
     */
    public function model()
    {
        return $this->belongsTo(VehicleModel::class, 'dr_model_id');
    }
}
