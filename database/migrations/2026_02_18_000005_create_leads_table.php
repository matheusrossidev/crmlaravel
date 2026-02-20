<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->string('source', 100)->default('manual');
            $table->json('tags')->nullable();
            $table->foreignId('pipeline_id')->constrained();
            $table->foreignId('stage_id')->references('id')->on('pipeline_stages');
            $table->foreignId('assigned_to')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'id']);
            $table->index(['tenant_id', 'pipeline_id', 'stage_id']);
            $table->index(['tenant_id', 'source']);
            $table->index(['tenant_id', 'assigned_to']);
            $table->index(['tenant_id', 'campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
