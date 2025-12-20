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
        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->integer('version'); // 4 or 6
            $table->string('subnet')->nullable();
            $table->string('gateway')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('group_id')->nullable()->constrained('ip_address_groups')->nullOnDelete();
            $table->string('status')->default('available'); // available, assigned, reserved
            $table->timestamps();

            $table->unique(['ip_address', 'version']);
            $table->index('version');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_addresses');
    }
};
