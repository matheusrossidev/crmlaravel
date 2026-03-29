<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add score columns to leads
        Schema::table('leads', function (Blueprint $table) {
            $table->smallInteger('score')->default(0)->after('birthday');
            $table->timestamp('score_updated_at')->nullable()->after('score');
        });

        // Scoring rules (tenant-configurable)
        Schema::create('scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100);
            $table->string('category', 30);   // engagement, pipeline, profile
            $table->string('event_type', 50);  // message_received, stage_advanced, etc.
            $table->json('conditions')->nullable();
            $table->smallInteger('points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('cooldown_hours')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'event_type']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Score change log (append-only)
        Schema::create('lead_score_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('scoring_rule_id')->nullable();
            $table->smallInteger('points');
            $table->string('reason', 191);
            $table->json('data_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'lead_id', 'created_at']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('scoring_rule_id')->references('id')->on('scoring_rules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_score_logs');
        Schema::dropIfExists('scoring_rules');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['score', 'score_updated_at']);
        });
    }
};
