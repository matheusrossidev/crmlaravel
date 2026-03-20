<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->string('utm_source', 100)->nullable()->after('tags');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 191)->nullable()->after('utm_medium');
            $table->string('utm_content', 191)->nullable()->after('utm_campaign');
            $table->string('utm_term', 191)->nullable()->after('utm_content');
            $table->string('fbclid', 191)->nullable()->after('utm_term');
            $table->string('gclid', 191)->nullable()->after('fbclid');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropColumn(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid']);
        });
    }
};
