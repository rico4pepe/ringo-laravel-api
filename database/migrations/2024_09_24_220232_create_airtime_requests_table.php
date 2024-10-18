<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAirtimeRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('airtime_requests', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('network');
            $table->unsignedInteger('amount');
            $table->enum('status', ['0', '1', '2'])->default('0');
            $table->string('type')->nullable();
            $table->unsignedBigInteger('bulk_id')->nullable();
            $table->text('bulk_phone');
            $table->string('request_id')->nullable();
            $table->text('response');
            $table->string('trariff')->nullable();
            $table->string('external_ref')->nullable();
            $table->string('phone_number');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('biller_id');
            $table->string('trans_code');
            $table->string('api_method')->nullable();
            $table->string('payload')->nullable();
            $table->enum('channel', ['0', '1', '2'])->nullable();
            $table->string('source')->nullable();
            $table->timestamps();


       // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('airtime_requests');
    }
}
