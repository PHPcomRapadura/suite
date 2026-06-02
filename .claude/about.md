Suite de aplicações de uso da comunidade PHP com Rapadura

## Módulos

### ✅ Site institucional (implementado — `resources/views/welcome.blade.php`)
Single-page Blade + Tailwind CSS v4 com as seções:
- **Hero** — logo centralizada, subtitle, scroll indicator, animação de entrada
- **Sobre** — texto da comunidade + parallax com foto da comunidade
- **Eventos** — placeholder "Em breve" (seção completa a implementar)
- **Código de Conduta** — compromisso + regras comportamentais atualizadas em 02/06/2026
- **Contato** — email com botão copiar + 6 redes sociais (Telegram, Instagram, Twitter/X, Facebook, GitHub, Flickr)
- **Footer** — fundo azul com marca d'água ilustração nordestina, logo branca, nav e copyright

Recursos transversais do site:
- Loader "Perainda!" com favicon girando
- Header fixo com scroll spy e menu mobile (drawer com focus trap)
- Back-to-top button contextual
- Transições de entrada (fade+slide) via IntersectionObserver
- SEO completo (OG, Twitter Card, JSON-LD, sitemap, robots.txt)
- Acessibilidade: skip link, aria-live, prefers-reduced-motion, aria-current

### ✅ Admin — Autenticação (implementado)

Área restrita em `/admin` com Vue.js 3 SPA + Laravel Sanctum:
- Login/logout com sessão + cookie HttpOnly
- Roles: `admin`, `colaborador`, `palestrante`
- Middleware `EnsureAdminRole` protege todas as rotas `/admin/*`
- Seed do primeiro admin via `ADMIN_EMAIL` / `ADMIN_PASSWORD` no `.env`
- 20 testes de feature cobrindo todos os cenários de autenticação

### Call for Papers (CFP) — a implementar
Sistema para que os palestrantes possam enviar suas propostas de palestras a um determinado evento. Esse módulo tem a seguinte característica: a rota /cfp ao ser acessada exibirá uma lista de eventos aceitando submissão de palestras. O usuário sem registro deve se cadastrar na plataforma; se já tiver cadastro basta realizar o login. Ao acessar o painel ele pode selecionar um evento disponível e preencher um formulário para submeter a palestra. Ao submeter, a palestra fica com status **Enviada** e no futuro pode ser **Aprovada** ou **Rejeitada** com feedback da organização.

### Eventos (Sistema gerenciado de eventos) — a implementar
Sistema de uso restrito por administradores da comunidade com controle de permissões. Os principais módulos são:
- Controle de eventos
- Controle de submissão de palestras por evento
- Controle de despesas por evento
- Controle de tarefas por evento (Kanban)
- Fórum com tópicos específicos relacionados a um evento
- Controle de participantes com upload de arquivo CSV
- Sorteio digital de acordo com os participantes de um evento
