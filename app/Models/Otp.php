<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'otp_code',
        'expires_at',
        'is_verified',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_verified' => 'boolean'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
