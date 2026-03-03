<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->boolean('use_agno')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn('use_agno');
        });
    }
};
