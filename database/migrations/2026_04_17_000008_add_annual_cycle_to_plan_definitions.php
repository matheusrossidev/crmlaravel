<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_definitions', function (Blueprint $table): void {
            $table->string('billing_cycle', 10)->default('monthly')->after('price_usd');
            $table->string('group_slug', 50)->nullable()->after('billing_cycle');
            $table->boolean('is_recommended')->default(false)->after('is_visible');

            $table->index('group_slug');
        });
    }

    public function down(): void
    {
        Schema::table('plan_definitions', function (Blueprint $table): void {
            $table->dropIndex(['group_slug']);
            $table->dropColumn(['billing_cycle', 'group_slug', 'is_recommended']);
        });
    }
};
