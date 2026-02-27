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
            $table->unsignedBigInteger('pipeline_id')->nullable()->change();
            $table->unsignedBigInteger('stage_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('pipeline_id')->nullable(false)->change();
            $table->unsignedBigInteger('stage_id')->nullable(false)->change();
        });
    }
};
