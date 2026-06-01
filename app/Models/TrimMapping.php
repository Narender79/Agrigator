<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrimMapping extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trim_mapping';
    protected $fillable = [
        'insurer_id',
        'dr_make_id',
        'dr_model_id',
        'dr_trim_id',
        'insurer_trim_id',
        'insurer_trim_name',
        'status',
        'created_by',
        'updated_by',
    ];
    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }
    public function yearMappings()
    {
        return $this->hasMany(TrimYearMapping::class, 'trim_mapping_id');
    }

    /**
     * Relationship to the Droom canonical Trim/Variant
     */
    public function trim()
    {
        return $this->belongsTo(Trim::class, 'dr_trim_id');
    }
}
