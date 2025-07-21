<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCommissionColumnsToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->decimal('commission_amount', 10, 2)->nullable();
            $table->string('driver_order_commission_type')->nullable();
            $table->decimal('driver_order_commission_rate', 5, 2)->nullable();
            $table->decimal('driver_order_commission_amount', 10, 2)->nullable();
            $table->decimal('driver_order_tip_rate', 5, 2)->nullable();
            $table->decimal('driver_order_tip_amount', 10, 2)->nullable();
            $table->decimal('driver_salary', 10, 2)->nullable();
            $table->decimal('final_profit', 10, 2)->nullable();
            $table->decimal('restaurant_net_amount', 10, 2)->nullable();
        });

        Schema::table('restaurant_earnings', function (Blueprint $table) {
            $table->decimal('net_amount', 20, 2)->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
            $table->dropColumn('commission_amount');
            $table->dropColumn('driver_order_commission_type');
            $table->dropColumn('driver_order_commission_rate');
            $table->dropColumn('driver_order_commission_amount');
            $table->dropColumn('driver_order_tip_rate');
            $table->dropColumn('driver_order_tip_amount');
            $table->dropColumn('driver_salary');
            $table->dropColumn('final_profit');
            $table->dropColumn('restaurant_net_amount');
        });
        Schema::table('restaurant_earnings', function (Blueprint $table) {
            $table->dropColumn('net_amount');
        });
    }
}
