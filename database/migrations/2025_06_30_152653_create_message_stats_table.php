<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
      public function up(): void
    {
        Schema::create('message_stats', function (Blueprint $table) {
            $table->id();

            $table->string('network', 10)->index(); // e.g. MTN, AIRTEL
            $table->unsignedInteger('batch_id');    // e.g. 1, 2, 3 for each 1000 record window

            $table->unsignedInteger('total_messages')->default(0);
            $table->unsignedInteger('delivered')->default(0);
            $table->unsignedInteger('undelivered')->default(0);
            $table->unsignedInteger('pending')->default(0);

            $table->string('start_id', 20)->nullable();  // first message ID in the batch
            $table->string('end_id', 20)->nullable();    // last message ID in the batch

            $table->timestamps();

            $table->unique(['network', 'batch_id']); // avoid duplicate inserts
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_stats');
    }
}
