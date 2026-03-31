<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_goals', function (Blueprint $table) {
            $table->string('type', 50)->change();
            $table->boolean('is_recurring')->default(false)->after('created_by');
            $table->decimal('growth_rate', 5, 2)->nullable()->after('is_recurring');
            $table->foreignId('parent_goal_id')->nullable()->after('growth_rate')
                ->constrained('sales_goals')->nullOnDelete();
            $table->json('bonus_tiers')->nullable()->after('parent_goal_id');
        });

        Schema::create('sales_goal_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('goal_id')->nullable()->constrained('sales_goals')->nullOnDelete();
            $table->string('type', 50);
            $table->string('period', 20);
            $table->decimal('target_value', 12, 2);
            $table->decimal('achieved_value', 12, 2)->default(0);
            $table->decimal('percentage', 5, 1)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'user_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_goal_snapshots');

        Schema::table('sales_goals', function (Blueprint $table) {
            $table->dropForeign(['parent_goal_id']);
            $table->dropColumn(['is_recurring', 'growth_rate', 'parent_goal_id', 'bonus_tiers']);
        });
    }
};
