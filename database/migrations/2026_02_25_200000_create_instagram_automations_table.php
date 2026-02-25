<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_automations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('instance_id');
            $table->string('name')->nullable();
            $table->string('media_id', 191)->nullable();       // null = todos os posts
            $table->text('media_thumbnail_url')->nullable();
            $table->text('media_caption')->nullable();
            $table->json('keywords');                           // ["palavra1","palavra2"]
            $table->enum('match_type', ['any', 'all'])->default('any');
            $table->text('reply_comment')->nullable();          // resposta pública ao comentário
            $table->text('dm_message')->nullable();             // mensagem DM privada
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->foreign('instance_id')
                  ->references('id')->on('instagram_instances')
                  ->cascadeOnDelete();

            $table->index(['tenant_id', 'instance_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_automations');
    }
};
