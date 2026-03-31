<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instagram_automations', function (Blueprint $table) {
            $table->string('media_type', 20)->default('IMAGE')->after('media_caption');
        });
    }

    public function down(): void
    {
        Schema::table('instagram_automations', function (Blueprint $table) {
            $table->dropColumn('media_type');
        });
    }
};
