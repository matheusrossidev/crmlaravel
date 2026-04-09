<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop coluna `leads.converted_at` + index `leads_tenant_converted_at_index`.
 *
 * Coluna foi adicionada em 2026_02_23 com backfill via sales.closed_at, mas
 * NUNCA foi lida nem escrita pelo codigo (auditoria 08/04/2026 confirma zero
 * usos no codebase). Index ocupava espaco a toa.
 *
 * Quem precisa saber quando um lead foi convertido usa diretamente
 * `sales.closed_at` (relacao Lead::sales).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_tenant_converted_at_index');
            $table->dropColumn('converted_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->timestamp('converted_at')->nullable()->after('notes');
            $table->index(['tenant_id', 'converted_at'], 'leads_tenant_converted_at_index');
        });
    }
};
