<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->nullable();
            $table->string('email')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('amount', 8, 2);
            $table->decimal('balance_after', 20, 2)->default(0.00);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedInteger('unit')->nullable();
            $table->string('description');
            $table->enum('status', ['0', '1', '2', '3']);
            $table->enum('payment_status', ['0', '1', '2']);
            $table->string('payment_method')->nullable();
            $table->unsignedInteger('product_category_id')->nullable();
            $table->string('source')->nullable();
            $table->enum('channel', ['web', 'app', 'pos', 'b2b', 'ussd']);
            $table->string('request_id')->nullable();
            $table->string('reference')->nullable();  
            $table->string('destination')->nullable();
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
        Schema::dropIfExists('transactions');
    }
}
