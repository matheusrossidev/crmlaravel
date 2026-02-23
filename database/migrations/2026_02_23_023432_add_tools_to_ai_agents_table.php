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
            $table->boolean('enable_pipeline_tool')->default(false)->after('auto_assign');
            $table->boolean('enable_tags_tool')->default(false)->after('enable_pipeline_tool');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn(['enable_pipeline_tool', 'enable_tags_tool']);
        });
    }
};
