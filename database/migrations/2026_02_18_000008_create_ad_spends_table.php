<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_spends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('spend', 10, 2)->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('cpc', 10, 4)->nullable();
            $table->decimal('cpm', 10, 4)->nullable();
            $table->decimal('ctr', 6, 4)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['tenant_id', 'campaign_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_spends');
    }
};
