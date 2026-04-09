---
type: lesson
status: active
date: 2026-04-08
related: ["[[2026-04-08 Instagram getProfile mudanca silenciosa Meta]]", "[[Multi-tenant]]"]
tags: [lesson, multi-tenant, security]
---

# Lição: Auto-discovery em multi-tenant é sempre suspeita

## A regra
Em sistema multi-tenant, **NUNCA** escreva código que "encontra um match razoável" e atribui ao primeiro candidato sem ID explícito. Auto-discovery em webhook/cron de tenant resolution é o caminho mais rápido pra cross-tenant contamination.

## Por que essa regra existe
Caso `[[2026-04-08 Instagram getProfile mudanca silenciosa Meta]]`:

`ProcessInstagramWebhook` tinha lógica:
```php
// Auto-descoberta: se nao achar instance pelo entry.id,
// pega a primeira instance conectada com ig_business_account_id NULL
if (! $instance) {
    $instance = InstagramInstance::withoutGlobalScope('tenant')
        ->where('status', 'connected')
        ->whereNull('ig_business_account_id')
        ->orderByDesc('updated_at')
        ->first();
    
    if ($instance) {
        $instance->update(['ig_business_account_id' => $igAccountId]);
    }
}
```

Resultado: webhook do tenant A acabava colando IDs na instance do tenant B. Webhooks de qualquer tenant podiam acabar grudados na instance errada. **Cross-tenant contamination silenciosa**, sem nenhum erro.

Bug existia há semanas, descoberto só quando tentei rastrear mensagens "perdidas" do tenant 12 que apareciam no banco do tenant 18.

## Como aplicar

1. **Webhook handlers**: se `entry.id` não bate com nenhuma instance, **log warning + ignore**, NÃO crie atalho de "primeira instance disponível"
2. **Cron resolution**: `forEach($tenants as $tenant)` é OK, mas dentro do loop nunca cair em "se nada bater, usar primeiro disponível"
3. **OAuth callback**: ID do tenant SEMPRE explícito no `state` parameter, nunca inferido do user logado
4. Pra fixar instances com IDs nulos: comando dedicado que usa o **token DA própria instance** pra chamar `/me` e popular IDs (não usa dado externo do webhook)

Exemplo do padrão certo (ver `RepairInstagramInstances`):
```php
foreach ($instances as $inst) {
    $token = decrypt($inst->access_token);
    $service = new InstagramService($token);
    $me = $service->getMe();
    if (! empty($me['error'])) continue;
    
    // Usa ID do PRÓPRIO token, nunca do webhook
    $userId = $me['user_id'] ?? $me['id'];
    $inst->update(['ig_business_account_id' => $userId]);
}
```

## Sintomas de cross-tenant contamination
- Mensagens "perdidas" — ficam no banco mas no tenant errado
- Conversations duplicadas em tenants diferentes
- Notificações chegando pro user errado
- Logs mostrando webhook resolvido pra tenant que não tem aquela instance

## Aplicações
- [[2026-04-08 Instagram getProfile mudanca silenciosa Meta]]
