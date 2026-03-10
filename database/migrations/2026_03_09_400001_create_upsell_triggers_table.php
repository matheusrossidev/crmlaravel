<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upsell_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('source_plan', 50)->nullable();
            $table->string('target_plan', 50);
            $table->string('metric', 50);
            $table->string('threshold_type', 20)->default('percentage');
            $table->decimal('threshold_value', 8, 2);
            $table->string('action_type', 30)->default('banner');
            $table->json('action_config')->nullable();
            $table->unsignedInteger('cooldown_hours')->default(72);
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upsell_triggers');
    }
};
