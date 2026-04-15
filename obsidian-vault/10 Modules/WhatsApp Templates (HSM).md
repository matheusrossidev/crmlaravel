---
type: module
status: active
related: ["[[WhatsApp Cloud API]]", "[[AI Agents]]", "[[Automations]]", "[[Chat Inbox]]"]
files:
  - app/Models/WhatsappTemplate.php
  - app/Services/Whatsapp/WhatsappTemplateService.php
  - app/Services/WhatsappCloudService.php
  - app/Http/Controllers/Tenant/WhatsappTemplateController.php
  - app/Console/Commands/SyncWhatsappTemplates.php
last_review: 2026-04-17
tags: [module, whatsapp, cloud-api, templates, hsm]
---

# WhatsApp Templates HSM

## O que é
Message Templates HSM da Meta (pré-aprovados). Necessários pra enviar texto fora da **janela de 24h** do Cloud API. WAHA não suporta — módulo só ativo pra instâncias `provider='cloud_api'`.

## Status
- ✅ CRUD local + sync bidirecional com Meta Graph API
- ✅ UI de criação com preview iPhone clay (70/30 form + preview)
- ✅ Inserção de variáveis `{{N}}` via botões (Nome do cliente / Data / Hora / Empresa / Valor / Código / Link / Outro)
- ✅ Upload de mídia de exemplo com resumable upload pra Meta (handle `h:4:...`)
- ✅ Envio manual pelo modal do chat (com detecção de janela 24h)
- ✅ Envio automático via automação (`send_whatsapp_template` action)
- ✅ Envio via follow-up IA (`ai_agents.followup_template_id`)
- ✅ Sync diário (`whatsapp:sync-templates` — 04:00)

## Tabela `whatsapp_templates`
- `tenant_id`, `whatsapp_instance_id` (FK)
- `name` (snake_case, único por WABA), `language` (pt_BR, en_US, es_ES...)
- `category` (MARKETING/UTILITY/AUTHENTICATION)
- `components` (JSON formato Meta: header+body+footer+buttons)
- `sample_variables` (exemplos reais pra revisão Meta)
- `status` (PENDING/APPROVED/REJECTED/PAUSED/DISABLED/IN_APPEAL/PENDING_DELETION)
- `meta_template_id`, `rejected_reason`, `quality_rating`, `last_synced_at`

## Criação
- **`/configuracoes/whatsapp-templates/criar`** — wizard 70/30 com preview iPhone clay à direita
- Botões de variáveis clicáveis (registram label amigável + inserem `{{N}}` no cursor)
- Dropzone pra mídia de exemplo (padrão da plataforma)
- Submit → `WhatsappTemplateService::create` → valida + chama `WhatsappCloudService::uploadToMetaResumable` (se header=IMAGE/VIDEO/DOC) → `createTemplate` via Graph API

## Categorização automática da Meta
Meta **reclassifica** categoria baseado no conteúdo. Template marcado como UTILITY mas com cara promocional vira MARKETING automaticamente. Comportamento oficial ([doc](https://developers.facebook.com/docs/whatsapp/updates-to-pricing/new-template-guidelines#template-category-changes)).

- `syncFromMeta` detecta e loga mudança de categoria
- Info box no `/show` explica pro user ser comportamento Meta

Pra UTILITY ser mantido: body precisa ter dados concretos (data, hora, código, nome), sem soar promocional.

## Envio manual (chat)
Modal no `/chats` (Cloud API only) — "+" → "Template":
1. Lista templates APPROVED filtráveis por search
2. Seleciona → preview ao vivo + inputs de variável
3. Upload de mídia de header (se aplicável)
4. Submit → `WhatsappTemplateService::send` → cria `WhatsappMessage` local (type='template', body=preview formatado, `cloud_message_id` retornado)

**Detecção de janela 24h**: Modal abre automaticamente + input de texto livre desabilita quando última inbound > 24h.

## Envio automático (automação)
3 actions novas em `AutomationEngine` (só visíveis se tenant tem Cloud):
- `send_whatsapp_template` — escolhe template + mapping de variáveis (default: 1=name, 2=company, 3=email; custom via `variable_mappings` no config)
- `send_whatsapp_buttons` — até 3 botões reply (exclusivo Cloud)
- `send_whatsapp_list` — lista interativa (WAHA + Cloud)

## Envio via Follow-up IA
`ai_agents.followup_strategy`:
- `smart` + `followup_template_id` preenchido → dentro da janela manda texto; fora manda template
- `template` → sempre template
- `off` → sem follow-up

## Sync (cron diário)
`whatsapp:sync-templates` (04:00) pra cada instância Cloud:
- Chama Graph API `/{waba_id}/message_templates`
- Upsert por `meta_template_id` (paginação via cursor)
- Remove locais que sumiram da Meta
- Loga re-classificação de categoria pela Meta

## Decisões / RCAs
- [[ADR — Resumable Upload pra sample_handle de mídia]]
- [[ADR — Preview iPhone clay com whatsapp-background.png]]
- [[ADR — Botões de variáveis amigáveis em vez de {{N}} técnico]]
