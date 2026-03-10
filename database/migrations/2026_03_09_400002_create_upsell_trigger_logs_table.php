<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upsell_trigger_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upsell_trigger_id')->constrained('upsell_triggers')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('action_type', 30);
            $table->unsignedBigInteger('metric_value')->default(0);
            $table->unsignedBigInteger('metric_limit')->default(0);
            $table->timestamp('fired_at');
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('converted_at')->nullable();

            $table->index(['tenant_id', 'upsell_trigger_id', 'fired_at'], 'upsell_logs_tenant_trigger_fired');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upsell_trigger_logs');
    }
};
