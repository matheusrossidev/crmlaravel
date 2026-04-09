---
type: lesson
status: active
date: 2026-04-09
related: ["[[2026-04-09 CSS important matava filtro de templates]]"]
tags: [lesson, css, frontend]
---

# Lição: CSS `!important` sempre ganha de inline style sem `!important`

## A regra
Quando o JavaScript faz `element.style.display = 'none'` (ou qualquer outra prop), está escrevendo um **inline style sem `!important`**. Se o CSS tem `display: flex !important` na regra que cobre o elemento, **o CSS ganha** e o JS é silenciosamente ignorado.

A única forma de bater `!important` via JS é:
```js
element.style.setProperty('display', 'none', 'important');
```

## Por que essa regra existe

Em **09/04/2026**: bug do filtro de nicho na Biblioteca de Templates (Lead Scoring + Automações + Sequências). Cards não sumiam quando o user clicava num filtro. Sem erro no console. State JS funcionava certinho.

Eu refatorei o JS inteiro pensando que era bug do regex que extraía categoria do `onclick`. Commit `d50c7d0`. **Continuou quebrado.**

User mandou screenshot do DevTools mostrando: state JS correto, classe `active` correta, console limpo, **cards visíveis mesmo assim**. Aí abri o CSS e vi:
```css
.tplib-shell .tplib-card {
    display: flex !important;
}
```

E o JS:
```js
card.style.display = show ? '' : 'none';   // ← inline style SEM !important
```

CSS com `!important` ganha. Os cards nunca sumiam. Bug existia desde o commit `f00a1b8` (criação das 3 bibliotecas) e **ninguém viu** porque os 3 lugares compartilham o mesmo partial.

Fix em commit `700b953`:
```js
if (show) {
    card.style.setProperty('display', 'flex', 'important');
} else {
    card.style.setProperty('display', 'none', 'important');
}
```

## Como aplicar

1. **Quando JS "funciona" mas o efeito visual não acontece**: abrir DevTools → Inspector → Computed Styles. A regra que está sendo aplicada vai estar listada com origem (CSS file vs inline style). Se inline está riscado, é `!important` do CSS ganhando.
2. **Antes de refatorar JS pensando que é bug de lógica**, conferir se a propriedade que o JS escreve tem `!important` no CSS
3. **Evitar `!important` em primeiro lugar** quando possível — usa specificity natural (`.parent .child`)
4. Quando precisar mesmo (ex: override de framework), **documentar no comentário do CSS** que essa regra é intencional + qual JS precisa de `setProperty('important')`
5. **Refactor de código limpo ≠ fix de bug.** Pode fazer os dois ao mesmo tempo, mas saber qual é qual.

## Aplicações
- [[2026-04-09 CSS important matava filtro de templates]]
