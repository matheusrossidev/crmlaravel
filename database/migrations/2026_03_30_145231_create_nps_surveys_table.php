<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nps_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 191);
            $table->string('type', 20)->default('nps'); // nps, csat, custom
            $table->text('question');
            $table->text('follow_up_question')->nullable();
            $table->string('trigger', 30)->default('manual'); // lead_won, conversation_closed, manual
            $table->unsignedSmallInteger('delay_hours')->default(0);
            $table->string('send_via', 20)->default('whatsapp'); // whatsapp, link
            $table->boolean('is_active')->default(true);
            $table->string('slug', 100)->unique();
            $table->text('thank_you_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nps_surveys');
    }
};
