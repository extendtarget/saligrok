<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCancelReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cancel_reasons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('role_id');
            $table->bigInteger('usage')->default(0);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('refund_type')->nullable();
            $table->string('cancel_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cancel_reasons');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('refund_type');
            $table->dropColumn('cancel_reason');
        });
    }
}
