<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApprovePaymentHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('approve_payment_histories')) {
            Schema::create('approve_payment_histories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('order_id');
                $table->string('user_id');
                $table->string('zone_id');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approve_payment_histories');
    }
}
