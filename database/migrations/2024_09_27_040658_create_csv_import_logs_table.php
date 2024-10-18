<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCsvImportLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('csv_import_logs', function (Blueprint $table) {
            $table->id();
            $table->json('api_request_data'); // Store the request data in JSON format
            $table->json('api_response_data')->nullable(); // Store the API response data
            $table->enum('status', [0, 1, 2, 3])->default(0); // Track status
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
        Schema::dropIfExists('csv_import_logs');
    }
}
