<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBetInputModeToPartiesTable extends Migration
{
    public function up()
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->string('bet_input_mode', 20)->default('contracts')->after('default_balance');
        });
    }

    public function down()
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn('bet_input_mode');
        });
    }
}
