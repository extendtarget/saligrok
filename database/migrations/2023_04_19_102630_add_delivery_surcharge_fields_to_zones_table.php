<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeliverySurchargeFieldsToZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->boolean('delivery_surcharge_active')->default(false);
            $table->string('delivery_surcharge_type')->default('percentage');
            $table->decimal('delivery_surcharge_rate', 10, 2)->default(0);
            $table->string('delivery_surcharge_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn('delivery_surcharge_active');
            $table->dropColumn('delivery_surcharge_type');
            $table->dropColumn('delivery_surcharge_rate');
            $table->dropColumn('delivery_surcharge_reason');
        });
    }
}
