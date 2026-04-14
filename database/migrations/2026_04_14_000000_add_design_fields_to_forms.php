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
            $table->string('layout', 20)->default('centered')->after('border_radius');
            $table->string('background_image_url', 500)->nullable()->after('layout');
            $table->boolean('enable_logo')->default(true)->after('background_image_url');
            $table->boolean('enable_background_image')->default(false)->after('enable_logo');
            $table->string('color_preset', 30)->default('default')->after('enable_background_image');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['layout', 'background_image_url', 'enable_logo', 'enable_background_image', 'color_preset']);
        });
    }
};
