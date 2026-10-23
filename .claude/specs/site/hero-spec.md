# Spec — Hero (Página Inicial)

**Status:** ✅ Implementado — `resources/views/welcome.blade.php`

**Rota:** `GET /`
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Estrutura geral da página

A página inicial é composta por um **header fixo com menu de navegação** seguido de uma **seção hero em tela cheia**.

```
┌─────────────────────────────────────────┐
│              HEADER (fixo)              │
│  Logo    Início Sobre Eventos ...       │
├─────────────────────────────────────────┤
│                                         │
│                                         │
│           HERO (100vh - header)         │
│              [Logo SVG]                 │
│              [Subtitle]                 │
│                                         │
│                  ↓                      │
└─────────────────────────────────────────┘
```

---

## 2. Favicon

- Arquivo: `public/images/favicon.png`
- Configurar no `<head>` da página

---

## 3. Header (navegação)

### 3.1 Layout

- Posição: **fixo no topo** (`position: fixed`, `z-index` alto)
- Altura: 64px (desktop) / 56px (mobile)
- Fundo: `--color-surface` com leve sombra ou borda inferior em `--color-border`
- Separado em dois lados: **logo à esquerda** e **links à direita**

### 3.2 Logo no header

- Exibir a logo `PHPcomRapadura_color.svg` à esquerda
- Altura máxima: 36px
- Clicar redireciona para `#hero` (topo da página)

### 3.3 Links de navegação

| Label | Âncora |
|-------|--------|
| Início | `#hero` |
| Sobre | `#sobre` |
| Eventos | `#eventos` |
| Código de conduta | `#codigo-de-conduta` |
| Contato | `#contato` |

- Fonte: Lexend, 14px, peso 500
- Cor padrão: `--color-text-muted`
- Cor hover / ativo: `--color-primary`
- Transição suave no hover (150ms)
- O link da seção visível no momento recebe destaque (active state via scroll spy)

### 3.4 Mobile (< 768px)

- Os links de navegação são **ocultados** e substituídos por um botão hambúrguer (ícone ≡)
- Ao clicar, abre um **menu drawer** deslizando da direita ou um dropdown abaixo do header
- Ao clicar em qualquer link do menu mobile, o menu fecha automaticamente
- Scroll suave até a âncora após fechar

---

## 4. Seção Hero

### 4.1 Dimensão

- Altura mínima: `100vh`; cresce quando necessário em telas baixas
- Conteúdo centralizado na área útil, com espaço inferior reservado à fileira de ilustrações e à seta

### 4.2 Fundo

- Cor de fundo: `--color-bg` (`#F5F6F8` no light mode)
- Cinco xilogravuras decorativas na base, lado a lado: Pedra da Galinha Choca, Iracema, Belchior, Ponte Metálica e Arena Castelão
- Partial `resources/views/partials/hero-ceara.blade.php`, com recortes SVG e `clipPath` das artes `hero-ceara-cordel.png` e `hero-ceara-cordel-v2.png`
- Fileira com largura máxima de 1000px; recortes com altura de 132px, ou 72px abaixo de 640px
- Opacidade de 7,5% no desktop, 6% no celular e 9% no modo escuro; estáticas, `aria-hidden` e sem interceptar cliques
- Referências e prompts em `docs/hero-ceara-cordel.md`

### 4.3 Logo

- Arquivo: `public/images/PHPcomRapadura_color.svg`
- `alt`: "PHP com Rapadura"
- Largura máxima: 340px no mobile, 560px a partir de 640px e 720px a partir de 1024px
- Centralizada horizontalmente

### 4.4 Subtitle

- Texto: *"Grupo de desenvolvedores PHP do Ceará, formados através de uma ligação doce, como a rapadura e o café."*
- Posição: abaixo da logo, espaçamento top de 24px
- Fonte: Lexend, 18px no mobile, 20px a partir de 640px e 24px a partir de 1024px, peso 400
- Cor: `--color-text-muted`
- Largura máxima: 600px, centralizado
- `text-align: center`

### 4.5 Scroll indicator

- Ícone de seta ou chevron apontando para baixo, centralizado horizontalmente
- Posicionado próximo ao fundo da seção hero
- Animação de bounce suave (loop)
- Cor: `--color-text-muted`
- Ao clicar, faz scroll suave até a próxima seção (`#sobre`)

### 4.6 Animação de entrada

- Logo e subtitle surgem com **fade-in + slide-up** suave ao carregar a página
- Duração: 600ms, easing `ease-out`
- Logo anima primeiro; subtitle com delay de 150ms

---

## 5. SEO / Meta

- `<title>`: `PHP com Rapadura — Comunidade PHP do Ceará`
- `<meta name="description">`: `"Grupo de desenvolvedores PHP do Ceará, formados através de uma ligação doce, como a rapadura e o café."`
- `$communityDescription` é a fonte única para o hero, meta description, OG/Twitter e descrição da organização no JSON-LD
- `#codigo-de-conduta` usa `data-nosnippet` para não compor resumos da busca; a escolha do snippet continua sendo do Google
- Imagem de compartilhamento: `public/images/phpcomrapadura-social.png`, logo do hero renderizada em PNG 1200 × 630 sobre fundo claro; usada por OG, Twitter e `Organization.image`
- Guia de manutenção: `docs/site-publico.md`

---

## 6. Acessibilidade

- Todas as imagens com `alt` descritivo
- Links do menu com foco visível (outline usando `--color-primary`)
- Botão hambúrguer com `aria-label="Abrir menu"` / `"Fechar menu"`
- Menu mobile com `aria-expanded` e navegação por teclado (fechar com `Esc`)

---

## 7. Responsividade

| Breakpoint | Comportamento |
|------------|--------------|
| Mobile (< 640px) | Logo até 340px, subtitle 18px, xilogravuras com altura de 72px |
| Tablet (640px–1023px) | Logo até 560px, subtitle 20px, xilogravuras com altura de 132px |
| Desktop (≥ 1024px) | Logo até 720px, subtitle 24px, xilogravuras com altura de 132px |

Testar obrigatoriamente em **360px de largura** sem overflow horizontal.

---

## 8. Critérios de aceite

- [ ] Favicon exibido na aba do navegador
- [ ] Header fixo não some ao rolar a página
- [ ] Todos os 5 links de navegação presentes e funcionando (scroll suave até a âncora)
- [ ] Link ativo destacado conforme a seção visível
- [ ] Menu hambúrguer funcional no mobile, abre e fecha corretamente
- [x] Hero tem altura mínima de 100vh, com logo e subtitle centralizados e espaço para a decoração
- [x] Logo com limites responsivos de 340px / 560px / 720px
- [x] Cinco xilogravuras suaves em fileira na base, sem cobrir o texto
- [ ] Animação de entrada ao carregar a página
- [ ] Scroll indicator com bounce e scroll suave ao clicar
- [ ] Página sem overflow horizontal em 360px
- [ ] `<title>` e `<meta description>` corretos no HTML
