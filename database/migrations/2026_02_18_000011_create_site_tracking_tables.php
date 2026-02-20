<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_id', 36);
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('page_url', 2000);
            $table->string('page_title', 500)->nullable();
            $table->string('referrer', 2000)->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'visitor_id']);
            $table->index(['tenant_id', 'lead_id']);
            $table->index(['tenant_id', 'utm_source']);
        });

        Schema::create('site_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_id', 36);
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_name');
            $table->json('event_data_json')->nullable();
            $table->string('page_url', 2000)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'event_name']);
            $table->index(['tenant_id', 'visitor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_events');
        Schema::dropIfExists('site_visits');
    }
};
