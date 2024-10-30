<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToCampaignTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign', function (Blueprint $table) {
            //
            
            $table->uuid('unique_reference')->unique(); 
            $table->string('campaign_title');
            $table->string('file_path');
            $table->date('schedule_date');
            $table->time('schedule_time');
            $table->boolean('is_custom_message');
            $table->string('status')->default('pending'); // pending, completed, file_missing
            $table->json('summary')->nullable(); // Store import summary details
        
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign', function (Blueprint $table) {
            //
        });
    }
}
