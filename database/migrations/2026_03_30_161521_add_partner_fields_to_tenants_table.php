<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('cnpj', 20)->nullable()->after('phone');
            $table->string('website', 191)->nullable()->after('cnpj');
            $table->string('city', 100)->nullable()->after('website');
            $table->string('state', 2)->nullable()->after('city');
            $table->string('segment', 50)->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['cnpj', 'website', 'city', 'state', 'segment']);
        });
    }
};
