<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('auth_provider')->nullable()->after('email');
            $table->string('auth_provider_id')->nullable()->after('auth_provider');
            $table->text('avatar_url')->nullable()->after('remember_token');

            $table->unique(['auth_provider', 'auth_provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_auth_provider_auth_provider_id_unique');
            $table->dropColumn(['auth_provider', 'auth_provider_id', 'avatar_url']);
        });
    }
};
