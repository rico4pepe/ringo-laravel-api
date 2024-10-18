<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'user_id',
        'network',
        'package',
        'amount',
        'status',
        'request_id',
        'response',
        'external_ref',
        'trariff',
        'bulk_id',
        'phone_number',
        'category_id',
        'biller_id',
        'trans_code',
        'api_method',
        'payload',
        'bundle',
        'source',
        'type'
    ];
}
