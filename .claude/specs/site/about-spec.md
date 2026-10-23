# Spec — Sobre (About)

**Status:** ✅ Implementado — `resources/views/welcome.blade.php`

**Rota:** `GET /` (âncora `#sobre`)
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Estrutura geral

A seção é composta por duas partes em sequência vertical:

```
┌─────────────────────────────────────────┐
│                                         │
│           BLOCO DE TEXTO                │
│         (fundo --color-bg)              │
│                                         │
├─────────────────────────────────────────┤
│                                         │
│        IMAGEM PARALLAX                  │
│    (transição para próxima seção)       │
│                                         │
└─────────────────────────────────────────┘
```

---

## 2. Bloco de texto

### 2.1 Container

- `id="sobre"` (obrigatório para âncora do menu)
- Fundo: `--color-bg`
- Padding vertical: `96px` (desktop) / `64px` (mobile)
- Padding horizontal: `24px`
- Centralizado horizontalmente com `max-width: 1024px`

### 2.2 Título

- Texto: **"Sobre a PHP com Rapadura"**
- Elemento: `<h2>`
- Fonte: Lexend, 32px (desktop) / 26px (mobile), peso 700
- Cor: `--color-text`
- Alinhamento: centralizado
- Margin bottom: `32px`

### 2.3 Parágrafos

Os 5 parágrafos abaixo devem ser renderizados em sequência, separados por `margin-bottom: 20px`:

> A PHP com Rapadura nasceu em 2014, no Ceará, da inquietação de quem acreditava que o Nordeste tinha potencial para ser muito mais protagonista no cenário tecnológico brasileiro. Após participar de eventos em outras regiões do país, nosso fundador, Alessandro Feitoza, percebeu que existiam milhares de estudantes, desenvolvedores e profissionais de tecnologia espalhados pelo estado, mas faltava um espaço capaz de conectar essas pessoas, incentivar o compartilhamento de conhecimento e fortalecer a comunidade PHP local.

> Foi assim que surgiu a PHP com Rapadura: uma comunidade feita por pessoas que acreditam que conhecimento só tem valor quando é compartilhado. Desde então, promovemos eventos, palestras, encontros, mentorias e iniciativas que aproximam estudantes, profissionais e empresas, criando oportunidades e fortalecendo o ecossistema de tecnologia da nossa região.

> O nome não poderia representar melhor nossa essência. O PHP simboliza a tecnologia que nos uniu. A rapadura representa nossas raízes nordestinas, a simplicidade, a resistência e a energia de um povo que aprende a transformar desafios em oportunidades. Assim como a rapadura nasceu dos engenhos e se tornou um dos maiores símbolos culturais do Nordeste, acreditamos que grandes profissionais, grandes projetos e grandes comunidades também podem nascer daqui.

> Mais do que uma comunidade de programação, somos um movimento que conecta pessoas. Aqui, iniciantes encontram apoio para dar os primeiros passos, profissionais compartilham experiências, empresas encontram talentos e todos crescem juntos. Porque acreditamos que a tecnologia muda vidas, mas é a comunidade que transforma trajetórias.

> Se achegue, pegue um café, corte um pedaço de rapadura e venha construir o futuro com a gente.

### 2.4 Tipografia dos parágrafos

- Fonte: Lexend, 17px (desktop) / 15px (mobile), peso 400
- Line-height: 1.8
- Cor: `--color-text`
- Alinhamento: centralizado
- Último parágrafo ("Se achegue…"): itálico, cor `--color-text-muted`

---

## 3. Imagem Parallax

### 3.1 Imagem

- Arquivo: `public/images/comunidade-php-com-rapadura.jpg` (4110 × 2044)
- `<img>` com dimensões intrínsecas, `loading="lazy"` e `decoding="async"`
- `alt`: "Participantes da PHP com Rapadura reunidos em um auditório"

### 3.2 Dimensões

- Largura: 100% da viewport
- Altura mínima da faixa: `460px` no desktop
- No mobile, foto inteira na proporção 4110 × 2044, com texto abaixo e altura total conforme o conteúdo

### 3.3 Efeito parallax

- `transform: translateY(var(--parallax-offset))`, limitado a ±14px
- Atualização via `requestAnimationFrame` enquanto a faixa está visível (`IntersectionObserver`)
- Imagem com `object-fit: cover` no desktop; sem `background-attachment: fixed`
- Desativado abaixo de 768px e com `prefers-reduced-motion: reduce`

### 3.4 Overlay

- Degradê concentrado na parte inferior, até o azul `#092c45`, para preservar os rostos e dar contraste ao convite

### 3.5 Transição

- Título centralizado: **O próximo encontro também pode ter você.**
- Link centralizado: **Veja os próximos eventos**, apontando para `#eventos`
- Sem subtítulo de apoio
- Borda inferior SVG decorativa inspirada em cordel, com `aria-hidden`

Implementação em `welcome.blade.php`, `app.css` e `app.js`; detalhes em `docs/site-publico.md`.

---

## 4. Responsividade

| Breakpoint | Texto | Imagem parallax |
|------------|-------|-----------------|
| Mobile (< 768px) | 15px | Foto inteira acima do convite, sem parallax |
| Desktop (≥ 768px) | 17px, padding 96px vertical | Faixa com altura mínima de 460px e parallax suave |

---

## 5. Acessibilidade

- `<h2>` com hierarquia correta após o `<h1>` implícito do hero
- Imagem decorativa com `alt` descritivo (não é `alt=""` pois tem valor contextual)
- Contraste dos parágrafos conforme WCAG AA

---

## 6. Critérios de aceite

- [ ] Seção tem `id="sobre"` e o link "Sobre" do menu faz scroll até ela
- [ ] Título `<h2>` visível, centralizado, Lexend 700
- [ ] 5 parágrafos renderizados em ordem com espaçamento correto
- [ ] Último parágrafo em itálico e cor muted
- [x] Bloco de texto com max-width 1024px centralizado na tela
- [x] Faixa ocupa 100% da largura com altura mínima de 460px no desktop
- [ ] Efeito parallax funcional no desktop (imagem move mais devagar que o scroll)
- [x] No mobile e com movimento reduzido, parallax desativado
- [x] Degradê azul, convite centralizado, link para eventos e borda de cordel
- [ ] Nenhum overflow horizontal em 360px de largura
