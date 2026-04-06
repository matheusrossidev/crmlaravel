<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['slug' => 'whatsapp',          'label' => 'WhatsApp',                    'description' => 'Chat inbox, automações e agentes IA via WhatsApp',     'is_enabled_globally' => true,  'sort_order' => 1],
            ['slug' => 'instagram',         'label' => 'Instagram DM',                'description' => 'Direct messages e automações de comentários',          'is_enabled_globally' => true,  'sort_order' => 2],
            ['slug' => 'facebook_leadads',  'label' => 'Formulários Lead Ads',        'description' => 'Captura de leads de formulários Facebook/Instagram',   'is_enabled_globally' => false, 'sort_order' => 3],
            ['slug' => 'google_calendar',   'label' => 'Google Calendar',             'description' => 'Sincronização de eventos e lembretes',                 'is_enabled_globally' => true,  'sort_order' => 4],
            ['slug' => 'chatbot',           'label' => 'Chatbot Builder',             'description' => 'Construtor visual de fluxos de chatbot',               'is_enabled_globally' => true,  'sort_order' => 5],
            ['slug' => 'ai_agents',         'label' => 'Agentes de IA',              'description' => 'Agentes inteligentes com memória e tools',              'is_enabled_globally' => true,  'sort_order' => 6],
            ['slug' => 'campaigns',         'label' => 'Campanhas',                   'description' => 'Sync e relatórios de campanhas Facebook/Google Ads',   'is_enabled_globally' => true,  'sort_order' => 7],
            ['slug' => 'nurture_sequences', 'label' => 'Sequências de Nutrição',     'description' => 'Automação de follow-up por etapas',                    'is_enabled_globally' => true,  'sort_order' => 8],
            ['slug' => 'nps_surveys',       'label' => 'Pesquisas NPS',              'description' => 'Net Promoter Score e pesquisas de satisfação',          'is_enabled_globally' => true,  'sort_order' => 9],
            ['slug' => 'website_chat',      'label' => 'Chat para Website',          'description' => 'Widget de chat para sites com chatbot',                 'is_enabled_globally' => true,  'sort_order' => 10],
            ['slug' => 'whatsapp_cloud_api','label' => 'WhatsApp Cloud API (Meta)',  'description' => 'Conexão via API oficial da Meta com modo Coexistência (use no celular e API ao mesmo tempo)', 'is_enabled_globally' => false, 'sort_order' => 11],
        ];

        foreach ($features as $feature) {
            FeatureFlag::updateOrCreate(
                ['slug' => $feature['slug']],
                $feature,
            );
        }
    }
}
