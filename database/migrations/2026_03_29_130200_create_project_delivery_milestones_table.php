<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_delivery_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('milestone_label');
            $table->unsignedInteger('sequence')->default(0);
            $table->date('planned_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->string('status')->index();
            $table->string('site_location')->nullable();
            $table->boolean('requires_crane')->default(false);
            $table->boolean('installation_required')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_delivery_milestones');
    }
};
