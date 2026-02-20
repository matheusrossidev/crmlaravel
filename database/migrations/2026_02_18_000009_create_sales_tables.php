<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_sale_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pipeline_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('value', 15, 2);
            $table->foreignId('closed_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamp('closed_at');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'closed_at']);
            $table->index(['tenant_id', 'campaign_id']);
            $table->index(['tenant_id', 'pipeline_id']);
        });

        Schema::create('lost_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pipeline_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reason_id')->nullable()->references('id')->on('lost_sale_reasons')->nullOnDelete();
            $table->text('reason_notes')->nullable();
            $table->timestamp('lost_at');
            $table->foreignId('lost_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'lost_at']);
            $table->index(['tenant_id', 'reason_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_sales');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('lost_sale_reasons');
    }
};
