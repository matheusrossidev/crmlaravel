<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Ranks ────────────────────────────────────────────────────
        Schema::create('partner_ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('image_path')->nullable();
            $table->unsignedInteger('min_sales')->default(0);
            $table->decimal('commission_pct', 5, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('color', 20)->default('#6b7280');
            $table->timestamps();
        });

        // ── Commissions ──────────────────────────────────────────────
        Schema::create('partner_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('asaas_payment_id', 100)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending'); // pending, available, withdrawn, cancelled
            $table->date('available_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // ── Withdrawals ──────────────────────────────────────────────
        Schema::create('partner_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending'); // pending, approved, processing, paid, rejected
            $table->string('pix_key', 100);
            $table->string('pix_key_type', 10); // CPF, CNPJ, EMAIL, PHONE, EVP
            $table->string('pix_holder_name', 100);
            $table->string('pix_holder_cpf_cnpj', 20);
            $table->string('asaas_transfer_id', 100)->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // ── Resources ────────────────────────────────────────────────
        Schema::create('partner_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('category', 50)->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ── Courses ──────────────────────────────────────────────────
        Schema::create('partner_courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ── Lessons ──────────────────────────────────────────────────
        Schema::create('partner_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('partner_courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ── Lesson Progress ──────────────────────────────────────────
        Schema::create('partner_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('partner_lessons')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['tenant_id', 'lesson_id']);
        });

        // ── Certificates ─────────────────────────────────────────────
        Schema::create('partner_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('partner_courses')->cascadeOnDelete();
            $table->string('certificate_code', 30)->unique();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['tenant_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_certificates');
        Schema::dropIfExists('partner_lesson_progress');
        Schema::dropIfExists('partner_lessons');
        Schema::dropIfExists('partner_courses');
        Schema::dropIfExists('partner_resources');
        Schema::dropIfExists('partner_withdrawals');
        Schema::dropIfExists('partner_commissions');
        Schema::dropIfExists('partner_ranks');
    }
};
