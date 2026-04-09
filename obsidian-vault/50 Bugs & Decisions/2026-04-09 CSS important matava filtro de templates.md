---
type: bug
status: resolved
date: 2026-04-09
severity: medium
modules: ["[[Lead Scoring]]", "[[Automations]]", "[[Nurture Sequences]]"]
files:
  - resources/views/tenant/settings/_template_library_modal.blade.php
commits: ["d50c7d0", "700b953"]
related: ["[[Lead Scoring]]", "[[Automations]]", "[[Nurture Sequences]]"]
tags: [bug, rca, css, frontend]
---

# 2026-04-09 — CSS !important matava filtro de templates

## Sintoma
Nas páginas de **Lead Scoring**, **Automações** e **Sequências**, a "Biblioteca de Modelos" tem filtro lateral por nicho ("Imobiliária", "E-commerce", etc). Clicar num nicho fazia o botão ficar azul (active) mas **os cards continuavam todos visíveis**. Sem erro no console. Sem fix óbvio.

## Investigação (também com erro pelo caminho)

### Erro 1 (meu)
Achei que o bug era no JS antigo que extraía categoria via regex no atributo `onclick`:
```js
const activeCat = document.querySelector(`#${modalId}-cats .tplib-cat.active`)
    ?.getAttribute('onclick')
    ?.match(/'([^']+)'/g)?.[1]?.replace(/'/g, '') || 'all';
```

Refatorei tudo (commit `d50c7d0`): event delegation, state object em `window.__tplLib`, `data-tplib-cat` em vez de onclick inline. Lint clean, deploy. **Continuou quebrado.**

### User mandou screenshot
Mostrava:
- Botão "E-commerce" azul (active CSS funcionando)
- Console SEM erros
- Cards de "Imóveis" / "Consulta" continuando visíveis

### Smoking gun
Ler o CSS do próprio modal. Tinha:
```css
.tplib-shell .tplib-card {
    display: flex !important;
    /* ... */
}
```

E o JS fazia `card.style.display = 'none'` (inline style sem `!important`).

**CSS com `!important` GANHA de inline style sem `!important`.** Os cards nunca sumiam.

## Causa raiz
Inline style assignment via JavaScript não bate `!important` em CSS. A única forma é usar `setProperty(prop, value, 'important')`:
```js
card.style.setProperty('display', 'none', 'important');
```

O bug existia desde o commit `f00a1b8` (criação das 3 bibliotecas) e ninguém viu porque os 3 lugares (scoring + automações + sequências) compartilham o mesmo partial — todos quebrados igual.

## Fix (commit `700b953`)
```js
if (show) {
    card.style.setProperty('display', 'flex', 'important');
    visible++;
} else {
    card.style.setProperty('display', 'none', 'important');
}
```

Comentário extenso no código alertando o próximo dev pra não cair no mesmo buraco.

## Por que não foi pego antes
- Sem testes E2E que cliquem em filtros
- Compartilha partial entre 3 páginas — todos quebrados juntos parece "design intencional"
- Console limpo, sem warning de CSS specificity (browser não loga isso)
- Filtro "Todos" funciona porque é o estado inicial — bug só aparece ao clicar num nicho específico

## Lição aprendida
- **CSS `!important` sempre ganha de inline style sem `!important`**, mesmo via JS
- **Quando o JS "funciona" mas o efeito visual não acontece**, abrir Inspector e verificar specificity da regra CSS
- O refactor JS que fiz no commit `d50c7d0` foi útil (event delegation > regex no onclick), mas **não era a causa** — confirmação de que "limpar código" e "fix de bug" são coisas separadas
- Na próxima vez: pedir screenshot do DevTools Computed Styles ANTES de assumir causa raiz

## Links
- Commits: `d50c7d0` (refactor JS — útil mas não era o fix), `700b953` (fix real do `!important`)
- Arquivo: [`resources/views/tenant/settings/_template_library_modal.blade.php`](resources/views/tenant/settings/_template_library_modal.blade.php)
- Lessons: [[CSS important sempre ganha de inline style sem important]]
