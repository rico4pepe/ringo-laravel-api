<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBvnVerificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bvn_verifications', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');

            $table->string('lastname');
            $table->string('middlename');
            $table->string('date_of_birth');
            $table->string('validity');
        
            $table->enum('status', ['0', '1', '2', '3'])->nullable();
            $table->string('bvn')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

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
        Schema::dropIfExists('bvn_verifications');
    }
}
