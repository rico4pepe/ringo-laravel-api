<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'user_id',
         'code',
        'balance_after',
        'amount',
        'product_id',
        'unit',
        'description',
        'status',
        'payment_status',
        'payment_method',
        'product_category_id',
        'source',
        'channel',
        'request_id',
        'reference',
        'destination'
    ];

    
}
