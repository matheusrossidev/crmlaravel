<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_flows', function (Blueprint $table) {
            $table->string('trigger_type', 30)->default('keyword')->after('trigger_keywords');
            $table->string('trigger_media_id', 191)->nullable()->after('trigger_type');
            $table->text('trigger_media_thumbnail')->nullable()->after('trigger_media_id');
            $table->text('trigger_media_caption')->nullable()->after('trigger_media_thumbnail');
            $table->text('trigger_reply_comment')->nullable()->after('trigger_media_caption');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_flows', function (Blueprint $table) {
            $table->dropColumn(['trigger_type', 'trigger_media_id', 'trigger_media_thumbnail', 'trigger_media_caption', 'trigger_reply_comment']);
        });
    }
};
