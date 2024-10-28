<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'phone_number',
        'birthday',
        'type',
        'discount',
        'status',
        'whitelistip',
        'auto_detect',
        'topicstatus',
        'password',
        'token',
        'token_time',
        'api_time',
        'device_info',
        'device_first_raw',
        'device_failed_raw',
        'device_first_hash',
        'attempt',
        'firstname',
        'lastname',
        'gender',
        'phone',
        'username',
        'pin_auth',
        'trials',
         'question1',
         'question2',
         'answer1',
         'answer2',
         'pin',
         'referred',
         'phone_verified',
         'email_verified',
         'bvn_verified',
         'email_token',
         'pass_reset',
         'phone_token',
         'token_exp_date',
         'token_expiry_time',
         'activation',
         'b2b',
         'print_charge',
         'print_amount_charged',
         'epinsprint',
         'app_token',
         'web_hook',
         'referred_id',
         'providus_account',
         'rolez_account',
         'rolez_status',
         'sterling_account',
         'sterling_reference',
          'sterling_status',
          'providus_reference',
          'providus_status',
          'rubies_account',
          'rolez_reference',
          'rubies_status',
          'rubies_reference',
          'wema_reference',
          'wema_account',
          'wema_status',
          'commission',
          'hash',
          'level',
          'group_id',
          'transacting',
          'fire_hash',
          'fire_failed'

    ];


    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
