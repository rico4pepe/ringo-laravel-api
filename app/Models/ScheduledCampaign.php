<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_title',
        'file_path',
        'schedule_date',
        'schedule_time',
        'is_custom_message',
        'status',
        'summary',
    ];
}
