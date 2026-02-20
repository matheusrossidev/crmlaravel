<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('platform', ['facebook', 'google']);
            $table->string('external_id');
            $table->string('name', 500);
            $table->string('status', 50)->default('active');
            $table->string('objective', 100)->nullable();
            $table->decimal('budget_daily', 10, 2)->nullable();
            $table->decimal('budget_lifetime', 10, 2)->nullable();
            $table->json('metrics_json')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'platform', 'external_id']);
            $table->index(['tenant_id', 'platform', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
