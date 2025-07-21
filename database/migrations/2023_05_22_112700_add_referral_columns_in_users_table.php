<?php

use App\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferralColumnsInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('users', ['referral_code', 'referred_by'])) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('referral_code')->nullable();
                $table->string('referred_by')->nullable();
            });
        }

        $users = User::whereNull('referral_code')->get();
        foreach ($users as $user) {
            $user->referral_code = str_random(6);
            $user->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumns('users', ['referral_code', 'referred_by'])) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('referral_code');
                $table->dropColumn('referred_by');
            });
        }
    }
}
