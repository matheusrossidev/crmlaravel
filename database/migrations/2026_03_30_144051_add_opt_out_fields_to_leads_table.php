<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('opted_out')->default(false)->after('score_updated_at');
            $table->timestamp('opted_out_at')->nullable()->after('opted_out');
            $table->string('opted_out_reason', 50)->nullable()->after('opted_out_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['opted_out', 'opted_out_at', 'opted_out_reason']);
        });
    }
};
