<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Meta usa dois IDs para a mesma conta Instagram Business:
     * - instagram_account_id: ID retornado pelo Instagram Login API (IGA token)
     * - ig_business_account_id: ID usado pelo webhook em entry.id (formato Facebook/Meta)
     * Ambos representam a mesma conta mas em namespaces diferentes.
     */
    public function up(): void
    {
        Schema::table('instagram_instances', function (Blueprint $table) {
            $table->string('ig_business_account_id', 30)->nullable()->unique()->after('instagram_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('instagram_instances', function (Blueprint $table) {
            $table->dropColumn('ig_business_account_id');
        });
    }
};
