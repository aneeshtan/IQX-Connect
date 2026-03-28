<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('sheet_source_id')->nullable()->after('workspace_id')->constrained()->nullOnDelete();
            $table->index(['workspace_id', 'sheet_source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['workspace_id', 'sheet_source_id']);
            $table->dropConstrainedForeignId('sheet_source_id');
        });
    }
};
