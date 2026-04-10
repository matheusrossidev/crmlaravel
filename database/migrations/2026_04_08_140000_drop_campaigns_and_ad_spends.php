<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop FK columns ANTES das tabelas pra evitar constraint errors
        // Cada operação isolada pra funcionar tanto MySQL quanto SQLite
        $this->safeDropColumn('leads', 'campaign_id', 'leads_tenant_id_campaign_id_index');
        $this->safeDropColumn('sales', 'campaign_id', 'sales_tenant_id_campaign_id_index');
        $this->safeDropColumn('lost_sales', 'campaign_id');
        $this->safeDropColumn('whatsapp_conversations', 'referral_campaign_id');

        if (Schema::hasColumn('whatsapp_conversations', 'referral_source')) {
            Schema::table('whatsapp_conversations', function (Blueprint $table): void {
                $table->dropColumn('referral_source');
            });
        }

        Schema::dropIfExists('ad_spends');
        Schema::dropIfExists('campaigns');
    }

    private function safeDropColumn(string $table, string $column, ?string $indexName = null): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        // Drop foreign key first (MySQL only)
        try {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign([$column]));
        } catch (\Throwable) {}

        // Drop index if exists (separate call to isolate failure)
        if ($indexName) {
            try {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($indexName));
            } catch (\Throwable) {}
        }

        // Drop column
        Schema::table($table, fn (Blueprint $t) => $t->dropColumn($column));
    }

    public function down(): void
    {
        // Sem rollback estrutural — feature foi removida intencionalmente.
    }
};
