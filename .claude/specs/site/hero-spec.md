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

- Altura: `100vh` (tela cheia)
- O conteúdo é vertically e horizontally centralizado

### 4.2 Fundo

- Cor de fundo: `--color-bg` (`#F5F6F8` no light mode)
- Sem imagem ou gradiente — fundo limpo e neutro

### 4.3 Logo

- Arquivo: `public/images/PHPcomRapadura_color.svg`
- `alt`: "PHP com Rapadura"
- Largura máxima: 320px (desktop) / 240px (mobile)
- Centralizada horizontalmente

### 4.4 Subtitle

- Texto: *"Grupo de desenvolvedores PHP do Ceará, formados através de uma ligação doce, como a rapadura e o café."*
- Posição: abaixo da logo, espaçamento top de 24px
- Fonte: Lexend, 18px (desktop) / 15px (mobile), peso 400
- Cor: `--color-text-muted`
- Largura máxima: 480px, centralizado
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
| Mobile (< 768px) | Menu hambúrguer, logo 240px, subtitle 15px |
| Tablet (768px–1024px) | Menu completo visível, logo 280px |
| Desktop (> 1024px) | Layout completo, logo 320px, subtitle 18px |

Testar obrigatoriamente em **360px de largura** sem overflow horizontal.

---

## 8. Critérios de aceite

- [ ] Favicon exibido na aba do navegador
- [ ] Header fixo não some ao rolar a página
- [ ] Todos os 5 links de navegação presentes e funcionando (scroll suave até a âncora)
- [ ] Link ativo destacado conforme a seção visível
- [ ] Menu hambúrguer funcional no mobile, abre e fecha corretamente
- [ ] Hero ocupa 100vh com logo e subtitle centralizados
- [ ] Logo limitada a 320px no desktop e 240px no mobile
- [ ] Animação de entrada ao carregar a página
- [ ] Scroll indicator com bounce e scroll suave ao clicar
- [ ] Página sem overflow horizontal em 360px
- [ ] `<title>` e `<meta description>` corretos no HTML
