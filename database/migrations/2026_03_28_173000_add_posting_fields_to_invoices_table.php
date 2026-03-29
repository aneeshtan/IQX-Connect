<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('posted_by_user_id')->nullable()->after('assigned_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable()->after('balance_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('posted_by_user_id');
            $table->dropColumn('posted_at');
        });
    }
};
