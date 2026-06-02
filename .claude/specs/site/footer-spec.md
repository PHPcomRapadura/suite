# Spec — Footer

**Status:** ✅ Implementado — `resources/views/welcome.blade.php`

**Rota:** `GET /` (ao final de todas as seções)
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Estrutura geral

```
┌─────────────────────────────────────────────────────────┐
│  [marca d'água footer.jpg — fundo, baixa opacidade]     │
│                                                         │
│  [Nav: Início · Sobre · Eventos · Cód. Conduta · Contato] │ ← desktop: linha única
│                                          [Logo branca]  │
│                                                         │
│  © 2014–2026 PHP com Rapadura. Todos os direitos        │
│  reservados.                                            │
└─────────────────────────────────────────────────────────┘

[⬆ botão floating — canto inferior direito, fixo na tela]
```

- Elemento: `<footer>`
- Fundo sólido: `#025c98` (cor primária da marca)
- Todo o texto e ícones em branco (`#ffffff`)
- Posição: `relative` para conter a marca d'água absoluta
- Overflow: `hidden` (a marca d'água não vaza)

---

## 2. Marca d'água (footer.jpg)

- Arquivo: `public/images/footer.jpg` (15507×2206px — panorâmica)
- Posicionamento: `position: absolute; inset: 0`
- `background-size: cover`, `background-position: center`
- Opacidade: **8%** — apenas visível como textura, não interfere na legibilidade
- `z-index: 0` — fica atrás de todo o conteúdo

---

## 3. Conteúdo principal

Todo o conteúdo fica num `div` com `position: relative; z-index: 1` para ficar acima da marca d'água.

### 3.1 Container

- `max-width: 1280px`, centralizado, `padding: 48px 24px 32px`

### 3.2 Linha superior — nav + logo

Layout `flex` com `justify-content: space-between` e `align-items: center`.

**Esquerda — navegação:**

Links do menu em linha horizontal (desktop) / vertical (mobile):

| Label | Âncora |
|-------|--------|
| Início | `#hero` |
| Sobre | `#sobre` |
| Eventos | `#eventos` |
| Código de conduta | `#codigo-de-conduta` |
| Contato | `#contato` |

- Fonte: Lexend, 14px, peso 500
- Cor: `rgba(255,255,255,0.8)` no estado normal
- Cor hover: `#ffffff`
- Transição: 150ms
- Separador entre links (desktop): `·` ou `|` em `rgba(255,255,255,0.3)`

**Direita — logo:**

- Arquivo: `public/images/phpcomrapadura_branca.svg`
- `alt`: "PHP com Rapadura"
- Altura: 48px (desktop) / 36px (mobile)
- Clicar rola suavemente para o topo (`#hero`)

### 3.3 Linha inferior — copyright

- Posição: abaixo da linha superior com `margin-top: 32px` e `padding-top: 24px` e `border-top: 1px solid rgba(255,255,255,0.15)`
- Texto: `© 2014–2026 PHP com Rapadura. Todos os direitos reservados.`
- Fonte: Lexend, 13px, peso 400
- Cor: `rgba(255,255,255,0.6)`
- Alinhamento: centralizado

---

## 4. Botão "Voltar ao topo" (floating)

Botão **fixo** na tela, canto inferior direito, independente do footer.

### 4.1 Comportamento

- **Oculto** quando o scroll está no topo (primeiros 400px)
- **Visível** após o usuário rolar mais de 400px — aparece com fade-in suave
- Ao clicar, rola suavemente para o `#hero`

### 4.2 Visual

- Posição: `position: fixed; bottom: 24px; right: 24px; z-index: 99`
- Formato: círculo, `48px × 48px`
- Fundo: `#025c98` (primária)
- Ícone: chevron/seta para cima, branco, SVG inline
- Sombra: `0 4px 12px rgba(0,0,0,0.25)`
- Hover: fundo `#024d80` (primária mais escura)
- Transição de visibilidade: `opacity` + `transform: translateY(8px)` → `translateY(0)`, 250ms ease

### 4.3 Acessibilidade

- `aria-label="Voltar ao topo"`
- `aria-hidden="true"` quando invisível
- Foco visível com `outline: 2px solid white; outline-offset: 2px`

---

## 5. Responsividade

| Breakpoint | Nav | Logo | Copyright |
|------------|-----|------|-----------|
| Mobile (< 768px) | Links empilhados verticalmente, centralizados | Centralizada acima dos links | Centralizado |
| Desktop (≥ 768px) | Linha horizontal, lado esquerdo | Lado direito | Centralizado abaixo |

No mobile: layout flex-col com logo no topo, links abaixo e copyright ao final.

---

## 6. Acessibilidade

- `<footer>` com `role="contentinfo"` implícito (elemento semântico)
- Nav do footer com `aria-label="Rodapé — navegação"`
- Logo com `alt` descritivo
- Todos os links com foco visível (outline branco)

---

## 7. Critérios de aceite

- [ ] Fundo `#025c98` cobrindo todo o footer
- [ ] Marca d'água `footer.jpg` visível como textura sutil (8% opacidade)
- [ ] Logo branca à direita no desktop, centralizada no mobile
- [ ] 5 links de navegação funcionando (scroll suave para âncoras)
- [ ] Texto de copyright com ano `2014–2026`
- [ ] Separador entre conteúdo e copyright
- [ ] Botão "Voltar ao topo" fixo no canto inferior direito
- [ ] Botão aparece apenas após 400px de scroll
- [ ] Botão oculto no topo com transição suave de entrada/saída
- [ ] Nenhum overflow horizontal em 360px
