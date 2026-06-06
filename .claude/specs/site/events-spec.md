# Spec — Eventos (Site Institucional)

**Status:** ✅ Implementado — `resources/views/welcome.blade.php`

**Rota:** `GET /` (âncora `#eventos`)
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Objetivo

Exibir os eventos publicados da comunidade no site institucional. Permite que visitantes conheçam o próximo evento, acessem o site público do evento e identifiquem se o CFP está aberto para submissões.

---

## 2. Dados e backend

### 2.1 Query

Buscar eventos com `status = 'publicado'`, ordenados por `starts_at DESC` (mais recente/futuro primeiro — próximo evento aparece no topo).

```php
Event::where('status', 'publicado')
    ->orderBy('starts_at', 'desc')
    ->get(['name', 'slug', 'edition', 'starts_at', 'ends_at',
           'location', 'is_online', 'is_accepting_talks', 'cover_image'])
```

### 2.2 Injeção de dados

A rota `GET /` em `routes/web.php` deve passar os eventos para a view:

```php
Route::get('/', function () {
    $events = \App\Models\Event::where('status', 'publicado')
        ->orderBy('starts_at', 'desc')
        ->get(['name', 'slug', 'edition', 'starts_at', 'ends_at',
               'location', 'is_online', 'is_accepting_talks', 'cover_image']);

    return view('welcome', compact('events'));
});
```

Se a rota crescer (mais queries), extrair para `WelcomeController`.

---

## 3. Layout da seção

```
┌─────────────────────────────────────────────────────────────┐
│                     SEÇÃO EVENTOS                           │
│                   id="eventos"                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│                      "Eventos"                              │
│                      <h2>                                   │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  [COVER 16:9]│  │  [COVER 16:9]│  │  [COVER 16:9]│      │
│  │ ┌──────────┐ │  │              │  │              │      │
│  │ │CFP Aberto│ │  │              │  │              │      │
│  │ └──────────┘ │  │              │  │              │      │
│  ├──────────────┤  ├──────────────┤  ├──────────────┤      │
│  │ Nome — Xª ed │  │ Nome — Xª ed │  │ Nome — Xª ed │      │
│  │ 📅 Data       │  │ 📅 Data       │  │ 📅 Data       │      │
│  │ 📍 Local      │  │ 📍 Local      │  │ 📍 Local      │      │
│  │ [Ver evento →]│  │ [Ver evento →]│  │ [Ver evento →]│      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 4. Container da seção

- `id="eventos"` — obrigatório para âncora do menu
- Fundo: `--color-bg`
- Padding vertical: `96px` desktop / `64px` mobile
- Padding horizontal: `24px`
- `max-width: 1024px`, centralizado na página

### 4.1 Título

- Texto: **"Eventos"**
- Elemento: `<h2>`
- Fonte: Lexend, 32px desktop / 26px mobile, peso 700
- Cor: `--color-text`
- Alinhamento: centralizado
- `margin-bottom: 48px`
- Classe `section-hidden` para animação de entrada

---

## 5. Grid de cards

- Layout: `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6`
- `max-width: 1024px`, centralizado

### 5.1 Estrutura do card

Cada evento é um `<article>` com duas áreas: imagem (topo) e conteúdo (abaixo).

```
┌─────────────────────────┐
│   IMAGEM (aspect 16:9)  │
│  ┌────────────────┐     │
│  │  CFP Aberto    │     │  ← badge, canto superior esquerdo
│  └────────────────┘     │
├─────────────────────────┤
│ Nome do Evento          │  ← <h3>
│ Xª edição               │
│ 📅 10 de jun. de 2026   │
│ 📍 Local ou Online      │
│                         │
│ [ Ver evento →        ] │  ← botão outline largura 100%
└─────────────────────────┘
```

- Borda: `1px solid --color-border`
- Border-radius: `12px`
- Fundo do conteúdo: `--color-surface`
- Sombra suave no hover: `box-shadow: 0 4px 16px rgba(0,0,0,0.08)`
- Transição no hover: `200ms ease`
- Classe `section-hidden` para animação escalonada (ver seção 8)

#### 5.1.1 Imagem

- Proporção: `aspect-ratio: 16/9`, `object-fit: cover`
- Largura: 100%
- Border-radius: `12px 12px 0 0`
- Alt: `{{ $event->name }}`
- **Placeholder** (quando `cover_image` é nulo):
  - Fundo: `--color-border`
  - Ícone SVG de calendário centralizado, 36px, cor `--color-text-muted`
  - `aria-hidden="true"`

#### 5.1.2 Badge "CFP Aberto"

Exibido **somente** quando `is_accepting_talks = true`.

- Posição: absoluta, canto superior esquerdo da imagem, `margin: 10px`
- Fundo: `--color-primary`
- Texto: "CFP Aberto", branco, 11px, peso 600, maiúsculas não
- Padding: `4px 10px`
- Border-radius: `6px`
- `aria-label="Call for Papers aberto para submissões"`

#### 5.1.3 Área de conteúdo

Padding: `20px`.

**Nome e edição**
- Elemento: `<h3>`
- Texto: `{{ $event->name }}`
- Se `edition` não for nulo, adicionar linha abaixo: `{{ $event->edition }}ª edição`, 12px, `--color-text-muted`
- Fonte: Lexend, 16px, peso 700, cor `--color-text`
- `margin-bottom: 12px`

**Data**
- Ícone SVG calendário (14px, `--color-text-muted`) + texto formatado
- `margin-bottom: 6px`, 13px, cor `--color-text-muted`
- Formatação em pt-BR via PHP (`IntlDateFormatter` ou `Carbon`):

| Caso | Exemplo |
|------|---------|
| Mesmo dia | "10 de jun. de 2026" |
| Dias seguidos, mesmo mês | "10 e 11 de jun. de 2026" |
| Meses diferentes | "30 de jun. a 1 de jul. de 2026" |

Se `ends_at` for nulo, exibir apenas `starts_at`.

**Local**
- Ícone SVG pin de mapa (14px, `--color-text-muted`) + texto
- Se `is_online = true`: "Online"
- Se `is_online = false`: `{{ $event->location }}`
- 13px, cor `--color-text-muted`
- Truncar em 1 linha com `overflow: hidden; text-overflow: ellipsis; white-space: nowrap`

**Botão CTA**
- Texto: "Ver evento →"
- Elemento: `<a href="{{ url('/'.$event->slug) }}">`
- Estilo: outline — `border: 1.5px solid --color-primary`, texto `--color-primary`, fundo transparente
- Hover: fundo `--color-primary`, texto branco
- Largura: 100%, `padding: 10px 16px`
- Border-radius: `8px`
- Fonte: 14px, peso 600
- `margin-top: 16px`
- Transição: `200ms ease`

---

## 6. Estado vazio

Quando `$events` está vazio (nenhum evento publicado):

```
┌────────────────────────────────────────────┐
│                                            │
│          [ícone calendário 48px]           │
│                                            │
│       "Novos eventos em breve!"            │
│  "Acompanhe nossas redes sociais para      │
│   não perder nenhuma novidade."            │
│                                            │
└────────────────────────────────────────────┘
```

- Container centralizado, padding vertical `64px`
- Ícone SVG de calendário, 48px, `--color-text-muted`, `opacity: 0.3`, `aria-hidden="true"`
- Título: "Novos eventos em breve!", 18px, peso 600, `--color-text`, `margin-top: 16px`
- Subtítulo: texto acima, 14px, `--color-text-muted`, `max-width: 360px`, centralizado

---

## 7. Formatação de datas no Blade

Usar `Carbon` com locale `pt_BR`. Exemplos de implementação:

```php
// No AppServiceProvider ou via helper
Carbon\Carbon::setLocale('pt_BR');

// No Blade:
// starts_at e ends_at já são Carbon graças ao cast no Model
@php
    $start = $event->starts_at;
    $end   = $event->ends_at;

    if (!$end || $start->isSameDay($end)) {
        $dateLabel = $start->translatedFormat('j \d\e M. \d\e Y');
    } elseif ($start->isSameMonth($end)) {
        $dateLabel = $start->day . ' e ' . $end->translatedFormat('j \d\e M. \d\e Y');
    } else {
        $dateLabel = $start->translatedFormat('j \d\e M.') . ' a ' . $end->translatedFormat('j \d\e M. \d\e Y');
    }
@endphp
```

> **Fuso horário:** `starts_at` e `ends_at` são armazenados como horário local (app timezone = UTC). Exibir sem conversão — apenas formatar conforme pt-BR.

---

## 8. Animação de entrada

- O bloco do título: classe `section-hidden` (padrão do site via IntersectionObserver)
- Cada `<article>` de card: classe `section-hidden` com `transition-delay` escalonado via estilo inline:
  - 1º card: `transition-delay: 0ms`
  - 2º card: `transition-delay: 100ms`
  - 3º card: `transition-delay: 200ms`
  - e assim por diante, incrementando 100ms

```blade
@foreach($events as $i => $event)
    <article class="section-hidden" style="transition-delay: {{ $i * 100 }}ms">
        ...
    </article>
@endforeach
```

---

## 9. Acessibilidade

- `<h2>Eventos</h2>` com hierarquia correta após o `<h1>` implícito do hero e o `<h2>` do Sobre
- Cada card como `<article>` com `<h3>` para o nome do evento
- Imagem placeholder com `aria-hidden="true"` (decorativo)
- Badge "CFP Aberto" com `aria-label` explícito
- Link CTA com contexto suficiente: `aria-label="Ver evento: {{ $event->name }}"`
- Foco visível nos links/botões (outline `--color-primary`)

---

## 10. Responsividade

| Breakpoint | Grid | Comportamento |
|------------|------|---------------|
| Mobile (< 768px) | 1 coluna | Cards empilhados, largura 100% |
| Tablet (768px–1023px) | 2 colunas | 2 cards por linha |
| Desktop (≥ 1024px) | 3 colunas | 3 cards por linha |

Testar obrigatoriamente em **360px de largura** sem overflow horizontal.

---

## 11. Arquivos modificados

| Arquivo | Alteração |
|---------|-----------|
| `routes/web.php` | `GET /` agora passa `$events` para `view('welcome')` |
| `resources/views/welcome.blade.php` | Seção substituída; variáveis `$contactEmail` e `$twitterHandle` definidas via `@php` no topo |

> **Bug corrigido — `@` em Blade:** o template tinha `@phpcomrapadura` e `contato@phpcomrapadura.org` que o compilador Blade interpretava como diretiva `@php`. Solução: definir `$contactEmail = 'contato@phpcomrapadura.org'` e `$twitterHandle = '@phpcomrapadura'` via `@php` no topo e usar `{{ $var }}` em todos os pontos de uso. O `@@` como escape só funciona quando o segundo `@` inicia um diretivo reconhecido — não serve como escape genérico.

---

## 12. Critérios de aceite

- [ ] Seção tem `id="eventos"` e o link "Eventos" do menu faz scroll até ela
- [ ] Somente eventos com `status = 'publicado'` são exibidos
- [ ] Eventos com outros status (rascunho, encerrado, cancelado) **não** aparecem
- [ ] Ordenação: mais recente (por `starts_at`) primeiro
- [ ] Badge "CFP Aberto" exibido quando `is_accepting_talks = true`, oculto caso contrário
- [ ] Datas formatadas em pt-BR, cobrindo os três casos (mesmo dia, mesmo mês, meses diferentes)
- [ ] Local exibido; "Online" quando `is_online = true`
- [ ] Local truncado em 1 linha com ellipsis
- [ ] Botão "Ver evento" leva para `/{slug}` corretamente
- [ ] Estado vazio exibido quando não há eventos publicados
- [ ] Placeholder de imagem exibido quando `cover_image` é nulo
- [ ] Grid responsivo: 1 col mobile, 2 col tablet, 3 col desktop
- [ ] Hover no card com elevação de sombra e transição suave
- [ ] Hover no botão com troca de cor (outline → filled)
- [ ] Animações de entrada escalonadas via `section-hidden` / `section-visible`
- [ ] Nenhum overflow horizontal em 360px
