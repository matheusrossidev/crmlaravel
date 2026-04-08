<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\FeatureFlag;

/**
 * Catálogo de integrações disponíveis no Syncro CRM.
 *
 * Single source of truth pra metadados de cada integração que aparece em
 * /configuracoes/integracoes. Mesma filosofia do PipelineTemplates: hardcoded
 * em PHP, sem tabela/migration. Editar o catálogo = editar este arquivo.
 *
 * Cada integração tem:
 *  - slug:               chave única (usada em data-attrs do JS e como ID dos panels)
 *  - name:               i18n key do nome curto
 *  - description_short:  i18n key da descrição (~120 chars, mostrada no card)
 *  - description_long:   i18n key da descrição longa (parágrafo completo, modal)
 *  - icon:               classe Bootstrap Icons (`bi bi-X`)
 *  - icon_bg:            cor de fundo do círculo do ícone
 *  - category:           slug da categoria (ver CATEGORIES abaixo)
 *  - type:               'native' | 'partner' | 'beta'
 *  - plans:              array de slugs de planos onde a integração está disponível
 *  - feature_flag:       slug de FeatureFlag (esconde card se desabilitada) ou null
 *  - panel_partial:      caminho do partial Blade renderizado no lado direito do modal
 */
final class IntegrationCatalog
{
    /**
     * Categorias disponíveis. As keys são slugs usadas em `category` de cada
     * integração e em `data-category` dos cards no JS. Os valores são as i18n
     * keys (renderizadas via `__('integrations.cat_<slug>')`).
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'all'          => 'integrations.cat_all',
            'messaging'    => 'integrations.cat_messaging',
            'lead_capture' => 'integrations.cat_lead_capture',
            'calendar'     => 'integrations.cat_calendar',
        ];
    }

    /**
     * Catálogo completo de integrações suportadas.
     *
     * @return array<string, array{
     *   slug: string,
     *   name: string,
     *   description_short: string,
     *   description_long: string,
     *   icon: string,
     *   icon_bg: string,
     *   category: string,
     *   type: string,
     *   plans: array<int, string>,
     *   feature_flag: ?string,
     *   panel_partial: string
     * }>
     */
    public static function all(): array
    {
        return [
            'whatsapp_waha' => [
                'slug'              => 'whatsapp_waha',
                'name'              => 'integrations.cat_whatsapp_waha_name',
                'description_short' => 'integrations.cat_whatsapp_waha_short',
                'description_long'  => 'integrations.cat_whatsapp_waha_long',
                'icon'              => 'bi-whatsapp',
                'icon_bg'           => '#25D366',
                'image'             => '/images/svg/whatsapp.svg',
                'category'          => 'messaging',
                'type'              => 'native',
                'plans'             => ['free', 'starter', 'pro', 'enterprise'],
                'feature_flag'      => null,
                'panel_partial'     => 'tenant.settings.integrations.panels.whatsapp-waha',
            ],

            'whatsapp_cloud' => [
                'slug'              => 'whatsapp_cloud',
                'name'              => 'integrations.cat_whatsapp_cloud_name',
                'description_short' => 'integrations.cat_whatsapp_cloud_short',
                'description_long'  => 'integrations.cat_whatsapp_cloud_long',
                'icon'              => 'bi-whatsapp',
                'icon_bg'           => '#0085f3',
                'image'             => '/images/svg/whatsapp.svg',
                'category'          => 'messaging',
                'type'              => 'beta',
                'plans'             => ['pro', 'enterprise'],
                'feature_flag'      => 'whatsapp_cloud_api',
                'panel_partial'     => 'tenant.settings.integrations.panels.whatsapp-cloud',
            ],

            'instagram' => [
                'slug'              => 'instagram',
                'name'              => 'integrations.cat_instagram_name',
                'description_short' => 'integrations.cat_instagram_short',
                'description_long'  => 'integrations.cat_instagram_long',
                'icon'              => 'bi-instagram',
                'icon_bg'           => '#E4405F',
                'image'             => '/images/svg/instagram.svg',
                'category'          => 'messaging',
                'type'              => 'native',
                'plans'             => ['starter', 'pro', 'enterprise'],
                'feature_flag'      => null,
                'panel_partial'     => 'tenant.settings.integrations.panels.instagram',
            ],

            'facebook_leadads' => [
                'slug'              => 'facebook_leadads',
                'name'              => 'integrations.cat_fb_leadads_name',
                'description_short' => 'integrations.cat_fb_leadads_short',
                'description_long'  => 'integrations.cat_fb_leadads_long',
                'icon'              => 'bi-facebook',
                'icon_bg'           => '#1877F2',
                'image'             => '/images/svg/facebook.svg',
                'category'          => 'lead_capture',
                'type'              => 'native',
                'plans'             => ['pro', 'enterprise'],
                'feature_flag'      => 'facebook_leadads',
                'panel_partial'     => 'tenant.settings.integrations.panels.facebook-leadads',
            ],

            'google_calendar' => [
                'slug'              => 'google_calendar',
                'name'              => 'integrations.cat_gcal_name',
                'description_short' => 'integrations.cat_gcal_short',
                'description_long'  => 'integrations.cat_gcal_long',
                'icon'              => 'bi-calendar3',
                'icon_bg'           => '#4285F4',
                'image'             => '/images/svg/google-calendar.svg',
                'category'          => 'calendar',
                'type'              => 'native',
                'plans'             => ['starter', 'pro', 'enterprise'],
                'feature_flag'      => null,
                'panel_partial'     => 'tenant.settings.integrations.panels.google-calendar',
            ],

            'whatsapp_button' => [
                'slug'              => 'whatsapp_button',
                'name'              => 'integrations.cat_wabtn_name',
                'description_short' => 'integrations.cat_wabtn_short',
                'description_long'  => 'integrations.cat_wabtn_long',
                'icon'              => 'bi-chat-dots-fill',
                'icon_bg'           => '#10b981',
                'image'             => '/images/svg/whatsapp.svg',
                'category'          => 'lead_capture',
                'type'              => 'native',
                'plans'             => ['free', 'starter', 'pro', 'enterprise'],
                'feature_flag'      => null,
                'panel_partial'     => 'tenant.settings.integrations.panels.whatsapp-button',
            ],
        ];
    }

    /**
     * Filtra o catálogo respeitando as feature flags do tenant.
     * Integrações com `feature_flag` definida só aparecem se a flag estiver
     * habilitada (globalmente ou pra esse tenant).
     *
     * @param int $tenantId
     * @return array<string, array>
     */
    public static function availableForTenant(int $tenantId): array
    {
        return array_filter(self::all(), function (array $integration) use ($tenantId): bool {
            $flag = $integration['feature_flag'] ?? null;
            if ($flag === null) {
                return true;
            }
            return FeatureFlag::isEnabled($flag, $tenantId);
        });
    }

    /**
     * Encontra uma integração pelo slug. Retorna null se não existe.
     */
    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }
}
