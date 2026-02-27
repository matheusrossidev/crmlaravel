<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('trigger_type'); // message_received | conversation_created | lead_created | lead_stage_changed | lead_won | lead_lost
            $table->json('trigger_config')->nullable(); // {channel, pipeline_id, stage_id, ...}
            $table->json('conditions')->nullable();     // [{field, operator, value}, ...]
            $table->json('actions');                    // [{type, config}, ...]
            $table->unsignedInteger('run_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'is_active', 'trigger_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
