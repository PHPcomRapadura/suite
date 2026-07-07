# Spec — Artes de Divulgação para Redes Sociais (MVP)

**Status:** 📝 Proposto
**Módulo:** Admin → Eventos → Hub → Artes de Divulgação
**Depende de:** `.claude/specs/admin/events-details.md`, `.claude/specs/admin/event-site-spec.md`

---

## 1. Visão geral

Adicionar um novo card no hub do evento em `/admin/events/{id}` para gerar artes simples de divulgação para Instagram Stories e posts de feed, com base nas informações do evento e no tema visual já configurado no site do evento.

O objetivo do MVP é permitir que o organizador gere, visualize e baixe uma imagem pronta para publicação, sem necessidade de criar tudo manualmente no Canva ou no Photoshop.

---

## 2. Objetivo do MVP

No MVP inicial, o módulo deve:

- adicionar um card novo no hub do evento chamado “Artes de divulgação”;
- permitir gerar uma arte para:
  - Story Instagram (1080x1920)
  - Post de feed (1080x1080)
- usar automaticamente os dados do evento:
  - nome
  - data
  - local
  - descrição curta / tagline
  - logo e capa do evento
- usar as cores e o tema do site do evento, quando estiverem configurados;
- salvar a imagem gerada no storage (ou R2, se já estiver configurado no ambiente) e disponibilizar um link de download/preview.

---

## 3. Escopo do MVP

### 3.1 O que entra

- 1 card novo na página de detalhe do evento;
- 1 tela simples de geração de artes;
- 2 formatos iniciais:
  - Story
  - Post
- 1 template visual base para o MVP;
- uso de dados do evento e do tema configurado no site;
- geração via backend com biblioteca de imagens;
- preview da arte gerada na interface.

### 3.2 O que não entra no MVP

- múltiplos templates;
- edição manual avançada de texto;
- customização completa de layout;
- galeria histórica de artes;
- geração automática de várias variações por vez;
- integração direta com Instagram ou redes sociais.

---

## 4. Experiência do usuário

### 4.1 Card no hub do evento

No hub já existente em `/admin/events/{id}`, adicionar um novo card:

```text
┌──────────────────────────────┐
│ 📸  Artes de Divulgação      │
│ Gere Stories e posts do      │
│ evento com um template base. │
│ [Gerar artes →]              │
└──────────────────────────────┘
```

### 4.2 Tela de geração

Ao clicar em “Gerar artes”, o usuário entra numa tela simples com:

- seletor de formato: Story / Post;
- botão “Gerar imagem”;
- preview da arte gerada;
- botão “Baixar PNG”;
- mensagem de sucesso ou erro.

A tela deve ser leve e direta, sem muita configuração no primeiro momento.

---

## 5. Regras visuais do MVP

O template inicial deve seguir uma estética limpa e profissional, com:

- fundo com imagem de capa do evento ou um gradiente de fallback;
- overlay escuro para garantir legibilidade;
- nome do evento em destaque;
- data e local do evento;
- slogan/tagline do site do evento, se existir;
- logo do evento, se existir;
- CTA simples, como “Garanta sua vaga” ou “Veja mais”.

### 5.1 Fonte e tema

Se o evento tiver configuração de site, usar:

- cor primária
- cor secundária
- fonte escolhida
- tagline do hero

Se não houver site configurado, usar um tema padrão do sistema.

### 5.2 Formatos

- Story: 1080x1920, layout vertical
- Post: 1080x1080 ou 1080x1350, layout quadrado/vertical

---

## 6. Backend

### 6.1 Biblioteca recomendada

Usar a biblioteca `intervention/image` para gerar as imagens no backend.

Isso é o mais adequado para o MVP porque:

- é simples de integrar com Laravel;
- permite desenhar textos, imagens, overlays e gradientes;
- gera PNG de forma confiável.

### 6.2 Fluxo de geração

1. Receber o ID do evento e o formato solicitado.
2. Buscar o evento e seus dados básicos.
3. Buscar configuração do site, se existir.
4. Montar a composição visual com base no template do MVP.
5. Salvar o arquivo gerado no storage do sistema.
6. Retornar o link da imagem para o frontend.

### 6.3 Exemplo de fluxo

```php
POST /admin/api/events/{event}/social-assets/generate
```

Payload esperado:

```json
{
  "format": "story"
}
```

Resposta esperada:

```json
{
  "data": {
    "format": "story",
    "url": "/storage/events/123/social/story.png"
  }
}
```

---

## 7. Rotas e API

### 7.1 Rotas web

Adicionar rotas SPA para a página do módulo no admin:

```php
// routes/web.php
Route::get('/events/{id}/social-assets', fn () => view('admin'))->name('events.social-assets');
```

### 7.2 Rotas API

```http
GET    /admin/api/events/{event}/social-assets
POST   /admin/api/events/{event}/social-assets/generate
```

### 7.3 Responsabilidades

- `GET /admin/api/events/{event}/social-assets` → retorna os dados iniciais da tela (eventos, formatos, tema atual, estado de geração)
- `POST /admin/api/events/{event}/social-assets/generate` → gera a imagem e devolve a URL

---

## 8. Frontend

### 8.1 Nova view Vue

Criar uma nova tela em:

```text
resources/js/views/admin/EventSocialAssets.vue
```

### 8.2 Nova rota no Vue Router

Adicionar a rota no router admin:

```js
{
  path: 'events/:id/social-assets',
  name: 'admin.events.social-assets',
  component: () => import('@/views/admin/EventSocialAssets.vue'),
},
```

### 8.3 Alteração no hub do evento

No componente do hub de detalhes, adicionar um novo card com o botão:

```html
<RouterLink :to="{ name: 'admin.events.social-assets', params: { id: event.id } }">
  Gerar artes
</RouterLink>
```

---

## 9. Arquitetura sugerida

### 9.1 Backend

Criar os seguintes arquivos:

| Arquivo | Função |
|--------|--------|
| `app/Http/Controllers/Admin/EventSocialAssetController.php` | recebe a requisição e orquestra a geração |
| `app/Services/EventSocialAssetService.php` | monta a imagem e salva o asset |

### 9.2 Frontend

| Arquivo | Função |
|--------|--------|
| `resources/js/views/admin/EventSocialAssets.vue` | tela de geração e preview |

### 9.3 Testes

Criar testes de feature para garantir o fluxo básico:

- gerar Story com sucesso;
- gerar Post com sucesso;
- retornar erro quando o evento não existir;
- fallback simples quando não há imagem de capa ou logo.

---

## 10. Critérios de aceite

- [ ] O card “Artes de divulgação” aparece no hub do evento.
- [ ] Clicar no card abre a tela de geração de artes.
- [ ] O usuário consegue gerar uma arte para Story.
- [ ] O usuário consegue gerar uma arte para Post.
- [ ] A imagem gerada é salva e exibida em preview.
- [ ] O usuário consegue baixar a imagem gerada.
- [ ] A arte usa o nome, data, local e tema do evento.
- [ ] Se o evento não tiver site configurado, a arte usa um tema padrão.
- [ ] Se faltar dados como capa ou logo, o sistema usa fallback visual.
- [ ] A geração não quebra quando os dados do evento forem incompletos.

---

## 11. Observações de implementação

Para o MVP, a implementação deve priorizar simplicidade e confiabilidade sobre personalização.

A estratégia ideal é:

1. começar com 1 template base;
2. 2 formatos;
3. dados automáticos do evento;
4. geração no backend;
5. preview + download.

Esse caminho entrega valor rapidamente sem criar uma complexidade desnecessária.

---

## 12. Próximos passos após o MVP

Após a primeira versão, o módulo pode evoluir para:

- múltiplos templates;
- variações por layout do site;
- edição manual de texto;
- histórico de artes geradas;
- exportação para múltiplos tamanhos;
- integração com ferramentas de publicação.
