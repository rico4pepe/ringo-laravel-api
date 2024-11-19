<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{
    use HasFactory;

    protected $table = 'sms';



    protected $fillable = [
        'firstname',
        'lastname',
        'phone_number',
        'message',
        'created_at',
        'updated_at',
        'status_code',
        'api_message',
        'account_number',

    ];
}
