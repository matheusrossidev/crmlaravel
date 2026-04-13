<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('type', 20)->default('classic'); // classic, conversational, multistep, popup, embed
            $table->json('fields')->nullable();
            $table->json('mappings')->nullable();

            // Lead destination
            $table->foreignId('pipeline_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained('pipeline_stages')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_utm', 100)->nullable();

            // Confirmation
            $table->string('confirmation_type', 20)->default('message'); // message, redirect
            $table->text('confirmation_value')->nullable();

            // Post-submission actions
            $table->json('notify_emails')->nullable();
            $table->foreignId('sequence_id')->nullable()->constrained('nurture_sequences')->nullOnDelete();
            $table->unsignedBigInteger('list_id')->nullable();
            $table->boolean('send_whatsapp_welcome')->default(false);
            $table->boolean('create_task')->default(false);
            $table->unsignedInteger('task_days_offset')->default(1);

            // Limits
            $table->unsignedInteger('max_submissions')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('views_count')->default(0);

            // Branding
            $table->string('logo_url', 500)->nullable();
            $table->string('logo_alignment', 10)->default('center'); // left, center, right
            $table->string('brand_color', 10)->default('#0085f3');
            $table->string('background_color', 10)->default('#ffffff');
            $table->string('input_bg_color', 10)->default('#ffffff');
            $table->string('input_text_color', 10)->default('#1a1d23');
            $table->string('label_color', 10)->default('#374151');
            $table->string('input_border_color', 10)->default('#e5e7eb');
            $table->string('button_color', 10)->default('#0085f3');
            $table->string('button_text_color', 10)->default('#ffffff');
            $table->string('font_family', 50)->default('Inter');
            $table->unsignedTinyInteger('border_radius')->default(8);

            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
