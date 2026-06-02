## Padrões de Front (Design System) — PHP com Rapadura

Este documento define os padrões visuais e de UI para manter consistência no projeto.

## 1. Cores

### 1.1 Diretriz geral

- **Priorize tokens semânticos** (ex.: `--color-primary`, `--color-bg`) em vez de usar cores “na mão”.
- **Contraste**: garantir leitura confortável em textos/inputs/botões (especialmente no dark mode).

### 1.2 Paleta base (verde — marca)

- **Brand 500 (primary)**: `#025c98`
- **Brand 600**: `#025c98`
- **Brand 700**: `#025c98`
- **Brand 800**: `#0A5507`
- **Brand 900**: `#004700`

### 1.3 Neutros (recomendação)

- **Fundo (light)**: `#F5F6F8` (cinza bem claro)
- **Superfícies (cards/inputs, light)**: `#FFFFFF`
- **Borda (light)**: `#E5E7EB`
- **Texto principal (light)**: `#111827`
- **Texto secundário (light)**: `#6B7280`

- **Fundo (dark)**: `#0B1220`
- **Superfícies (dark)**: `#0F172A`
- **Borda (dark)**: `#1F2937`
- **Texto principal (dark)**: `#E5E7EB`
- **Texto secundário (dark)**: `#9CA3AF`

### 1.4 Tokens semânticos (CSS variables)

Use as variáveis abaixo no layout (os valores são sugestão inicial e podem ser refinados):

```css
:root {
  /* Brand */
  --color-primary: #025c98;
  --color-primary-hover: #025c98;
  --color-primary-active: #025c98;

  /* Background / surface */
  --color-bg: #f5f6f8;
  --color-surface: #ffffff;
  --color-border: #e5e7eb;

  /* Text */
  --color-text: #111827;
  --color-text-muted: #6b7280;

  /* Feedback */
  --color-success: #16a34a;
  --color-warning: #f59e0b;
  --color-danger: #dc2626;
}

.dark {
  --color-bg: #0b1220;
  --color-surface: #0f172a;
  --color-border: #1f2937;

  --color-text: #e5e7eb;
  --color-text-muted: #9ca3af;
}
```

> Sugestão: usar estratégia de dark mode por classe (`.dark`) para facilitar.

## 2. Tipografia

### 2.1 Fonte

- **Fonte padrão**: Lexend (Google Fonts)
- **Fallback**: system-ui, -apple-system, Segoe UI, Roboto, sans-serif
- **Peso recomendado**:
  - Texto: 400/500
  - Títulos: 600/700

### 2.2 Hierarquia (escala sugerida)

- **H1**: 28–32px / 700
- **H2**: 22–24px / 700
- **H3**: 18–20px / 600
- **Body**: 14–16px / 400–500
- **Caption**: 12–13px / 400–500

## 3. Espaçamento, layout e bordas

- **Grid**: 8px (margens/paddings em múltiplos de 8)
- **Radius padrão**: 10–12px (cards), 8–10px (inputs/botões)
- **Bordas**: 1px com `--color-border`
- **Sombras**: sutis (evitar sombras pesadas)

## 4. Componentes (padrões de UI)

### 4.1 Botões

- **Primary**: fundo `--color-primary`, texto branco
- **Secondary**: fundo `--color-surface`, borda `--color-border`, texto `--color-text`
- **Danger**: usar `--color-danger` para ações destrutivas
- **Estados**: hover/active/disabled (sempre com feedback visual)

### 4.2 Inputs

- Fundo: `--color-surface`
- Borda: `--color-border`
- Foco: destacar com outline/anel usando `--color-primary`
- Erro: borda/ajuda em `--color-danger`

### 4.3 Cards / Tabelas

- Cards: `--color-surface` + borda leve
- Tabelas: zebra muito sutil + bordas (não depender só de cor)

## 5. Dark mode

### 5.1 Regras

- Evitar preto puro (#000): prefira tons azulados/acinzentados.
- Testar obrigatoriamente: botões, inputs, tabelas, badges e alertas.

### 5.2 Estratégia (Tailwind)

Recomendação: `darkMode: 'class'` e alternar a classe `dark` no `html` (ou `body`).

Exemplo (conceitual):

```js
// tailwind.config.js (exemplo)
export default {
  darkMode: 'class',
};
```

## 6. Acessibilidade (mínimo aceitável)

- **Focus visible**: todo elemento interativo precisa de estado de foco claro.
- **Alvo clicável**: ~40px (mínimo recomendado).
- Não depender só de cor para indicar status (usar ícones/texto).

## 7. Loader de progresso (carregamento)

### 7.1 Objetivo

Padronizar um **indicador de carregamento** ao buscar dados e ao trocar rotas, evitando “tela travada” e reduzindo ansiedade do usuário.

### 7.2 Padrão recomendado

- **Preferência**: *Top Progress Bar* (barra fina no topo) para carregamentos rápidos/médios.
- **Complemento**: *Skeletons* para listas/cards/tabelas quando o layout é previsível.
- **Fallback**: *Spinner* local em botões/áreas específicas (ex.: “Salvar”, “Entrar”).

### 7.3 Regras de uso (UX)

- **Delay**: só exibir o loader se o carregamento passar de ~150–250ms (evita “piscadas”).
- **Não bloquear toda a UI** por padrão: prefira loaders **locais**; use bloqueio global apenas em fluxos críticos.
- **Estados consistentes**: carregando / sucesso / erro (erro deve exibir mensagem e permitir retry).
- **Concorrência**: se houver múltiplas requisições ao mesmo tempo, o loader global deve considerar “pendências” (só finaliza quando todas terminarem).

### 7.4 Onde disparar

- **Troca de rota** (Vue Router): iniciar no `beforeEach` e finalizar no `afterEach` (ou quando os dados essenciais carregarem).
- **Requisições HTTP** (axios/fetch): iniciar antes da chamada e finalizar no `finally`.
- **Componentes**: listas/tabelas devem ter `loading`/`isFetching` explícito.

### 7.5 Padrão visual

- Altura: **2–3px**
- Cor: usar o **primary** (`--color-primary`)
- Em dark mode: manter contraste (mesma cor geralmente funciona bem; ajustar se necessário)

## 8. Mobile-friendly (responsividade)

### 8.1 Regra geral

**Todas as telas devem ser mobile-friendly**. O padrão do projeto é **mobile-first**: desenhar e implementar primeiro para telas pequenas e evoluir para desktop.

### 8.2 Diretrizes práticas

- **Layout**: evitar larguras fixas; preferir `w-full`, `max-w-*`, `flex-wrap` e `grid` responsivo.
- **Breakpoints**: usar breakpoints de forma consistente (ex.: `sm`, `md`, `lg`, `xl`) e sempre testar pelo menos `mobile` e `desktop`.
- **Navegação**: menus e ações principais devem continuar acessíveis no mobile (ex.: menu colapsável, barra inferior quando fizer sentido).
- **Tabelas**: no mobile, evitar “tabela espremida”:
  - Preferir **lista/cards** responsivos, ou
  - Permitir scroll horizontal (`overflow-x-auto`) com cabeçalhos fixos quando necessário.
- **Formulários**: labels legíveis, inputs com altura confortável e teclados corretos (`type=email`, `type=tel`, etc.).
- **Toque**: alvos clicáveis ~**40px** (mínimo), com espaçamento suficiente entre ações.
- **Performance**: evitar carregar “peso” desnecessário no mobile; usar paginação e skeletons.

### 8.3 Checklist mínimo antes de considerar “pronto”

- Funciona bem em largura ~**360px** (sem overflow inesperado)
- Botões/links não ficam colados (evita misclick)
- Inputs são utilizáveis (sem zoom/scroll estranho)
- Tabelas/listas continuam legíveis

---

## 9. ⚠️ Tailwind v4 — Sintaxe de CSS Variables

**CRÍTICO:** No Tailwind v4, o uso de CSS custom properties com colchetes `[]` gera CSS inválido.

| ❌ Errado | ✅ Correto |
|-----------|-----------|
| `bg-[--color-bg]` → `background-color: --color-bg` | `bg-(--color-bg)` → `background-color: var(--color-bg)` |
| `text-[--color-text]` | `text-(--color-text)` |
| `border-[--color-border]` | `border-(--color-border)` |
| `hover:text-[--color-primary]` | `hover:text-(--color-primary)` |
| `focus-visible:outline-[--color-primary]` | `focus-visible:outline-(--color-primary)` |

Usar sempre **parênteses** para referenciar CSS custom properties no Tailwind v4.

Para cores hardcoded (quando o token não existe): usar `bg-white`, `bg-[#025c98]`, etc.

---

## 10. SEO — Padrões para novas páginas

Toda página pública deve incluir no `<head>`:

```html
<!-- SEO básico -->
<title>Título da Página — PHP com Rapadura</title>
<meta name="description" content="...">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#025c98">
<link rel="canonical" href="{{ url('/rota') }}">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:title" content="...">
<meta property="og:description" content="...">
<meta property="og:image" content="{{ asset('images/og-image.jpg') }}">
<meta property="og:locale" content="pt_BR">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@@phpcomrapadura">

<!-- JSON-LD (usar @@ para escapar @ no Blade) -->
<script type="application/ld+json">
{ "@@context": "https://schema.org", "@@type": "WebPage", ... }
</script>
```

> **Blade + JSON-LD:** Escrever `@@context` e `@@type` — o Blade renderiza `@context` e `@type` no HTML final.

---

## 11. Acessibilidade — Checklist mínimo

Todo componente/página deve atender:

- `lang` correto no `<html>` (`pt-BR`)
- `<main>` envolvendo conteúdo principal
- Skip link "Pular para o conteúdo" visível no foco
- `<h1>` presente (pode ser `sr-only` se for imagem/logo)
- Hierarquia de headings `h1 → h2 → h3` sem pulos
- Todas as `<img>` com `alt` descritivo (ou `alt=""` se decorativa)
- Todos os `<button>` e `<a>` sem texto visível com `aria-label`
- `aria-live="polite"` em regiões com conteúdo dinâmico
- `aria-current="true"` no item ativo de navegação
- Focus visível em todos os elementos interativos (`focus-visible:outline-2`)
- `@media (prefers-reduced-motion: reduce)` desativando animações
- Modais com `role="dialog"`, `aria-modal`, focus trap e Esc para fechar
- Imagens com `width` e `height` para evitar CLS

