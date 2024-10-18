<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone_number')->nullable();
            $table->date('birthday')->nullable();

            
            $table->string('type')->default('Default');
            $table->string('discount')->default('regular');
            $table->enum('status', ['0', '1']);
            $table->enum('whitelistip', ['0', '1'])->default('0');
            $table->enum('auto_detect', ['0', '1'])->default('0');
            $table->enum('topicstatus', ['0', '1'])->nullable();

            $table->string('password');
            $table->string('token')->nullable();
            $table->timestamp('token_time')->default('2021-01-21 08:58:13');
            $table->text('api_token');
            $table->text('device_info');
            $table->string('device_first_raw')->nullable();
            $table->string('device_failed_raw')->nullable();
            $table->string('device_failed_hash')->nullable();
            $table->string('attempt')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone')->nullable();
            $table->string('username')->nullable();

            $table->enum('pin_auth', ['0', '1', '2'])->default('0');
            $table->unsignedInteger('trials')->nullable();
            $table->text('question1');
            $table->text('question2');
            $table->string('answer1')->nullable();
            $table->string('answer2')->nullable();
            $table->unsignedInteger('pin')->nullable();
            $table->enum('referred', ['y', 'n'])->default('n');
            $table->enum('phone_verified', ['0', '1', '2', '3'])->default('0');
            $table->enum('email_verified', ['0', '1', '2', '3'])->default('0');
            $table->enum('bvn_verified', ['0', '1', '2', '3'])->default('0');
            $table->string('email_token')->nullable();
            $table->string('pass_reset')->nullable();
            $table->unsignedInteger('phone_token')->nullable();
            $table->string('token_exp_date')->nullable();
            $table->timestamp('token_expiry_time')->nullable();
            $table->enum('activation', ['0', '1'])->default('0');
            $table->enum('b2b', ['0', '1'])->default('0');
            $table->decimal('print_charge', 11, 2)->nullable();
            $table->decimal('print_amount_charged', 11, 2)->nullable();
            $table->string('epinsprint')->nullable();
            $table->text('app_token');
            $table->string('web_hook')->nullable();
            $table->unsignedInteger('referred_id')->nullable();
            $table->string('providus_account')->nullable();
            $table->string('rolez_account')->nullable();
            $table->enum('rolez_status', ['0', '1', '2', '3'])->default('0');
            $table->string('sterling_account')->nullable();
            $table->string('sterling_reference')->nullable();
            $table->enum('sterling_status', ['0', '1', '2', '3'])->default('0');
            $table->string('providus_reference')->nullable();
            $table->enum('providus_status', ['0', '1'])->default('0');
            $table->string('rubies_account')->nullable();
            $table->string('rolez_reference')->nullable();
            $table->enum('rubies_status', ['0', '1'])->default('0');
            $table->string('rubies_reference')->nullable(); 
            $table->string('wema_reference')->nullable();
            $table->string('wema_account')->nullable();
            $table->enum('wema_status', ['0', '1', '2', '3', '4', '5'])->nullable();
            $table->string('commission')->nullable();
            $table->string('hash')->nullable();
            $table->string('level')->default('individual');
            $table->unsignedInteger('group_id')->nullable();
            $table->enum('transacting', ['0', '1', '2'])->nullable();
            $table->text('fire_hash');
            $table->text('fire_failed');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
