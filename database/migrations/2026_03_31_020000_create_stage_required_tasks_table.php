<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_required_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_stage_id')->constrained('pipeline_stages')->cascadeOnDelete();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('task_type', 20)->default('task');
            $table->string('priority', 20)->default('medium');
            $table->unsignedInteger('due_date_offset')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_required_tasks');
    }
};
