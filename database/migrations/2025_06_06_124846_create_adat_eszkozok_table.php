<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('adat_eszkozok', function (Blueprint $table) {
        $table->id();
        $table->uuid('device_id')->unique();
        $table->string('device')->nullable();
        $table->string('os')->nullable();
        $table->string('app_version')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adat_eszkozok');
    }
};
