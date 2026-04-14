<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('embed_mode', 20)->default('hosted')->after('data');
            $table->string('referrer_url', 500)->nullable()->after('embed_mode');
            $table->index(['tenant_id', 'embed_mode']);
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'embed_mode']);
            $table->dropColumn(['embed_mode', 'referrer_url']);
        });
    }
};
