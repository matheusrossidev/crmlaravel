<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE lead_events MODIFY COLUMN event_type ENUM(
            'created', 'updated', 'stage_changed', 'pipeline_changed',
            'assigned', 'note_added', 'whatsapp_started', 'site_visit',
            'sale_won', 'sale_lost', 'custom_field_updated',
            'task_created', 'task_updated', 'merged'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE lead_events MODIFY COLUMN event_type ENUM(
            'created', 'updated', 'stage_changed', 'pipeline_changed',
            'assigned', 'note_added', 'whatsapp_started', 'site_visit',
            'sale_won', 'sale_lost', 'custom_field_updated',
            'task_created', 'task_updated'
        ) NOT NULL");
    }
};
