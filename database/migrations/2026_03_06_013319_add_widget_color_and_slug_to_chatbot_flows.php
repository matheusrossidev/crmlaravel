<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_flows', function (Blueprint $table) {
            $table->string('widget_color', 10)->default('#0085f3')->after('widget_type');
            $table->string('slug', 191)->nullable()->after('name');

            $table->unique(['tenant_id', 'slug'], 'chatbot_flows_tenant_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_flows', function (Blueprint $table) {
            $table->dropUnique('chatbot_flows_tenant_slug_unique');
            $table->dropColumn(['widget_color', 'slug']);
        });
    }
};
