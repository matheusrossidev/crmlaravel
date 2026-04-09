---
type: lesson
status: active
date: 2026-04-08
related: ["[[2026-04-08 Instagram getProfile mudanca silenciosa Meta]]", "[[ADR — Hybrid Instagram contact fetch]]"]
tags: [lesson, principle]
---

# Lição: Sempre preferir hybrid em vez de remover caminho

## A regra
Quando descobrir que um caminho/endpoint/método "às vezes funciona, às vezes não", **NÃO remova**. Faça **hybrid**: tenta o caminho A primeiro, se falhar fallback pra B.

Mesmo que o caminho A funcione "só em 30% dos casos", esses 30% retornam dados melhores (ex: `name + username + profile_pic` em vez de só `username`). Vale o try-catch.

## Por que essa regra existe
Caso `[[2026-04-08 Instagram getProfile mudanca silenciosa Meta]]`:

- Endpoint `GET /{IGSID}` retornava dados completos
- Meta mudou silenciosamente, alguns IGSIDs passaram a falhar com 100/33
- Minha primeira reação foi "remover esse endpoint, é um caminho morto"
- Realidade: **535 conversations** no banco tinham foto preenchida por esse endpoint. Continuava funcionando pra IGSIDs antigos.
- Solução final: hybrid (`getProfile()` primeiro, `listConversations()+participants` como fallback). Pega o melhor dos dois mundos.

## Como aplicar

1. Quando um endpoint começar a falhar pra alguns casos, **NÃO remova a chamada**
2. Envolve em `try`, captura o erro, dispara fallback no `catch`
3. Logue qual caminho foi usado em cada chamada (pra observability)
4. Documente o fallback no código com comentário explicando POR QUÊ existem dois caminhos
5. Considere "probe" no início (`probeDirectEndpoint`) pra decidir antes de loop
6. Se eventualmente o caminho A morrer 100%, aí sim remove — mas baseado em **dado**, não em achismo

## Exceções
- Se o caminho A é **destrutivo** quando falha de forma silenciosa (ex: cria entidade duplicada), aí remove
- Se manter os 2 caminhos aumenta complexidade O(n²) com muitos consumers, pode valer remover

## Aplicações
- [[2026-04-08 Instagram getProfile mudanca silenciosa Meta]]
- [[ADR — Hybrid Instagram contact fetch]]
