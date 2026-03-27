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
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('default_workspace_id')->nullable()->after('company_id')->constrained('workspaces')->nullOnDelete();
            $table->string('job_title')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_workspace_id');
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['job_title', 'is_active']);
        });
    }
};
