<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make platform (ENUM) and external_id nullable via raw statement
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN platform ENUM('facebook','google') NULL DEFAULT NULL");
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN external_id VARCHAR(191) NULL DEFAULT NULL");
        }

        // Drop the old unique constraint (tenant_id, platform, external_id)
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'platform', 'external_id']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            // 'manual' | 'facebook' | 'google'
            $table->string('type', 20)->default('manual')->after('external_id');
            // facebook: awareness|traffic|engagement|leads|app_promotion|sales
            // google: search|display|video|shopping|performance_max|smart
            $table->string('campaign_type', 50)->nullable()->after('type');

            // UTM parameters stored on the campaign (used to generate tracking links)
            $table->string('utm_source', 100)->nullable()->after('campaign_type');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 200)->nullable()->after('utm_medium');
            $table->string('utm_term', 200)->nullable()->after('utm_campaign');
            $table->string('utm_content', 200)->nullable()->after('utm_term');

            // Landing page or destination URL for generating full UTM link
            $table->string('destination_url', 2000)->nullable()->after('utm_content');

            // Unique index only for synced campaigns (platform + external_id both non-null)
            $table->index(['tenant_id', 'utm_campaign'], 'campaigns_tenant_utm_campaign_index');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndexIfExists('campaigns_tenant_utm_campaign_index');
            $table->dropColumn([
                'type', 'campaign_type',
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'destination_url',
            ]);
        });

        // Restore NOT NULL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN platform ENUM('facebook','google') NOT NULL");
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN external_id VARCHAR(191) NOT NULL");
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->unique(['tenant_id', 'platform', 'external_id']);
        });
    }
};
