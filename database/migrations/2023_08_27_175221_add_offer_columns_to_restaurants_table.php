<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOfferColumnsToRestaurantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('item_offers')->default(false);
            $table->decimal('item_offer_min_subtotal')->nullable();
            $table->longText('free_items')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('item_offers');
            $table->dropColumn('item_offer_min_subtotal');
            $table->dropColumn('free_items');
        });
    }
}
