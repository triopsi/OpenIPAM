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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_two_factor_enabled')->default(false)->after('remember_token');
            $table->string('email_two_factor_code', 6)->nullable()->after('email_two_factor_enabled');
            $table->timestamp('email_two_factor_expires_at')->nullable()->after('email_two_factor_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_two_factor_enabled', 'email_two_factor_code', 'email_two_factor_expires_at']);
        });
    }
};
