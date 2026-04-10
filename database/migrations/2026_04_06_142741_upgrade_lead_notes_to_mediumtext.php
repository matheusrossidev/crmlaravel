<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eleva as colunas de notas (texto livre) de TEXT (65 KB) pra MEDIUMTEXT (16 MB).
     * Usuários estavam batendo no limite ao tentar colar notas longas/relatórios.
     */
    public function up(): void
    {
        // lead_notes.body — usado pelas notas múltiplas (LeadNote model)
        if (Schema::hasTable('lead_notes')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE lead_notes MODIFY COLUMN body MEDIUMTEXT NOT NULL');
            }
        }

        // leads.notes — campo legado do model Lead (usado em alguns lugares)
        if (Schema::hasColumn('leads', 'notes')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE leads MODIFY COLUMN notes MEDIUMTEXT NULL');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lead_notes')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE lead_notes MODIFY COLUMN body TEXT NOT NULL');
            }
        }

        if (Schema::hasColumn('leads', 'notes')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE leads MODIFY COLUMN notes TEXT NULL');
            }
        }
    }
};
