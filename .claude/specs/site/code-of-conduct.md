# Spec — Código de Conduta

**Status:** ✅ Implementado — `resources/views/welcome.blade.php`

**Rota:** `GET /` (âncora `#codigo-de-conduta`)
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Estrutura geral da seção

```
┌─────────────────────────────────────────┐
│           CÓDIGO DE CONDUTA             │
│              (fundo branco)             │
│                                         │
│  [Título h2]                            │
│                                         │
│  [Bloco de compromisso — destaque]      │
│                                         │
│  [Lista de regras numeradas 1–7, 42]    │
│                                         │
│  [Frase de encerramento]                │
└─────────────────────────────────────────┘
```

- `id="codigo-de-conduta"` (obrigatório para âncora do menu)
- `min-height: 100vh` — ocupa tela cheia como as demais seções
- Fundo: `--color-surface` (`#ffffff`) para contrastar com as seções vizinhas
- Conteúdo centralizado com `max-width: 1024px`
- Padding vertical: `96px` (desktop) / `64px` (mobile)
- Padding horizontal: `24px`

---

## 2. Título

- Texto: **"Código de Conduta"**
- Elemento: `<h2>`
- Fonte: Lexend, 32px (desktop) / 26px (mobile), peso 700
- Cor: `--color-text`
- Alinhamento: centralizado
- Margin bottom: `48px`

---

## 3. Bloco de compromisso

Exibir como um bloco destacado (card ou blockquote estilizado) com:

- Fundo: `--color-primary` com 8% de opacidade (`--color-primary` / `0.08`)
- Borda esquerda: 4px sólida em `--color-primary`
- Padding: `24px`
- Border-radius: `8px`
- Margin bottom: `48px`

**Conteúdo** (4 parágrafos em sequência, separados por `margin-bottom: 12px`):

> A **PHP com Rapadura** está comprometida a ser uma comunidade livre de discriminação para todos, independente de gênero, identidade e expressão de gênero, orientação sexual, deficiência, aparência física, raça, idade, origem ou religião.

> Não será tolerado, sob nenhuma circunstância, o constrangimento de participantes em eventos e canais de comunicação organizados pela comunidade.

> Linguagem e/ou exposição de imagens sexuais ou de cunho discriminatório não serão apropriadas no âmbito de nossa comunidade — isto inclui os eventos, as palestras, apresentações, exemplos ou demonstrações, site, emails, chats, telegram e quaisquer outro canal da comunidade.

> Os participantes que violarem estas regras podem ser repreendidos ou expulsos do canal e/ou evento sem direito a reembolso, a critério da organização.

### 3.1 Sub-bloco "Entendemos como assédio"

Dentro do mesmo bloco, após os parágrafos, adicionar:

**Título inline:** "Entendemos como assédio e constrangimento:" (peso 600)

**Lista** (`<ul>`) com marcadores em `--color-primary`:
- Comentários que reforçam estruturas sociais de dominação, sejam relacionados a gênero, identidade e expressão de gênero, orientação sexual, deficiência, aparência física, raça, idade, origem ou religião
- Imagens sexuais em espaços públicos ou privados
- Intimidação intencional
- Perseguição
- Obtenção de som ou imagem constrangedores
- Interrupção continuada e intencional de palestras e/ou eventos paralelos
- Contato físico inapropriado; atenção sexual não solicitada

---

## 4. Regras da comunidade

### 4.1 Subtítulo

- Texto: "Regras da comunidade"
- Elemento: `<h3>`
- Fonte: Lexend, 22px, peso 600
- Cor: `--color-text`
- Margin bottom: `24px`

Antes do subtítulo, exibir o aviso em destaque:

> Antes de postar ou interagir, **leia com atenção** todas as regras da comunidade #PHPcomRapadura. Caso a sua publicação não apareça no grupo, provavelmente ela infringiu uma das regras listadas abaixo.

### 4.2 Lista de regras

Exibir como lista numerada com cada regra em card separado ou linha com número destacado em `--color-primary`.

Cada item: número em destaque (peso 700, cor `--color-primary`) + título em negrito + descrição.

| # | Título | Descrição |
|---|--------|-----------|
| 1 | Respeito | Tenha cuidado com sua mensagem, veja se ela não vai assediar e/ou desrespeitar alguém. |
| 2 | Conteúdo | Poste apenas conteúdo que possua, de alguma forma, relação com o assunto abordado pela comunidade ou alguma ferramenta envolvendo as tecnologias. Assuntos fora do contexto serão deletados e o membro notificado. Havendo persistência, o autor será banido sem aviso prévio. Exemplos aceitos: dúvidas técnicas, mercado de trabalho, dicas para desenvolvedores, eventos de tecnologia. |
| 3 | Clareza | Preze pela objetividade e clareza nas informações do seu post. |
| 4 | Códigos | Caso o seu post possua códigos, utilize CodePen, JSBin, JSFiddle ou GitHub para demonstrá-los. |
| 5 | Organização | Para garantir a boa organização do grupo, adicione o tipo de post antes da postagem quando necessário. Ex.: [OFF], [EVENTO], [VAGA], [DÚVIDA]. |
| 6 | Anúncios | Não serão permitidos anúncios de compra e venda de produtos. Também não serão aceitos sites hacks ou links de templates pagos — pirataria é crime. Havendo persistência, o autor será banido sem aviso prévio. |
| 7 | Denúncia | Caso alguma publicação vá contra as regras aqui mencionadas, favor marcar o nome dos admins e/ou marcar como "Denunciar/Spam". |
| 42 | Alimentação *(easter egg)* | Todos os membros devem comer uma rapadura sem beber água para provar seu merecimento de estar no grupo... |

> **Nota:** A regra 42 é um easter egg da comunidade — deve ser renderizada igual às demais, sem destaque especial.

### 4.3 Frase de encerramento

Após as regras, exibir em destaque:

> **Aproveitem saudavelmente a comunidade!**

- Fonte: Lexend, 18px, peso 600
- Cor: `--color-primary`
- Alinhamento: centralizado
- Margin top: `48px`

---

## 5. Tipografia das regras

- Número: Lexend, 20px, peso 700, cor `--color-primary`
- Título da regra: Lexend, 16px, peso 600, cor `--color-text`
- Descrição: Lexend, 15px (mobile) / 16px (desktop), peso 400, cor `--color-text`, line-height 1.7

---

## 6. Responsividade

| Breakpoint | Comportamento |
|------------|---------------|
| Mobile (< 768px) | Padding 64px vertical, título h2 26px, regras em lista vertical simples |
| Desktop (≥ 768px) | Padding 96px vertical, título h2 32px |

Testar sem overflow horizontal em **360px** de largura.

---

## 7. Acessibilidade

- `<h2>` com hierarquia correta (após hero e sobre)
- `<h3>` para "Regras da comunidade" como sub-hierarquia
- Lista de assédio com `<ul>` semântico
- Links para ferramentas externas (CodePen, JSBin, etc.) com `target="_blank"` e `rel="noopener noreferrer"`

---

## 8. Critérios de aceite

- [ ] Seção tem `id="codigo-de-conduta"` e o link do menu ancora corretamente
- [ ] Seção ocupa `min-height: 100vh`
- [ ] Fundo branco contrasta com as seções vizinhas
- [ ] Bloco de compromisso com borda azul à esquerda e fundo azul claro visíveis
- [ ] Lista de assédio com 7 itens renderizada dentro do bloco
- [ ] 8 regras exibidas (1–7 + 42) com número, título e descrição
- [ ] Regra 42 presente como easter egg sem destaque especial
- [ ] Frase de encerramento em azul e centralizada
- [ ] Nenhum overflow horizontal em 360px
