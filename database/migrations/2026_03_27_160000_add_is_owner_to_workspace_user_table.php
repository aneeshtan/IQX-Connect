<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_user', function (Blueprint $table) {
            $table->boolean('is_owner')->default(false)->after('job_title');
        });

        $workspaceIds = DB::table('workspaces')->pluck('id');

        foreach ($workspaceIds as $workspaceId) {
            $ownerUserId = DB::table('users')
                ->where('default_workspace_id', $workspaceId)
                ->orderBy('id')
                ->value('id');

            if (! $ownerUserId) {
                $ownerUserId = DB::table('workspace_user')
                    ->where('workspace_id', $workspaceId)
                    ->orderBy('id')
                    ->value('user_id');
            }

            if ($ownerUserId) {
                DB::table('workspace_user')
                    ->where('workspace_id', $workspaceId)
                    ->where('user_id', $ownerUserId)
                    ->update(['is_owner' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('workspace_user', function (Blueprint $table) {
            $table->dropColumn('is_owner');
        });
    }
};
