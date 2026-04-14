<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->unsignedInteger('views_count_hosted')->default(0)->after('views_count');
            $table->unsignedInteger('views_count_inline')->default(0)->after('views_count_hosted');
            $table->unsignedInteger('views_count_popup')->default(0)->after('views_count_inline');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['views_count_hosted', 'views_count_inline', 'views_count_popup']);
        });
    }
};
