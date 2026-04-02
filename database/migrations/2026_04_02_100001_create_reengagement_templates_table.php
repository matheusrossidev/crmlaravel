<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reengagement_templates', function (Blueprint $table) {
            $table->id();
            $table->string('stage', 10);       // 7d, 14d, 30d
            $table->string('channel', 10);     // email, whatsapp
            $table->string('subject')->nullable(); // email subject only
            $table->text('body');              // template with {{variables}}
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['stage', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reengagement_templates');
    }
};
