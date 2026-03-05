<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_conversations', function (Blueprint $table) {
            $table->string('utm_id', 100)->nullable()->after('utm_term');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('utm_id', 100)->nullable()->after('utm_content');
        });
    }

    public function down(): void
    {
        Schema::table('website_conversations', function (Blueprint $table) {
            $table->dropColumn('utm_id');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('utm_id');
        });
    }
};
