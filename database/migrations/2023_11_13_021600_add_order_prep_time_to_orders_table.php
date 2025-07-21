<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderPrepTimeToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('orders', 'prep_time')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dateTime('prep_time')->nullable();
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
        if (Schema::hasColumn('orders', 'order_prep_time')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('prep_time');
            });
        }
    }
}
