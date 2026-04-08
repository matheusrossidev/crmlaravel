<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop FK columns ANTES das tabelas pra evitar constraint errors
        if (Schema::hasColumn('leads', 'campaign_id')) {
            Schema::table('leads', function (Blueprint $table): void {
                try { $table->dropForeign(['campaign_id']); } catch (\Throwable) {}
                $table->dropColumn('campaign_id');
            });
        }

        if (Schema::hasColumn('sales', 'campaign_id')) {
            Schema::table('sales', function (Blueprint $table): void {
                try { $table->dropForeign(['campaign_id']); } catch (\Throwable) {}
                $table->dropColumn('campaign_id');
            });
        }

        if (Schema::hasColumn('lost_sales', 'campaign_id')) {
            Schema::table('lost_sales', function (Blueprint $table): void {
                try { $table->dropForeign(['campaign_id']); } catch (\Throwable) {}
                $table->dropColumn('campaign_id');
            });
        }

        if (Schema::hasColumn('whatsapp_conversations', 'referral_campaign_id')) {
            Schema::table('whatsapp_conversations', function (Blueprint $table): void {
                try { $table->dropForeign(['referral_campaign_id']); } catch (\Throwable) {}
                $table->dropColumn('referral_campaign_id');
            });
        }

        if (Schema::hasColumn('whatsapp_conversations', 'referral_source')) {
            Schema::table('whatsapp_conversations', function (Blueprint $table): void {
                $table->dropColumn('referral_source');
            });
        }

        Schema::dropIfExists('ad_spends');
        Schema::dropIfExists('campaigns');
    }

    public function down(): void
    {
        // Sem rollback estrutural — feature foi removida intencionalmente.
        // Se for necessário recriar, restaurar migrations originais via git.
    }
};
