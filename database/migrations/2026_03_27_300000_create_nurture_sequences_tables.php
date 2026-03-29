<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nurture_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100);
            $table->string('description', 191)->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('channel', 20)->default('whatsapp');
            $table->boolean('exit_on_reply')->default(true);
            $table->boolean('exit_on_stage_change')->default(false);
            $table->unsignedInteger('stats_enrolled')->default(0);
            $table->unsignedInteger('stats_completed')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('nurture_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sequence_id');
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->string('type', 20); // message, wait_reply, condition, action
            $table->json('config');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['sequence_id', 'position']);
            $table->foreign('sequence_id')->references('id')->on('nurture_sequences')->cascadeOnDelete();
        });

        Schema::create('lead_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('sequence_id');
            $table->unsignedSmallInteger('current_step_position')->default(0);
            $table->string('status', 20)->default('active'); // active, paused, completed, exited
            $table->timestamp('next_step_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('exited_at')->nullable();
            $table->string('exit_reason', 30)->nullable(); // replied, human_takeover, stage_changed, manual, timeout
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'next_step_at']);
            $table->unique(['lead_id', 'sequence_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('sequence_id')->references('id')->on('nurture_sequences')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sequences');
        Schema::dropIfExists('nurture_sequence_steps');
        Schema::dropIfExists('nurture_sequences');
    }
};
