---
type: module
status: active
related: ["[[ChatbotFlow]]", "[[ChatbotFlowNode]]", "[[ProcessChatbotStep]]"]
files:
  - resources/js/chatbot-builder.jsx
  - app/Jobs/ProcessChatbotStep.php
  - app/Models/ChatbotFlow.php
last_review: 2026-04-09
tags: [module, chatbot, react]
---

# Chatbot Builder

## O que é
Builder visual drag-drop (React Flow) para criar fluxos de chatbot multi-canal (WhatsApp + Instagram + Website). Único bloco React no projeto que usa Vite.

## Status
- ✅ React Flow com drag-drop
- ✅ Multi-canal (cores diferentes por canal)
- ✅ Trigger por keyword OU por comentário Instagram
- ✅ Variables de sessão (interpolação `{{nome}}`)
- ✅ Tracking de `completions_count`
- ✅ Cards (carousel website) + audio (WhatsApp only)

## Node types
| Tipo | Função |
|---|---|
| `message` | Texto/imagem/áudio |
| `input` | Pergunta + branches (lista WA, quick replies IG, texto Web) |
| `cards` | Carousel de cards (Website only) |
| `condition` | Avalia variável (equals, contains, gt, lt) |
| `action` | change_stage, add_tag, assign_human, send_webhook, set_custom_field |
| `delay` | Pausa N segundos |
| `end` | Mensagem final, limpa fluxo |

## Execução ([[ProcessChatbotStep]])
- Max **30 iterações por mensagem** (previne loops infinitos)
- 3s de delay entre mensagens (simula digitação)
- Variables persistidas em `conversation.chatbot_variables` (JSON)
- Multi-canal: WhatsApp `sendList()`, Instagram quick replies, Website texto/cards

## Decisões
- [[ADR — React Flow no chatbot builder (único React no projeto)]]
