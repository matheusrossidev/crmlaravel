# Syncro CRM — Second Brain (Obsidian Vault)

> Vault Obsidian com a memória estruturada da plataforma. Criado em 2026-04-09.

## Como abrir

1. Instale o [Obsidian](https://obsidian.md) (free, multiplataforma)
2. Abra o Obsidian → "Open folder as vault" → selecione `obsidian-vault/`
3. Pronto. A nota inicial é `00 Index/🏠 Home.md`.

## Por que existe

Versão estruturada (e linkada) do conhecimento da plataforma. Complementa o `CLAUDE.md` (que é fonte canônica linear) com:

- **Backlinks** — toda nota mostra quem a referencia
- **Graph view** — mapa visual das conexões entre módulos, models, services, decisões
- **Tags consistentes** (`#module`, `#bug`, `#decision`, `#service`, `#model`, `#integration`, `#lesson`)
- **Decision log** (RCAs e ADRs) que vai crescendo a cada bug/decisão
- **Daily notes** opcionais pra "diário de bordo"

## Estrutura

```
obsidian-vault/
├── 00 Index/                  ← Home + Map of Content
├── 10 Modules/                ← 16 módulos da plataforma (escritos à mão)
├── 20 Architecture/           ← Multi-tenant, Reverb, Webhook Pipeline, etc
├── 30 Models/                 ← AUTO-GERADO (90 models do app/Models/)
├── 40 Services/               ← AUTO-GERADO (37 services do app/Services/)
├── 50 Bugs & Decisions/       ← RCAs, ADRs, lessons learned (à mão)
├── 60 Operations/             ← Deploy, comandos VPS, toolbox, Routes (auto)
├── 70 Integrations/           ← Meta, WAHA, Asaas, Stripe, Google Calendar, Agno
├── 80 Daily/                  ← Daily notes (opcional)
└── 99 Templates/              ← Templates pra criar notas novas com padrão
```

## Auto-sync

Notas em **`30 Models/`**, **`40 Services/`** e **`60 Operations/Routes.md`** são **auto-geradas** por:

```bash
php artisan obsidian:sync
```

Variantes:
```bash
php artisan obsidian:sync --models     # só models
php artisan obsidian:sync --services   # só services
php artisan obsidian:sync --routes     # só rotas
```

Toda nota auto-gerada tem `auto_generated: true` no frontmatter — **não edite à mão**, vai ser sobrescrito. Pra adicionar contexto, crie uma nota separada na pasta correspondente (ex: `30 Models/Lead — notas.md`) ou nas pastas escritas à mão (`10 Modules/`, `50 Bugs & Decisions/`).

**Notas escritas à mão NUNCA são tocadas pelo sync.**

## Convenções

### Nomes de arquivo
- **Modules:** `Lead Scoring.md`, `Chat Inbox.md` (verbo + substantivo curto)
- **Models:** PascalCase como na classe PHP (`Lead.md`, `WhatsappConversation.md`)
- **Services:** PascalCase + sufixo `Service`
- **Bugs:** `YYYY-MM-DD descrição curta.md` (ordena cronologicamente)
- **Decisions (ADR):** `ADR — descrição.md`
- **Lessons:** título descritivo (`CSS important sempre ganha de inline style sem important.md`)

### Frontmatter padrão
```yaml
---
type: module | model | service | bug | decision | integration | lesson | architecture
status: active | deprecated | half-implemented | resolved
related: ["[[Outra Nota]]"]
files:
  - app/Path/Para/Arquivo.php
last_review: YYYY-MM-DD
tags: [tag1, tag2]
---
```

### Backlinks
Use `[[Nome da Nota]]` em qualquer lugar — Obsidian indexa automaticamente. O painel "Backlinks" à direita mostra quem cita a nota atual.

## Como contribuir (workflow recomendado)

### Quando você fixa um bug
1. Cria nota em `50 Bugs & Decisions/YYYY-MM-DD descrição.md` usando template `99 Templates/Bug RCA.md`
2. Linka pros módulos/services afetados via `[[Nome]]`
3. Linka pros commits

### Quando você toma uma decisão arquitetural
1. Cria nota `50 Bugs & Decisions/ADR — descrição.md` usando template `Decision Record.md`
2. Documente alternativas consideradas
3. Linke nos módulos relevantes

### Quando você adiciona um módulo novo
1. Cria nota em `10 Modules/Nome do Modulo.md` usando template `Module.md`
2. Atualiza `00 Index/Map of Content.md`
3. Roda `php artisan obsidian:sync` se criou models/services novos

### Quando você muda código de Models/Services
- Roda `php artisan obsidian:sync` pra atualizar as notas auto-geradas
- Não precisa mexer nas notas a mão

## Versionado

Esse vault é **commitado no git**. As notas escritas à mão (`10 Modules/`, `20 Architecture/`, `50 Bugs & Decisions/`, `60 Operations/`, `70 Integrations/`) viram parte da história do projeto. Outros devs/IAs futuras herdam o conhecimento.

**Não versionado** (ver `.gitignore`):
- `obsidian-vault/.obsidian/workspace.json` — preferências de UI por dev
- `obsidian-vault/.obsidian/cache/` — cache local
- `obsidian-vault/.trash/` — notas deletadas (apaga manualmente)
- `obsidian-vault/80 Daily/` — daily notes pessoais (opcional ignorar — por enquanto NÃO está no gitignore)

## Plugins core ativados

- File explorer · Search · Switcher · Graph · Backlinks · Outgoing links
- Tag pane · Properties (frontmatter) · Page preview · Outline
- Daily notes (template `99 Templates/Daily.md` em pasta `80 Daily/`)
- Templates (pasta `99 Templates/`)
- Canvas · Bookmarks · Word count · File recovery
- Slash commands · Markdown importer

## Troubleshooting

**As notas auto-geradas estão desatualizadas?**
→ `php artisan obsidian:sync`

**Quero criar uma nota tipo "X"?**
→ Cmd/Ctrl+N → frontmatter manual OU Cmd+P → "Insert template" → escolha o template

**Graph view tá poluído?**
→ Arquivo `.obsidian/graph.json` já tem color groups por pasta. Ajuste filtros de busca no painel do graph.

**Esqueci o que tem disponível?**
→ Abre `00 Index/Map of Content.md`
