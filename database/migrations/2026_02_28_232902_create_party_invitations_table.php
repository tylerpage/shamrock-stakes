<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();
            $table->unique(['party_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_invitations');
    }
};
