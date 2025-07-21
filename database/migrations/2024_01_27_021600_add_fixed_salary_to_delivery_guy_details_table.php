<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFixedSalaryToDeliveryGuyDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('delivery_guy_details', 'fixed_salary')) {
            Schema::table('delivery_guy_details', function (Blueprint $table) {
                $table->decimal('fixed_salary', 10, 2)->default(0);
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
        if (Schema::hasColumn('delivery_guy_details', 'order_fixed_salary')) {
            Schema::table('delivery_guy_details', function (Blueprint $table) {
                $table->dropColumn('fixed_salary');
            });
        }
    }
}
