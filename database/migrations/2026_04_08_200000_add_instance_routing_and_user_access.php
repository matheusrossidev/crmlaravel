<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Pivot user <-> whatsapp_instance (controla quais users veem cada numero)
        Schema::create('user_whatsapp_instance', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_instance_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'whatsapp_instance_id'], 'uwi_unique');
            $table->index('whatsapp_instance_id');
        });

        // 2) is_primary em whatsapp_instances — instance default do tenant pra fallback
        Schema::table('whatsapp_instances', function (Blueprint $table): void {
            if (! Schema::hasColumn('whatsapp_instances', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('label');
                $table->index(['tenant_id', 'is_primary']);
            }
        });

        // 3) instance_id em scheduled_messages — quando setado, dispara por essa instancia
        Schema::table('scheduled_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('scheduled_messages', 'instance_id')) {
                $table->foreignId('instance_id')
                    ->nullable()
                    ->after('conversation_id')
                    ->constrained('whatsapp_instances')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_messages', function (Blueprint $table): void {
            if (Schema::hasColumn('scheduled_messages', 'instance_id')) {
                $table->dropForeign(['instance_id']);
                $table->dropColumn('instance_id');
            }
        });

        Schema::table('whatsapp_instances', function (Blueprint $table): void {
            if (Schema::hasColumn('whatsapp_instances', 'is_primary')) {
                $table->dropIndex(['tenant_id', 'is_primary']);
                $table->dropColumn('is_primary');
            }
        });

        Schema::dropIfExists('user_whatsapp_instance');
    }
};
