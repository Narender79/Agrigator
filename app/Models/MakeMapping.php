<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MakeMapping extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'make_mapping';
    protected $fillable = [
        'insurer_id',
        'dr_make_id',
        'insurer_make_id',
        'insurer_make_name',
        'logo_path',
        'status',
        'is_popular',
        'vehicle_type',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'is_popular' => 'boolean',
    ];
    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }

    /**
     * Relationship to the Droom canonical Make
     */
    public function make()
    {
        return $this->belongsTo(Make::class, 'dr_make_id');
    }
}
