<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('yes_no')->comment('yes_no or people');
            $table->timestamp('ends_at')->nullable();
            $table->string('resolution_type')->default('voting')->comment('official or voting');
            $table->timestamp('voting_ends_at')->nullable()->comment('24h after ends_at for voting outcome');
            $table->string('status')->default('setup')->comment('setup, pre_voting, live, resolved');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('markets');
    }
}
