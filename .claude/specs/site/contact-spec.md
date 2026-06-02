# Spec — Contato

**Status:** ✅ Implementado — `resources/views/welcome.blade.php`

**Rota:** `GET /` (âncora `#contato`)
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Estrutura geral

```
┌─────────────────────────────────────────┐
│              CONTATO                    │
│         (fundo --color-bg)              │
│                                         │
│  [Título h2]                            │
│  [Subtítulo]                            │
│                                         │
│  [Card email — destaque]                │
│                                         │
│  [Grid de redes sociais — 5 cards]      │
│                                         │
└─────────────────────────────────────────┘
```

- `id="contato"` (obrigatório para âncora do menu)
- `min-height: 100vh` — ocupa tela cheia como as demais seções
- Fundo: `--color-bg` para contrastar com o branco do Código de Conduta
- Conteúdo centralizado com `max-width: 1024px`
- Padding vertical: `96px` (desktop) / `64px` (mobile)
- Padding horizontal: `24px`

---

## 2. Título e subtítulo

- **Título:** "Fale com a gente"
- Elemento: `<h2>`, Lexend 32px (desktop) / 26px (mobile), peso 700, cor `--color-text`, centralizado
- **Subtítulo:** "Estamos presentes em diversas plataformas. Escolha a que preferir."
- Elemento: `<p>`, Lexend 17px, peso 400, cor `--color-text-muted`, centralizado
- Margin bottom após subtítulo: `48px`

---

## 3. Card de email — destaque

O email deve ter tratamento visual diferenciado, maior que os cards de redes sociais.

- Layout: linha horizontal centralizada com ícone + texto + botão de cópia
- Fundo: `--color-surface` com borda `1px solid --color-border` e `border-radius: 12px`
- Padding: `24px 32px`
- `max-width: 480px`, centralizado
- Margin bottom: `48px`

**Conteúdo:**
- Ícone de envelope (SVG inline)
- Label: "Email" — Lexend 12px, peso 600, `--color-text-muted`, uppercase, letter-spacing
- Endereço: `contato@phpcomrapadura.org` — Lexend 18px, peso 600, `--color-text`
- Botão "Copiar" discreto ao lado — ao clicar, copia o email e muda o label para "Copiado ✓" por 2 segundos

**Acessibilidade:** `<a href="mailto:contato@phpcomrapadura.org">` envolvendo o email.

---

## 4. Grid de redes sociais

### 4.1 Layout

- Grid: 2 colunas (mobile) / 3 colunas (tablet) / 5 colunas (desktop ≥ 1024px)
- `gap: 16px`

### 4.2 Card individual

Cada rede social é um card:
- Fundo: `--color-surface`
- Borda: `1px solid --color-border`, `border-radius: 12px`
- Padding: `24px 16px`
- Alinhamento: centralizado (ícone + nome + handle)
- Hover: leve sombra (`shadow-md`) + `border-color: --color-primary` com transição 200ms
- O card inteiro é clicável (`<a>` com `target="_blank" rel="noopener noreferrer"`)

**Estrutura interna de cada card:**
```
[Ícone SVG — 32px — cor da rede]
[Nome da rede — 14px, peso 600, --color-text]
[Handle/URL curta — 13px, --color-text-muted]
```

### 4.3 Redes sociais

| Rede | Handle/Label | URL | Cor do ícone |
|------|-------------|-----|--------------|
| Telegram | @phpcomrapadura | https://t.me/phpcomrapadura | `#229ED9` |
| Instagram | @phpcomrapadura | https://www.instagram.com/phpcomrapadura | `#E1306C` |
| Twitter/X | @phpcomrapadura | https://x.com/phpcomrapadura | `#000000` |
| Facebook | RAPADURAdoPoder | https://www.facebook.com/RAPADURAdoPoder | `#1877F2` |
| Flickr | Álbum de fotos | https://flickr.com/photos/phpcomrapadura/albums | `#FF0084` |

> **Nota:** O Flickr é o repositório oficial de fotos dos eventos. O handle deve ser "Álbum de fotos" para deixar claro o propósito.

### 4.4 Ícones

Usar SVGs inline para cada rede social. Não depender de biblioteca externa. Cada ícone deve ter `aria-hidden="true"` e o `<a>` deve ter `aria-label` descritivo.

---

## 5. Responsividade

| Breakpoint | Grid cards | Título |
|------------|-----------|--------|
| Mobile (< 640px) | 2 colunas | 26px |
| Tablet (640px–1023px) | 3 colunas | 28px |
| Desktop (≥ 1024px) | 5 colunas | 32px |

Testar sem overflow horizontal em **360px** de largura.

---

## 6. Acessibilidade

- Cada `<a>` de rede social com `aria-label`: ex. `"PHP com Rapadura no Instagram (abre em nova aba)"`
- Email com `href="mailto:..."` para abrir cliente de email nativo
- Botão de copiar email com feedback visual e `aria-live="polite"` para leitores de tela
- Ícones SVG com `aria-hidden="true"` (o texto do card já descreve a rede)
- Foco visível em todos os cards e botões (`outline: 2px solid --color-primary`)

---

## 7. Critérios de aceite

- [ ] Seção tem `id="contato"` e o link "Contato" do menu ancora corretamente
- [ ] Seção ocupa `min-height: 100vh`
- [ ] Card de email com `href="mailto:"` funcional
- [ ] Botão de copiar email funciona e exibe feedback "Copiado ✓"
- [ ] 5 cards de redes sociais presentes com ícone, nome e handle corretos
- [ ] Todos os links abrem em `target="_blank"` com `rel="noopener noreferrer"`
- [ ] Hover nos cards com sombra e borda azul
- [ ] Grid responsivo: 2 colunas mobile / 3 tablet / 5 desktop
- [ ] Nenhum overflow horizontal em 360px
- [ ] `aria-label` descritivo em todos os links externos
