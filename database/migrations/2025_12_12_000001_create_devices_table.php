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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hostname')->nullable();
            $table->string('mac_address')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->nullable(); // router, switch, server, workstation, etc.
            $table->string('location')->nullable();
            $table->string('status')->default('active'); // active, inactive, maintenance
            $table->timestamp('last_seen')->nullable(); // Last time device was seen/pinged
            $table->timestamps();

            $table->index('name');
            $table->index('mac_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
