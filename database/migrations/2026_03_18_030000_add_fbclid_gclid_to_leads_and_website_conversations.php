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
            $table->string('fbclid', 255)->nullable()->after('utm_content');
            $table->string('gclid', 255)->nullable()->after('fbclid');
        });

        Schema::table('website_conversations', function (Blueprint $table) {
            $table->string('fbclid', 255)->nullable()->after('utm_term');
            $table->string('gclid', 255)->nullable()->after('fbclid');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['fbclid', 'gclid']);
        });

        Schema::table('website_conversations', function (Blueprint $table) {
            $table->dropColumn(['fbclid', 'gclid']);
        });
    }
};
