<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reengagement_templates', function (Blueprint $table) {
            $table->string('locale', 5)->default('pt_BR')->after('channel');

            $table->dropUnique(['stage', 'channel']);
            $table->unique(['stage', 'channel', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::table('reengagement_templates', function (Blueprint $table) {
            $table->dropUnique(['stage', 'channel', 'locale']);
            $table->unique(['stage', 'channel']);

            $table->dropColumn('locale');
        });
    }
};
