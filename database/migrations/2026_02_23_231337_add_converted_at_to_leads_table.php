<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->timestamp('converted_at')->nullable()->after('notes');
            $table->index(['tenant_id', 'converted_at'], 'leads_tenant_converted_at_index');
        });

        // Backfill a partir do sales.closed_at para leads que já possuem venda registrada
        DB::statement('
            UPDATE leads l
            INNER JOIN sales s ON s.lead_id = l.id
            SET l.converted_at = s.closed_at
            WHERE l.converted_at IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_tenant_converted_at_index');
            $table->dropColumn('converted_at');
        });
    }
};
