---
type: ops
status: active
related: ["[[Master Panel]]"]
tags: [ops, master, super-admin]
---

# Toolbox Master

## O que é
14 ferramentas de manutenção em `/master/ferramentas` (super_admin only). Cada uma é uma operação destrutiva ou de conserto que não vale exportar pra UI dos tenants.

## Ferramentas

| Tool | Função |
|---|---|
| `sync-group-names` | Sincroniza nomes de grupos via WAHA |
| `clear-leads` | Apaga todos os leads do tenant |
| `clear-cache` | Limpa cache Redis |
| `fix-unread-counts` | Recalcula contadores de não-lidas |
| `reset-password` | Reset senha de usuário |
| `wa-status` | Verifica status da instância WhatsApp |
| `close-conversations` | Fecha conversas em batch |
| `cleanup-lid-conversations` | Remove conversas com LID sem phone |
| `resolve-lid-conversations` | Tenta resolver LID→phone |
| `reimport-wa-history` | Reimporta histórico do WhatsApp |
| `reimport-empty-conversations` | Reimporta conversas sem mensagens |
| `sync-profile-pictures` | Sincroniza fotos de perfil |
| `export-tenant-stats` | Exporta estatísticas do tenant |
| `check-user-account` | Valida dados do usuário |

## Comandos artisan equivalentes (não-UI)
Algumas dessas ferramentas têm também command CLI:
- `instagram:repair-instances` ([[2026-04-08 Instagram getProfile mudanca silenciosa Meta]])
- `instagram:repair-contacts` (idem)
- `whatsapp:repair-pictures`
- `tags:backfill` ([[Tags polimorficas (refactor)]])

## Princípios
- **Toda tool é tenant-scoped** — operação só afeta um tenant por vez
- **Toda tool tem confirm dialog** — operações destrutivas exigem digitar nome do tenant
- **Logs em `storage/logs/master-tools-*.log`**
- **Idempotentes onde possível** — rodar 2x não dobra o efeito
