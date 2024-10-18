<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirtimeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'user_id',
        'network',
        'amount',
        'status',
        'type', 
        'bulk_id',
        'bulk_phone',
        'request_id',
        'response',
        'trariff',
        'external_ref',
        'phone_number',
        'category_id',
        'biller_id',
        'trans_code',
        'api_method',
        'payload',
        'channel',
        'source'

    ];
}
