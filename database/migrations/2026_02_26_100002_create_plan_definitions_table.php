<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();       // free, starter, pro, enterprise, partner
            $table->string('display_name', 100);        // "Grátis", "Starter", etc.
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->json('features_json')->nullable();  // limites e toggles de features
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed com planos padrão
        DB::table('plan_definitions')->insert([
            [
                'name'          => 'trial',
                'display_name'  => 'Trial',
                'price_monthly' => 0,
                'features_json' => json_encode([
                    'max_users'         => 2,
                    'max_leads'         => 100,
                    'max_pipelines'     => 1,
                    'ai_agents'         => true,
                    'instagram'         => false,
                    'chatbot'           => false,
                    'ai_tokens_monthly' => 50000,
                ]),
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'free',
                'display_name'  => 'Grátis',
                'price_monthly' => 0,
                'features_json' => json_encode([
                    'max_users'         => 2,
                    'max_leads'         => 200,
                    'max_pipelines'     => 1,
                    'ai_agents'         => false,
                    'instagram'         => false,
                    'chatbot'           => false,
                    'ai_tokens_monthly' => 0,
                ]),
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'starter',
                'display_name'  => 'Starter',
                'price_monthly' => 97,
                'features_json' => json_encode([
                    'max_users'         => 3,
                    'max_leads'         => 1000,
                    'max_pipelines'     => 3,
                    'ai_agents'         => true,
                    'instagram'         => true,
                    'chatbot'           => true,
                    'ai_tokens_monthly' => 500000,
                ]),
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'pro',
                'display_name'  => 'Pro',
                'price_monthly' => 197,
                'features_json' => json_encode([
                    'max_users'         => 10,
                    'max_leads'         => 10000,
                    'max_pipelines'     => 10,
                    'ai_agents'         => true,
                    'instagram'         => true,
                    'chatbot'           => true,
                    'ai_tokens_monthly' => 2000000,
                ]),
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'enterprise',
                'display_name'  => 'Enterprise',
                'price_monthly' => 497,
                'features_json' => json_encode([
                    'max_users'         => 999,
                    'max_leads'         => 999999,
                    'max_pipelines'     => 999,
                    'ai_agents'         => true,
                    'instagram'         => true,
                    'chatbot'           => true,
                    'ai_tokens_monthly' => 10000000,
                ]),
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'partner',
                'display_name'  => 'Parceiro',
                'price_monthly' => 0,
                'features_json' => json_encode([
                    'max_users'         => 999,
                    'max_leads'         => 999999,
                    'max_pipelines'     => 999,
                    'ai_agents'         => true,
                    'instagram'         => true,
                    'chatbot'           => true,
                    'ai_tokens_monthly' => 999999999,
                ]),
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_definitions');
    }
};
