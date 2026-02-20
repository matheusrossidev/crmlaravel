<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->enum('event_type', [
                'created', 'updated', 'stage_changed', 'pipeline_changed',
                'assigned', 'note_added', 'whatsapp_started', 'site_visit',
                'sale_won', 'sale_lost', 'custom_field_updated',
            ]);
            $table->text('description')->nullable();
            $table->json('data_json')->nullable();
            $table->foreignId('performed_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_events');
    }
};
