<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_token_increments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('token_increment_plan_id')->constrained();
            $table->unsignedBigInteger('tokens_added');
            $table->decimal('price_paid', 10, 2);
            $table->string('asaas_payment_id')->nullable();
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_token_increments');
    }
};
