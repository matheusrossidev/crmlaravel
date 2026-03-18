<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_quick_messages', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('body');
            $table->string('image_mime', 100)->nullable()->after('image_path');
            $table->string('image_filename')->nullable()->after('image_mime');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_quick_messages', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'image_mime', 'image_filename']);
        });
    }
};
