<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifySmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sms', function (Blueprint $table) {
            //
            $table->string('err_code')->nullable();  
            $table->string('status')->nullable();  

            $table->string('charset')->nullable();  
            $table->string('kannel_id')->nullable();  

            $table->string('sender')->nullable();  
            $table->string('userref')->nullable(); 

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sms', function (Blueprint $table) {
            //
        });
    }
}
