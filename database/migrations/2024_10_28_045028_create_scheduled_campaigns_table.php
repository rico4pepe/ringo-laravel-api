<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduledCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduled_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_title');
            $table->string('file_path');
            $table->date('schedule_date');
            $table->time('schedule_time');
            $table->boolean('is_custom_message');
            $table->string('status')->default('pending'); // pending, completed, file_missing
            $table->json('summary')->nullable(); // Store import summary details
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
        Schema::dropIfExists('scheduled_campaigns');
    }
}
