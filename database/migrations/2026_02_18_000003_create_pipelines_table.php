<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#3B82F6');
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'sort_order']);
        });

        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#6B7280');
            $table->integer('position')->default(0);
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['pipeline_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');
    }
};
