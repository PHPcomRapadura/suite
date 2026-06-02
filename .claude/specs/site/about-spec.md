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
- Centralizado horizontalmente com `max-width: 760px`

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

- Arquivo: `public/images/sobre-php-com-rapadura.jpg`
- `alt`: "Comunidade PHP com Rapadura reunida"

### 3.2 Dimensões

- Largura: 100% da viewport
- Altura: `480px` (desktop) / `300px` (mobile)

### 3.3 Efeito parallax

- Implementar com `background-attachment: fixed` e `background-position: center`
- A imagem move-se mais lentamente que o scroll, criando profundidade
- `background-size: cover`

### 3.4 Overlay

- Overlay escuro semitransparente sobre a imagem: `rgba(0, 0, 0, 0.35)`
- Garante contraste caso haja texto sobre a imagem no futuro

### 3.5 Transição

- A imagem serve como separador visual entre a seção Sobre e a próxima seção
- Sem texto sobre a imagem nesta versão

> **Nota mobile:** `background-attachment: fixed` não funciona corretamente em iOS. No mobile, usar `background-attachment: scroll` com `background-size: cover`.

---

## 4. Responsividade

| Breakpoint | Texto | Imagem parallax |
|------------|-------|-----------------|
| Mobile (< 768px) | 15px, padding 64px vertical | 300px altura, sem parallax |
| Desktop (≥ 768px) | 17px, padding 96px vertical | 480px altura, com parallax |

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
- [ ] Bloco de texto com max-width 760px centralizado na tela
- [ ] Imagem ocupa 100% da largura com altura 480px no desktop
- [ ] Efeito parallax funcional no desktop (imagem move mais devagar que o scroll)
- [ ] No mobile, parallax desativado (`background-attachment: scroll`)
- [ ] Overlay escuro visível sobre a imagem
- [ ] Nenhum overflow horizontal em 360px de largura
