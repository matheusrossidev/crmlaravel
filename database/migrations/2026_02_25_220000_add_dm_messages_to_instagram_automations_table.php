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
            $table->json('dm_messages')->nullable()->after('dm_message');
        });
    }

    public function down(): void
    {
        Schema::table('instagram_automations', function (Blueprint $table) {
            $table->dropColumn('dm_messages');
        });
    }
};
