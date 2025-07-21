<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLocationDataToAcceptDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('accept_deliveries', 'location_data')) {
            Schema::table('accept_deliveries', function (Blueprint $table) {
                $table->longText('location_data')->nullable();
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
        if (Schema::hasColumn('accept_deliveries', 'location_data')) {
            Schema::table('accept_deliveries', function (Blueprint $table) {
                $table->dropColumn('location_data');
            });
        }
    }
}
