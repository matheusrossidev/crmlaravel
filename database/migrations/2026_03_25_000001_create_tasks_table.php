<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('subject', 191);
            $table->text('description')->nullable();
            $table->string('type', 30);          // call, email, task, visit, whatsapp, meeting
            $table->string('status', 20)->default('pending'); // pending, in_progress, completed, cancelled
            $table->string('priority', 10)->default('medium'); // low, medium, high
            $table->date('due_date');
            $table->time('due_time')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->unsignedBigInteger('whatsapp_conversation_id')->nullable()->index();
            $table->unsignedBigInteger('instagram_conversation_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('whatsapp_conversation_id')->references('id')->on('whatsapp_conversations')->onDelete('set null');
            $table->foreign('instagram_conversation_id')->references('id')->on('instagram_conversations')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['tenant_id', 'status', 'due_date']);
            $table->index(['tenant_id', 'assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
