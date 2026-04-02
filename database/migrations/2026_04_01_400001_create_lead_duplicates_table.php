<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_duplicates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id_a')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('lead_id_b')->constrained('leads')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->enum('status', ['pending', 'merged', 'ignored'])->default('pending');
            $table->enum('detected_by', ['realtime', 'import', 'scheduled_job']);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['lead_id_a', 'lead_id_b']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_duplicates');
    }
};
