<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrimYearMapping extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trim_year_mapping';
    protected $fillable = [
        'year',
        'trim_mapping_id',
        'status',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'year' => 'integer',
    ];
    public function trimMapping()
    {
        return $this->belongsTo(TrimMapping::class, 'trim_mapping_id');
    }
}
