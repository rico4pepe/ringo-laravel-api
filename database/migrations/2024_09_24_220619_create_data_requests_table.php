<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_requests', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('network');
            $table->string('package');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['0', '1', '2'])->default('0');
            $table->string('request_id')->nullable();
            $table->string('external_ref')->nullable();
            $table->text('response');
            $table->unsignedBigInteger('bulk_id')->nullable();
            $table->string('phone_number');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('biller_id');
            $table->string('trans_code');
            $table->string('api_method')->nullable();
            $table->string('payload')->nullable();
            $table->string('source')->nullable();
            $table->string('bundle')->nullable();
            $table->string('type')->nullable();

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
        Schema::dropIfExists('data_requests');
    }
}
