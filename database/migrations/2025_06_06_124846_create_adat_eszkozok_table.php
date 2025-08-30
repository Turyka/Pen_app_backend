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
        $table->string('device_id')->unique(); 
        $table->string('device');
        $table->string('os');
        $table->string('fcm_token');
        $table->boolean('kozlemenyErtesites')->default(true);
        $table->boolean('naptarErtesites')->default(true);
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
