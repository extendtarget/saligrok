<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStoreShowColumnToCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('show_in_restaurant')->default(1);
            $table->boolean('show_in_cart')->after('show_in_restaurant')->default(false);
            $table->boolean('show_in_home')->after('show_in_cart')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('show_in_restaurant');
            $table->dropColumn('show_in_cart');
            $table->dropColumn('show_in_home');
        });
    }
}
