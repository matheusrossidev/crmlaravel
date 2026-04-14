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
            $table->string('widget_trigger', 20)->default('immediate')->after('color_preset');
            $table->unsignedInteger('widget_delay')->default(5)->after('widget_trigger');
            $table->unsignedInteger('widget_scroll_pct')->default(50)->after('widget_delay');
            $table->boolean('widget_show_once')->default(true)->after('widget_scroll_pct');
            $table->string('widget_position', 20)->default('center')->after('widget_show_once');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['widget_trigger', 'widget_delay', 'widget_scroll_pct', 'widget_show_once', 'widget_position']);
        });
    }
};
