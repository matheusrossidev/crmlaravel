<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_lead_form_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('oauth_connection_id')->constrained('oauth_connections')->cascadeOnDelete();
            $table->string('page_id', 64);
            $table->string('page_name', 191)->nullable();
            $table->text('page_access_token')->nullable();   // encrypted
            $table->string('form_id', 64);
            $table->string('form_name', 191)->nullable();
            $table->json('form_fields_json')->nullable();     // cached form questions from Meta
            $table->foreignId('pipeline_id')->constrained();
            $table->unsignedBigInteger('stage_id');
            $table->json('field_mapping');                    // {meta_field: crm_field}
            $table->json('default_tags')->nullable();
            $table->unsignedBigInteger('auto_assign_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'form_id']);
            $table->index(['page_id', 'form_id']);
        });

        Schema::create('facebook_lead_form_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connection_id')->constrained('facebook_lead_form_connections')->cascadeOnDelete();
            $table->string('meta_lead_id', 64);
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform', 10)->default('fb');    // fb or ig
            $table->string('ad_id', 64)->nullable();
            $table->string('campaign_name_meta', 191)->nullable();
            $table->json('raw_data');
            $table->string('status', 20)->default('processed'); // processed, failed, duplicate, skipped
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'meta_lead_id']);
            $table->index('connection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_lead_form_entries');
        Schema::dropIfExists('facebook_lead_form_connections');
    }
};
