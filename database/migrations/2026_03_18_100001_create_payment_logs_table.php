<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // subscription, token_increment
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('asaas_payment_id', 100)->nullable();
            $table->string('status', 20)->default('confirmed');
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->index(['tenant_id', 'paid_at']);
            $table->index('paid_at');
            $table->index('asaas_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
