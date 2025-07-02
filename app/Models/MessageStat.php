<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'network',
        'user_id',
        'batch_id',
        'total_messages',
        'delivered',
        'undelivered',
        'pending',
        'errors',
        'start_id',
        'end_id',
        'created_at',
        'updated_at'
    ];
}
