---
type: module
status: active
related: ["[[Leads & CRM]]", "[[Automations]]", "[[Nurture Sequences]]"]
files:
  - app/Models/Form.php
  - app/Models/FormSubmission.php
  - app/Http/Controllers/Tenant/Forms/FormController.php
  - app/Http/Controllers/Tenant/Forms/FormBuilderController.php
  - app/Http/Controllers/FormPublicController.php
  - app/Services/Forms/FormSubmissionService.php
  - app/Services/Forms/FormLeadCreator.php
  - app/Services/Forms/FormNotifier.php
  - resources/views/forms/_phone-lib.blade.php
last_review: 2026-04-17
tags: [module, forms, lead-capture]
---

# Forms module

## O que é
Módulo nativo de captura de leads com 3 tipos (classic, conversational, multistep). Permite criar formulários pela UI e embedar em sites externos via SDK JS sem iframe. Substituiu integrações 3rd-party em abril/2026.

## Status
- ✅ 3 tipos: classic (página única), conversational (Typeform-style), multistep (wizard)
- ✅ Builder drag-drop sem React (JS puro) — sidebar + canvas + config panel
- ✅ Conditional logic (5 operators: equals/not_equals/contains/not_empty/is_empty)
- ✅ Color presets + font + background + logo upload
- ✅ Mapping field → lead.name/phone/email/company/value/tags/source/notes/custom:N
- ✅ Post-actions: pipeline+stage destino, assigned user, nurture sequence enroll, static list, create task
- ✅ **SDK JS nativo sem iframe** (Fase 3) — cola `<script>` e renderiza diretamente no DOM do cliente
- ✅ 3 modos de embed: hosted (link `/f/slug`), inline (`data-mode="inline"`), popup (`data-mode="popup"` com triggers immediate/time/scroll/exit)
- ✅ **Phone mask internacional** (2026-04-17) — intl-tel-input v25 via CDN jsDelivr, bandeiras emoji, formatAsYouType, strictMode, validação E.164 no submit. Config por form: `default_country` + `allowed_countries`
- ✅ Distribution analytics — `views_count_hosted` / `views_count_inline` / `views_count_popup` separados

## Fluxo de submissão
```
Form submit (hosted view OU SDK embed)
  → POST /api/form/{slug}/submit (ou /f/{slug} pra hosted)
    → FormPublicController::submit
      → FormSubmissionService::process(Form, data, ip, ua, embedMode, referrerUrl)
        → honeypot check (_website_url field invisível)
        → FormLeadCreator::create (com PlanLimitChecker + UTM capture + tags + custom fields)
        → FormSubmission row cria com embed_mode + referrer_url
        → post-actions: nurture enroll, list add, task create
        → FormNotifier::notify (email + WhatsApp welcome opcional)
      → redirect OU confirmação textual conforme Form.confirmation_type
```

## SDK JS embed (inline + popup)
Endpoint `/api/form/{slug}.js` gera IIFE self-contained:
- Identifica tag script via `data-form="{slug}"` (resiliente a `async`)
- Fetch `/api/form/{slug}/config.json` (sanitizado — sem expor mappings/notify_emails/assigned_user_id)
- Injeta CSS scoped em `#syncro-form-{id}` (style isolation)
- Renderiza SEM chrome (zero logo, zero "Criado com Syncro" — views hospedadas têm chrome, SDK não)
- Popup: show-once via `localStorage.syncro_form_shown_{id}`
- Submete via `POST /api/form/{slug}/submit` (JSON, sem CSRF — rota fora do middleware web, CORS `*`)
- Track view: `POST /api/form/{slug}/track-view` (1x por session via `sessionStorage`)

## Phone mask internacional
Fields tipo `tel` recebem bandeira + código + máscara automática:
- `intl-tel-input v25` via CDN jsDelivr (bundle WithUtils inclui libphonenumber)
- Bandeira via **emoji nativo** (🇧🇷 🇺🇸 🇵🇹) — sem sprite/PNG
- `formatAsYouType: true` — máscara adapta ao país
- `strictMode: true` — bloqueia caractere inválido enquanto digita
- `countryOrder: ['br', 'us', 'pt']` — populares no topo
- Valor enviado ao backend em E.164 (`+5511912345678`)
- Validação client-side: bloqueia submit se número inválido pro país

Config por form (em `/formularios/{id}/editar` → Avançado):
- `forms.default_country` VARCHAR(2) DEFAULT 'BR'
- `forms.allowed_countries` JSON NULLABLE (ISO-2; null = todos)
- UI com radio "Todas / Só os que eu marcar" + checkboxes

Aplicado em 4 lugares via partial shared `resources/views/forms/_phone-lib.blade.php`:
1. SDK JS embed (`FormPublicController::buildSdkJs`)
2. `public.blade.php` (classic hosted)
3. `public-conversational.blade.php`
4. `public-multistep.blade.php`

## Tabela `forms`
```
name, slug (unique — random suffix 6 chars), type ENUM('classic','conversational','multistep','popup','embed')
fields JSON, mappings JSON, conditional_logic JSON, steps JSON
pipeline_id, stage_id, assigned_user_id, source_utm, confirmation_type, confirmation_value
notify_emails JSON, sequence_id, list_id
send_whatsapp_welcome BOOL, create_task BOOL, task_days_offset INT
max_submissions INT, expires_at TIMESTAMP, is_active BOOL
views_count / views_count_hosted / views_count_inline / views_count_popup
logo_url, logo_alignment, brand_color, background_color, card_color, etc (styling)
default_country VARCHAR(2) DEFAULT 'BR', allowed_countries JSON NULLABLE
widget_trigger ENUM('immediate','time','scroll','exit'), widget_delay, widget_scroll_pct, widget_show_once, widget_position
```

## Tabela `form_submissions`
```
form_id, tenant_id, lead_id (nullable),
data JSON,
embed_mode ENUM('hosted','inline','popup'),
referrer_url, ip_address, user_agent,
submitted_at
index (tenant_id, embed_mode)
```

## Convenções
- **"Envios" não "Submissões"** na UI (decisão do user)
- **NUNCA reintroduzir iframe** — SDK nativo é a única via suportada
- **SDK SEMPRE sem chrome** — zero logo, zero título
- Submissões via SDK não passam pelo middleware `web` (sem CSRF) — usam CORS `*`
- Config JSON do SDK sanitiza: nunca expor `mappings`, `notify_emails`, `assigned_user_id`, `pipeline_id`, `stage_id`, `sequence_id`, `list_id`

## Decisões / RCAs
- [[ADR — SDK nativo sem iframe]]
- [[ADR — Phone mask internacional com intl-tel-input v25]]
- [[ADR — "Envios" em vez de "Submissões"]]
