<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_type',
        'os_name',
        'app_version',
        'ip_address',
        'lat',
        'long',
        'browser_name',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'lat' => 'decimal:8',
        'long' => 'decimal:8',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
