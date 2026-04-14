<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('whatsapp_instance_id')->constrained('whatsapp_instances')->cascadeOnDelete();

            $table->string('name', 64);
            $table->string('language', 10);
            $table->string('category', 20);

            $table->json('components');
            $table->json('sample_variables')->nullable();

            $table->string('status', 20)->default('PENDING');
            $table->string('meta_template_id', 64)->nullable();
            $table->text('rejected_reason')->nullable();
            $table->string('quality_rating', 20)->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('meta_template_id');
            $table->unique(
                ['whatsapp_instance_id', 'name', 'language'],
                'waba_template_name_lang_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
