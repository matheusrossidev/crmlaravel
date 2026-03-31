<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->string('area', 30)->nullable();
            $table->string('title', 100);
            $table->text('description');
            $table->string('impact', 20)->nullable();
            $table->tinyInteger('priority')->default(3);
            $table->string('evidence_path')->nullable();
            $table->boolean('can_contact')->default(false);
            $table->string('contact_email', 191)->nullable();
            $table->string('url_origin')->nullable();
            $table->string('plan_name', 30)->nullable();
            $table->string('user_role', 20)->nullable();
            $table->string('status', 20)->default('new');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
