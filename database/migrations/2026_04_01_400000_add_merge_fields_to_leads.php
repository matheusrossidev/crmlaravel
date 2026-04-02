<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('notes');
            $table->unsignedBigInteger('merged_into')->nullable()->after('status');
            $table->timestamp('merged_at')->nullable()->after('merged_into');

            $table->foreign('merged_into')->references('id')->on('leads')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['merged_into']);
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropColumn(['status', 'merged_into', 'merged_at']);
        });
    }
};
