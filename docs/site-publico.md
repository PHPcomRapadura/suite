# Site institucional e páginas de eventos

Estado revisado em 23/10/2026.

## Hero com referências do Ceará

A logo e a descrição ficam centralizadas. Na base, acima da seta de rolagem, cinco ilustrações em xilogravura aparecem lado a lado: Pedra da Galinha Choca, Iracema, Belchior, Ponte Metálica e Arena Castelão.

- Partial: `resources/views/partials/hero-ceara.blade.php`.
- Arte original: `public/images/hero-ceara-cordel.png`.
- Pedra e Iracema corrigidas a partir de fotos: `public/images/hero-ceara-cordel-v2.png`.
- Recortes SVG com `clipPath` preservam as proporções. As ilustrações são decorativas, não interceptam cliques e ficam ocultas para leitores de tela.
- Fileira com largura máxima de 1000px; recortes com altura de 132px, ou 72px abaixo de 640px. Opacidade: 7,5% no desktop, 6% no celular e 9% no modo escuro.
- Prompts e origem das artes: [hero-ceara-cordel.md](hero-ceara-cordel.md).

## Foto entre Sobre e Eventos

A faixa usa `public/images/comunidade-php-com-rapadura.jpg` (4110 × 2044), com carregamento adiado, degradê azul e borda inspirada em cordel.

O texto final é **“O próximo encontro também pode ter você.”**, seguido do link **“Veja os próximos eventos”** para `#eventos`. Ambos ficam centralizados. Não há subtítulo de apoio.

No desktop, a faixa tem altura mínima de 460px e a foto usa `object-fit: cover`. O movimento é limitado a 14px em cada direção, atualizado com `requestAnimationFrame` apenas enquanto a faixa está visível. No celular, a foto aparece inteira acima do texto. O parallax é desativado abaixo de 768px e com `prefers-reduced-motion: reduce`.

Implementação: `resources/views/welcome.blade.php`, `resources/css/app.css` e `resources/js/app.js`.

## Busca e compartilhamento

`$communityDescription`, no início de `welcome.blade.php`, é a fonte única para o texto do hero, meta description, descrições Open Graph/Twitter e `Organization.description` no JSON-LD:

> Grupo de desenvolvedores PHP do Ceará, formados através de uma ligação doce, como a rapadura e o café.

A seção `#codigo-de-conduta` tem `data-nosnippet`: permanece visível, mas não deve ser usada no resumo da busca. Isso não obriga o Google a reproduzir a descrição exatamente; o trecho pode variar conforme a pesquisa. Depois da publicação, uma nova indexação pode ser solicitada pelo Search Console.

As prévias de compartilhamento usam **a logo da comunidade**, em `public/images/phpcomrapadura-social.png` (1200 × 630, fundo claro), renderizada a partir do mesmo SVG usado no hero. `og:image`, `twitter:image` e `Organization.image` apontam para esse PNG. Ao trocar a arte, atualizar também suas dimensões, tipo e texto alternativo nos metadados. A foto da faixa é independente dessa prévia.

Referência: [controle de snippets do Google](https://developers.google.com/search/docs/appearance/snippet).

## Logos dos eventos

As logos preservam as proporções com `object-contain` e são limitadas à largura disponível:

| Layout | Celular | Tela maior |
|---|---|---|
| Clássico (`Layout1.vue`) | 224 × 128px | 288 × 160px a partir de 640px |
| Imersivo (`Layout2.vue`) | 256 × 160px | 320 × 192px a partir de 768px |

Os valores descrevem a caixa da imagem, incluindo o preenchimento interno. O Minimalista mantém a logo compacta no cabeçalho.

## Como chegar

`resources/js/components/EventDirections.vue` é montado uma vez em `EventSiteApp.vue`, fora do layout selecionado. O botão aparece nos três layouts quando `event.is_online` é falso e `event.location`, após remover espaços das extremidades, não está vazio.

O painel apresenta o endereço e as opções Uber, Google Maps e Waze com ícones. No campo **Local** do cadastro do evento, informar estabelecimento, rua, número, bairro, cidade e estado; esse texto é enviado aos aplicativos com codificação de URL.

| Plataforma | Abertura |
|---|---|
| Desktop | Links HTTPS em nova aba |
| iPhone/iPad | `uber://riderequest`, `comgooglemaps://` e `waze://`, na mesma aba |
| Android | Links `intent:` com pacote do aplicativo e `browser_fallback_url` |

Após selecionar um aplicativo no celular, o painel oferece **“Não abriu? Usar [aplicativo] no navegador”**. Não há redirecionamento automático por temporizador. A troca para outro app não fecha o painel quando o iOS emite perda de foco sem elemento de destino.

O botão fica à direita, acima do botão de voltar ao topo, respeitando a área segura do dispositivo. O painel fecha pelo botão, por clique fora, por foco movido para fora ou por Escape; Escape devolve o foco ao botão. O painel suporta telas estreitas e endereços longos.

### Limitação aceita do Uber

O usuário confirmou a abertura dos três aplicativos no Safari e no Chrome do iPhone. No Uber, **o campo “Para onde?” não é preenchido**, apesar do envio de `dropoff[formatted_address]` e `dropoff[nickname]`. O usuário preenche o destino no aplicativo.

Em 23/10/2026, foi decidido manter esse comportamento: **não adicionar latitude, longitude, geocodificação ou novos campos ao cadastro**. A implementação não deve ser descrita como preenchimento garantido do destino no Uber. Não houve alteração de código após essa decisão.

Referências: [Uber](https://developer.uber.com/docs/riders/ride-requests/tutorials/deep-links/introduction), [Google Maps no iOS](https://developers.google.com/maps/documentation/urls/ios-urlscheme), [Waze](https://developers.google.com/waze/deeplinks) e [Android Intents no Chrome](https://developer.chrome.com/docs/android/intents).

## Validação

Com os containers disponíveis em `http://localhost:8000`, executar:

```bash
npm run build
npx playwright test tests/e2e/event-directions.spec.js --reporter=line --output=/tmp/phpcomrapadura-e2e
git diff --check
```

Os dez testes cobrem os três layouts, codificação de endereço com acentos e caracteres especiais, abertura/fechamento, teclado, eventos online ou sem local, tela pequena, links nativos no iPhone e intents no Android.

Os cenários chamados Safari/Chrome no iPhone usam user agents e eventos de toque simulados no Chromium. **Não são testes em Safari/WebKit ou de abertura real de apps instalados.** A abertura real no iPhone foi confirmada manualmente pelo usuário; a abertura real no Android não foi confirmada nesta sessão.

Os ajustes visuais e os metadados também foram conferidos no navegador local. Mudanças apenas na documentação não exigem repetir o build ou a suíte de testes.
