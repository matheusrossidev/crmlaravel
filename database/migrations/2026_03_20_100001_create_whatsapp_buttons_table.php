<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_buttons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number', 30);
            $table->string('default_message', 500)->default('Olá! Vi seu site e gostaria de saber mais.');
            $table->string('button_label', 100)->default('Fale no WhatsApp');
            $table->string('website_token', 36)->unique();
            $table->boolean('show_floating')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('whatsapp_button_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('button_id')->constrained('whatsapp_buttons')->cascadeOnDelete();
            $table->string('visitor_id', 36)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 191)->nullable();
            $table->string('utm_content', 191)->nullable();
            $table->string('utm_term', 191)->nullable();
            $table->string('fbclid', 191)->nullable();
            $table->string('gclid', 191)->nullable();
            $table->text('page_url')->nullable();
            $table->string('referrer_url', 500)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('clicked_at');

            $table->index(['button_id', 'clicked_at']);
            $table->index(['tenant_id', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_button_clicks');
        Schema::dropIfExists('whatsapp_buttons');
    }
};
