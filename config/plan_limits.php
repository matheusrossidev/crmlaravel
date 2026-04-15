<?php

declare(strict_types=1);

/**
 * Source of truth pros recursos limitáveis por plano.
 *
 * Consumidores:
 *  - App\Services\PlanLimitChecker::getResourceConfig() — enforcement runtime
 *  - App\Http\Controllers\StripeWebhookController::handleCheckoutCompleted() — sincroniza tenant.max_* do features_json
 *  - App\Http\Controllers\Master\PlanController — UI de edição de planos (render dinâmico)
 *  - App\Http\Controllers\Master\TenantController — overrides individuais
 *  - App\Console\Commands\BackfillTenantLimits — reconciliação
 *
 * Adicionar novo recurso limitável: 1 entrada aqui + migration com coluna max_<key>.
 *
 * Schema de cada entrada:
 *  - label   : texto exibido na UI do master
 *  - column  : nome da coluna na tabela `tenants` (shadow copy do plano)
 *  - model   : FQCN do Model usado pra count
 *  - noun    : substantivo plural usado na mensagem de erro ("Limite de X <noun> atingido")
 *
 * Convenção: `null` ou `0` na coluna => ilimitado.
 */

return [
    'users' => [
        'label'  => 'Usuários',
        'column' => 'max_users',
        'model'  => \App\Models\User::class,
        'noun'   => 'usuários',
    ],
    'leads' => [
        'label'  => 'Leads',
        'column' => 'max_leads',
        'model'  => \App\Models\Lead::class,
        'noun'   => 'leads',
    ],
    'pipelines' => [
        'label'  => 'Pipelines',
        'column' => 'max_pipelines',
        'model'  => \App\Models\Pipeline::class,
        'noun'   => 'pipelines',
    ],
    'custom_fields' => [
        'label'  => 'Campos extras',
        'column' => 'max_custom_fields',
        'model'  => \App\Models\CustomFieldDefinition::class,
        'noun'   => 'campos personalizados',
    ],
    'departments' => [
        'label'  => 'Departamentos',
        'column' => 'max_departments',
        'model'  => \App\Models\Department::class,
        'noun'   => 'departamentos',
    ],
    'chatbot_flows' => [
        'label'  => 'Fluxos chatbot',
        'column' => 'max_chatbot_flows',
        'model'  => \App\Models\ChatbotFlow::class,
        'noun'   => 'fluxos de chatbot',
    ],
    'ai_agents' => [
        'label'  => 'Agentes IA',
        'column' => 'max_ai_agents',
        'model'  => \App\Models\AiAgent::class,
        'noun'   => 'agentes de IA',
    ],
    'whatsapp_instances' => [
        'label'  => 'Instâncias WhatsApp',
        'column' => 'max_whatsapp_instances',
        'model'  => \App\Models\WhatsappInstance::class,
        'noun'   => 'números de WhatsApp',
    ],
    'automations' => [
        'label'  => 'Automações',
        'column' => 'max_automations',
        'model'  => \App\Models\Automation::class,
        'noun'   => 'automações',
    ],
    'nurture_sequences' => [
        'label'  => 'Sequências de nurture',
        'column' => 'max_nurture_sequences',
        'model'  => \App\Models\NurtureSequence::class,
        'noun'   => 'sequências de nurture',
    ],
    'forms' => [
        'label'  => 'Formulários',
        'column' => 'max_forms',
        'model'  => \App\Models\Form::class,
        'noun'   => 'formulários',
    ],
    'whatsapp_templates' => [
        'label'  => 'Templates HSM',
        'column' => 'max_whatsapp_templates',
        'model'  => \App\Models\WhatsappTemplate::class,
        'noun'   => 'templates HSM',
    ],
];
