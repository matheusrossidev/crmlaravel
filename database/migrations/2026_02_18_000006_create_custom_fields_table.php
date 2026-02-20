<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('label');
            $table->enum('field_type', ['text', 'textarea', 'number', 'currency', 'date', 'select', 'multiselect', 'checkbox', 'url', 'phone', 'email']);
            $table->json('options_json')->nullable();
            $table->string('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('show_on_card')->default(false);
            $table->integer('card_position')->default(0);
            $table->boolean('show_on_list')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_id')->references('id')->on('custom_field_definitions')->cascadeOnDelete();
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 15, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->tinyInteger('value_boolean')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['lead_id', 'field_id']);
            $table->index(['tenant_id', 'lead_id']);
            $table->index(['tenant_id', 'field_id', 'value_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_field_definitions');
    }
};
