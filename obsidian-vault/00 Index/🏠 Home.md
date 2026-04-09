---
type: index
tags: [home]
---

# 🏠 Syncro CRM — Second Brain

> Vault Obsidian com a memória estruturada da plataforma. Tudo conectado por backlinks pra que o grafo conte uma história.

## Atalhos rápidos

- **Mapa geral:** [[Map of Content]]
- **Módulos da plataforma:** [[10 Modules]]
- **Arquitetura:** [[20 Architecture]]
- **Modelos do banco:** [[30 Models]]
- **Services:** [[40 Services]]
- **Bugs e decisões:** [[50 Bugs & Decisions]]
- **Operações:** [[60 Operations]]
- **Integrações externas:** [[70 Integrations]]
- **Daily notes:** [[80 Daily]]

## Como usar este vault

1. **Procure por nome** — Cmd+O / Ctrl+O abre o switcher
2. **Procure por conteúdo** — Cmd+Shift+F / Ctrl+Shift+F
3. **Veja conexões** — Cmd+G / Ctrl+G abre o graph view
4. **Backlinks** — toda nota tem painel de backlinks à direita
5. **Tags** — `#module`, `#bug`, `#decision`, `#service`, `#model`, `#integration`

## Convenções de nomes

- **Modules:** verbo + substantivo curto (`Lead Scoring`, `Chat Inbox`)
- **Models:** PascalCase como na classe PHP (`Lead`, `WhatsappConversation`)
- **Services:** PascalCase + sufixo `Service`
- **Bugs:** `YYYY-MM-DD descrição curta` (ordena cronologicamente)
- **Decisions:** `ADR — descrição` (Architecture Decision Record)

## Links pra documentação canônica

- [[Map of Content]] — índice manual organizado por tópico
- `CLAUDE.md` no root do projeto — fonte de verdade pro Claude/IA
- `docs/*.html` — docs auto-geradas mais antigas (em conversão pra .md)

## Auto-sync

Os arquivos em `30 Models/`, `40 Services/` e referências de rotas podem ser **regenerados** automaticamente a partir do código:

```bash
php artisan obsidian:sync
```

Roda esse comando depois de mudanças grandes em models/services pra manter as notas geradas em dia. Notas escritas à mão (modules, bugs, decisions) NÃO são sobrescritas.
