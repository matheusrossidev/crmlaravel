---
type: lesson
status: active
date: 2026-04-08
related: ["[[2026-04-08 Instagram getProfile mudanca silenciosa Meta]]"]
tags: [lesson, principle]
---

# Lição: Verificar empiricamente antes de declarar limitação

## A regra
NUNCA declarar que algo é "limitação técnica irremovível", "impossível no fluxo X", ou "não funciona em produto Y" sem ter testado empiricamente contra dado **real** do banco em **pelo menos 2 cenários diferentes**.

Especificamente: antes de escrever comentários de código dizendo "Meta não retorna esse campo nesse fluxo" ou similar, **rodar o endpoint e ver o que volta**.

## Por que essa regra existe

Em **08/04/2026** insisti por várias rodadas que o endpoint `GET /{IGSID}?fields=name,username,profile_pic` "não funciona no fluxo Instagram API with Instagram Login". Escrevi comentários no código, planos elaborados, removi o método. Tudo baseado em **UM smoke test contra UMA instance** que falhava com erro 100/33.

O user me mandou checar contra o banco. Tinha **535 conversations com `contact_picture_url` preenchida** (URLs de `cdninstagram.com`), populadas pelo próprio código da plataforma. Quando testei contra a instance #34 (criada 27/03), o endpoint retornou tudo perfeito.

A diferença real: a Meta mudou silenciosamente o comportamento entre ~27/03 e 01/04/2026. Instances/IGSIDs criados depois retornam 100/33; criados antes continuam funcionando. **A solução certa era hybrid** — e eu só cheguei nela depois de horas de achismo, commits errados, e o user gritando "VOCÊ TÁ ERRADO" várias vezes.

Custou tempo, contexto e paciência do user.

## Como aplicar (próxima vez)

Antes de escrever qualquer comentário do tipo "X não funciona em Y" ou "limitação do flow Z":

1. **Query no banco** procurando contraevidência (ex: `WHERE foo IS NOT NULL`)
2. Se encontrar dados, abrir `git log` no arquivo relevante e ver qual commit/código populou aqueles dados — isso é prova viva de que o caminho funciona/funcionou
3. Testar o endpoint via tinker contra **MÚLTIPLAS instances/tenants/datas** diferentes antes de tirar conclusão
4. Se uma instance funciona e outra falha, hipótese padrão é "**scope/permissão diferente**" ou "**Meta mudou silenciosamente**" — NÃO "endpoint não existe nesse fluxo"
5. Sempre preferir **solução hybrid** (tenta caminho A, fallback pra B) em vez de declarar caminho A morto e remover ele
6. Em integrações Meta/Facebook/Google: doc oficial às vezes está desatualizada ou contradiz o que a API realmente retorna. **Dado real do banco > documentação oficial.**

## Aplicações
- [[2026-04-08 Instagram getProfile mudanca silenciosa Meta]]
