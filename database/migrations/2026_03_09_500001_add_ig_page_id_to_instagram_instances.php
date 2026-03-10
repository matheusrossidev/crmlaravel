<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instagram_instances', function (Blueprint $table) {
            $table->string('ig_page_id')->nullable()->after('ig_business_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('instagram_instances', function (Blueprint $table) {
            $table->dropColumn('ig_page_id');
        });
    }
};
