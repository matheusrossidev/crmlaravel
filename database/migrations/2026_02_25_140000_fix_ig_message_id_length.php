<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Instagram message IDs are ~164+ chars; VARCHAR(100) was too short.
     */
    public function up(): void
    {
        Schema::table('instagram_messages', function (Blueprint $table) {
            $table->string('ig_message_id', 191)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('instagram_messages', function (Blueprint $table) {
            $table->string('ig_message_id', 100)->nullable()->change();
        });
    }
};
