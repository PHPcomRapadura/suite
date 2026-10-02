# Graph Report - phpcomrapadura-suite  (2026-10-02)

## Corpus Check
- 258 files · ~453,624 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 26 file(s) not represented in the graph (top: (none) 13, .ttf 6, .example 1)

## Summary
- 1793 nodes · 3115 edges · 131 communities (84 shown, 47 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 79 edges (avg confidence: 0.85)
- Token cost: 194,782 input · 0 output

## Community Hubs (Navigation)
- Social Assets Generation
- Database Migrations
- Event Site Admin UI
- Event Module Controllers
- Lottery Draw UI
- Models & Auth Service
- Site JS & Admin Shell
- Kanban Board UI
- Account & Login Controllers
- Task Comments Backend
- NPM Dependencies
- Expenses UI
- Controllers & Role Middleware
- Talk Submission UI
- User Management Backend
- Task Modal & Comments UI
- Admin CFP Review UI
- Events List UI
- CRUD Pattern Docs
- Social Assets UI
- Participants UI
- Expenses Spec
- Event & Task Controllers
- Speakers List UI
- Event Modal Upload
- Public Layout Classic
- Public Layout Immersive
- Event Site Spec
- Participant & Schedule Factories
- CFP SPA Shell
- Speaker Profile UI
- Public Layout Minimal
- Admin Router
- CFP & Talk Admin Controllers
- Lottery & Participants Models
- Institutional Site Specs
- Eloquent Models
- CFP Auth Composable
- Event Detail Hub
- Speakers Spec
- Config & Base Factories
- Participant CSV Upload
- Shared Modals
- Schedule & Site Config Models
- Update Requests
- Delivered Modules Overview
- Admin Auth & Events Spec
- Social Assets Controller
- Talk Submission Backend
- Composer Dev Deps
- Event Store Requests
- Theme & Event Site SPA
- Change Password Modal
- CFP Module Spec
- Frontend Design Patterns
- Password Reset Mail
- Talk Review Modal
- Speaker Dashboard
- CFP Login
- Security Guidelines
- Speaker Service
- Rate Limiting Provider
- Composer Metadata
- Composer Scripts
- Task Factory
- Talk Factory
- Test Bootstrap
- Brand Identity Assets
- CFP Config Modal
- Reset Password UI
- Dashboard Spec
- Kanban Spec
- Public CFP Spec
- Schedule Controller
- CFP Model & Tests
- Docker & Quality Hooks
- Event Factory
- Database Seeders
- Speaker Registration
- EventSponsorController
- pestphp/pest-plugin
- require
- EventExpenseFactory
- CfpMyEvents
- Controle de Participantes (import CSV)
- Spec: Controle de Participantes
- Spec: CRUD de Usuarios
- StoreCfpRequest
- UpdateCfpRequest
- DashboardController
- UploadParticipantsRequest
- StoreEventSiteRequest
- StoreTaskRequest
- StoreUserRequest
- SpeakerProfileController
- GenerateEventSocialAssetRequest
- Talk
- EventCfpFactory
- EventSiteController
- UpdateTaskRequest
- UpdateUserRequest
- UpdatePasswordRequest
- StoreEventScheduleItemRequest
- StoreEventSponsorRequest
- UpdateEventScheduleItemRequest
- UpdateEventSponsorRequest
- StoreExpenseRequest
- LoginRequest
- UpdateAccountRequest
- UpdateSpeakerProfileRequest
- autoload
- logging
- useAuth.js
- PHPUnit\Framework\TestCase
- application
- autoload-dev
- extra
- Illuminate\Foundation\Inspiring
- fetchApprovedTalks()

## God Nodes (most connected - your core abstractions)
1. `Event` - 166 edges
2. `User` - 75 edges
3. `Controller` - 48 edges
4. `vue` - 45 edges
5. `SocialAssetCanvas` - 41 edges
6. `EventTask` - 34 edges
7. `axios` - 34 edges
8. `Talk` - 31 edges
9. `Speaker` - 26 edges
10. `vue-router` - 21 edges

## Surprising Connections (you probably didn't know these)
- `Spec: CRUD de Usuarios` --conceptually_related_to--> `Padrao de CRUD (cards grid, 9/page, modal, ConfirmModal, toggle)`  [INFERRED]
  .claude/specs/admin/user-crud-spec.md → CLAUDE.md
- `README Suite` --references--> `Spec: Controle de Participantes`  [INFERRED]
  README.md → .claude/specs/admin/participants-spec.md
- `README Suite` --references--> `Spec: Listagem de Palestrantes`  [INFERRED]
  README.md → .claude/specs/admin/speakers-spec.md
- `README Suite` --references--> `Spec: Kanban de Tarefas`  [INFERRED]
  README.md → .claude/specs/admin/tasks-kanban-spec.md
- `README Suite` --references--> `Spec: CRUD de Usuarios`  [INFERRED]
  README.md → .claude/specs/admin/user-crud-spec.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Upload de arquivos para R2 via Service (eventos, despesas, artes)** — _claude_specs_admin_events_spec_eventservice, _claude_specs_admin_expenses_spec_eventexpenseservice, _claude_specs_admin_event_social_assets_spec_eventsocialassetservice, _claude_specs_admin_events_spec_r2_disk, _claude_patterns_crud_patterns_r2_upload_pattern [INFERRED 0.85]
- **Sub-módulos acessados pelo hub do evento** — _claude_specs_admin_events_details_eventdetail_vue, _claude_specs_admin_events_cfp_eventcfp_vue, _claude_specs_admin_expenses_spec_eventexpenses_vue, _claude_specs_admin_lottery_spec_eventlottery_vue, _claude_specs_admin_event_social_assets_spec_eventsocialassets_vue, _claude_specs_admin_event_site_spec_eventsite_vue [INFERRED 0.85]
- **Controle de acesso por role (admin/colaborador/palestrante)** — _claude_specs_admin_auth_spec_roles, _claude_specs_admin_auth_spec_ensureadminrole, _claude_about_ensurespeaker, _claude_specs_admin_events_spec_toggle_talks, _claude_specs_admin_lottery_spec_eventlotterycontroller [INFERRED 0.85]
- **Event hub submodules (participants, tasks) scoped by event_id** — _claude_specs_admin_participants_spec_eventparticipant, _claude_specs_admin_tasks_kanban_spec_eventtask, _claude_specs_admin_participants_spec_eventparticipantcontroller, _claude_specs_admin_tasks_kanban_spec_eventtaskcontroller, claude_crud_pattern [INFERRED 0.75]
- **Institutional single-page site sections** — _claude_specs_site_hero_spec, _claude_specs_site_about_spec, _claude_specs_site_events_spec, _claude_specs_site_code_of_conduct, _claude_specs_site_contact_spec, _claude_specs_site_footer_spec, claude_welcome_blade_php [EXTRACTED 1.00]
- **CFP speaker submission flow** — _claude_specs_cfp_home_spec_cfp_vue_spa, _claude_specs_cfp_home_spec_cfppubliccontroller, _claude_specs_cfp_submit_talk_spec_ensurespeaker, _claude_specs_cfp_submit_talk_spec_talksubmissioncontroller, resources_js_views_cfp_submittalk, _claude_specs_cfp_submit_talk_spec_speakerprofilecontroller [INFERRED 0.85]
- **PHP com Rapadura Brand Identity Assets** — public_images_phpcomrapadura_color_logo, public_images_phpcomrapadura_branca_logo, public_images_favicon_favicon, public_images_phpcomrapadura_color_elephpant_mascot [INFERRED 0.85]

## Communities (131 total, 47 thin omitted)

### Community 0 - "Social Assets Generation"
Cohesion: 0.08
Nodes (17): EventSponsor, EventSocialAssetService, EventMeta, SocialAssetCanvas, AnnouncementTemplate, SellingOutTemplate, SocialAssetTemplate, SpeakerSpotlightTemplate (+9 more)

### Community 1 - "Database Migrations"
Cohesion: 0.05
Nodes (4): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Schema

### Community 2 - "Event Site Admin UI"
Cohesion: 0.04
Nodes (32): appearanceForm, approvedTalks, contentForm, copied, deletingScheduleItem, deletingSponsor, editingScheduleItem, editingSponsor (+24 more)

### Community 3 - "Event Module Controllers"
Cohesion: 0.06
Nodes (8): EventExpenseController, EventLotteryController, EventParticipantController, Event, EventParticipantService, Carbon\Carbon, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Validation\ValidationException

### Community 4 - "Lottery Draw UI"
Cohesion: 0.05
Nodes (40): ref_components_confirmmodal_vue, ref_components_usermodal_vue, closeOverlay(), countdown, delay(), doDraw(), doReset(), drawError (+32 more)

### Community 5 - "Models & Auth Service"
Cohesion: 0.08
Nodes (9): EventExpense, AdminAuthService, EventExpenseService, EventService, Illuminate\Auth\AuthenticationException, Illuminate\Http\UploadedFile, Illuminate\Pagination\LengthAwarePaginator, Illuminate\Support\Facades\Storage (+1 more)

### Community 6 - "Site JS & Admin Shell"
Cohesion: 0.05
Nodes (31): ref_components_appsidebar_vue, ref_components_changepasswordmodal_vue, ref_composables_useauth, ref_composables_usetheme, closeMenu(), getFocusableInMenu(), openMenu(), trapFocus() (+23 more)

### Community 7 - "Kanban Board UI"
Cohesion: 0.06
Nodes (28): ref_components_taskmodal_vue, ref_composables_useauth_js, activeTab, assignees, board, COLUMNS, confirmTask, deleteTask() (+20 more)

### Community 8 - "Account & Login Controllers"
Cohesion: 0.09
Nodes (13): AccountController, AdminLoginController, SpeakerController, CfpPublicController, AccountController, CfpPasswordResetController, Controller, EventSitePublicController (+5 more)

### Community 9 - "Task Comments Backend"
Cohesion: 0.11
Nodes (5): EventTaskCommentController, EventTask, EventTaskComment, EventTaskService, EventTaskCommentFactory

### Community 10 - "NPM Dependencies"
Cohesion: 0.07
Nodes (30): dependencies, axios, canvas-confetti, @vitejs/plugin-vue, vue, vue-router, devDependencies, concurrently (+22 more)

### Community 11 - "Expenses UI"
Cohesion: 0.06
Nodes (25): ref_components_expensemodal_vue, CATEGORIES, CATEGORY_COLORS, confirmDelete, currency, deleteLoading, deleteTarget, doDelete() (+17 more)

### Community 12 - "Controllers & Role Middleware"
Cohesion: 0.14
Nodes (14): CheckRole, Response, EnsureAdminRole, Response, EnsureSpeaker, Response, SecurityHeaders, Closure (+6 more)

### Community 13 - "Talk Submission UI"
Cohesion: 0.07
Nodes (20): cfp, cfpStatus, durationLabel, editingId, errors, event, form, guideOpen (+12 more)

### Community 14 - "User Management Backend"
Cohesion: 0.10
Nodes (7): UserController, User, UserService, Illuminate\Database\Eloquent\Relations\HasOne, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, Laravel\Sanctum\HasApiTokens

### Community 15 - "Task Modal & Comments UI"
Cohesion: 0.08
Nodes (20): activeTab, commentBody, comments, confirmDeleteComment, deleteComment(), editingCommentBody, editingCommentId, emit (+12 more)

### Community 16 - "Admin CFP Review UI"
Cohesion: 0.09
Nodes (24): ref_components_cfpmodal_vue, ref_components_talkreviewmodal_vue, cfp, cfpLoading, cfpStatusBadge, cfpStatusLabel, eventId, fetchCfp() (+16 more)

### Community 17 - "Events List UI"
Cohesion: 0.09
Nodes (21): ref_components_eventmodal_vue, actionLoading, cancellingEvent, currentYear, editingEvent, events, executeCancel(), fetchEvents() (+13 more)

### Community 18 - "CRUD Pattern Docs"
Cohesion: 0.08
Nodes (26): Padrão de CRUDs, Proteção de auto-ação (não excluir/inativar a si mesmo), Listagem em grid de cards (9 por página, sem tabelas), Rota Laravel web para cada rota Vue (F5), Method spoofing para PUT com FormData, Proteção multi-tenant por company_id, Upload para Cloudflare R2 (Service + Storage::fake), Componentes reutilizáveis (FormInput, FormButton, Toggle, ConfirmModal) (+18 more)

### Community 19 - "Social Assets UI"
Cohesion: 0.08
Nodes (20): assetsList, canGenerate, currentAsset, error, event, generatedAtLabel, generating, loading (+12 more)

### Community 20 - "Participants UI"
Cohesion: 0.10
Nodes (20): ref_components_participantuploadmodal_vue, clearAll(), debounceFetch(), deleteParticipant(), event, fetchParticipants(), filters, goToPage() (+12 more)

### Community 21 - "Expenses Spec"
Cohesion: 0.10
Nodes (19): Spec — Controle de Despesas, Model EventExpense (enum category), EventExpenseController, EventExpenses.vue (toggle cards/lista + totais), EventExpenseService (comprovante no R2), amountDisplay, CATEGORIES, centsToDisplay() (+11 more)

### Community 22 - "Event & Task Controllers"
Cohesion: 0.14
Nodes (4): EventController, EventTaskController, CfpAuthController, Illuminate\Http\JsonResponse

### Community 23 - "Speakers List UI"
Cohesion: 0.09
Nodes (14): ref_components_speakermodal_vue, city, currentPage, lastPage, loading, modalOpen, pages, search (+6 more)

### Community 24 - "Event Modal Upload"
Cohesion: 0.09
Nodes (14): coverFile, coverPreview, emit, errors, form, isEditing, loading, logoFile (+6 more)

### Community 25 - "Public Layout Classic"
Cohesion: 0.10
Nodes (14): activeDay, activeSection, formatDate(), formatPeriod(), levelLabels, levelOrder, navVisible, openFaq (+6 more)

### Community 26 - "Public Layout Immersive"
Cohesion: 0.10
Nodes (14): activeDay, activeSection, formatDate(), formatPeriod(), levelLabels, levelOrder, navVisible, openFaq (+6 more)

### Community 27 - "Event Site Spec"
Cohesion: 0.14
Nodes (21): Spec — Site Público do Evento, Model EventScheduleItem (grade de programação), EventSite.vue (admin), Model EventSiteConfig (event_site_configs), Model EventSponsor (event_sponsors, tiers), Campo faq (JSON) com accordion ARIA, Nav sticky + scroll spy + aria-current, Patrocinadores por tier (rapadura_com_castanha > coco > ...) (+13 more)

### Community 28 - "Participant & Schedule Factories"
Cohesion: 0.12
Nodes (8): EventParticipantFactory, static, EventScheduleItemFactory, EventSiteConfigFactory, static, EventSponsorFactory, SpeakerFactory, Illuminate\Database\Eloquent\Factories\Factory

### Community 29 - "CFP SPA Shell"
Cohesion: 0.11
Nodes (12): ref_composables_usecfpauth, vue-router, router, routes, nav, router, sidebarOpen, { user, fetchUser, logout } (+4 more)

### Community 30 - "Speaker Profile UI"
Cohesion: 0.10
Nodes (15): accountErrors, accountForm, accountSuccess, avatarFile, avatarPreview, loading, profileErrors, profileForm (+7 more)

### Community 31 - "Public Layout Minimal"
Cohesion: 0.10
Nodes (13): activeDay, activeSection, formatDate(), formatPeriod(), levelLabels, levelOrder, openFaq, props (+5 more)

### Community 32 - "Admin Router"
Cohesion: 0.11
Nodes (4): ref_views_auth_login_vue, app, router, routes

### Community 33 - "CFP & Talk Admin Controllers"
Cohesion: 0.13
Nodes (5): CfpController, TalkController, CfpService, TalkService, InvalidArgumentException

### Community 34 - "Lottery & Participants Models"
Cohesion: 0.15
Nodes (4): EventLotteryWinner, EventParticipant, EventLotteryService, Illuminate\Database\Eloquent\Builder

### Community 35 - "Institutional Site Specs"
Cohesion: 0.12
Nodes (18): Spec: Sobre (About), Spec: Codigo de Conduta, Spec: Contato, Spec: Eventos (Site), WelcomeController (published events, starts_at DESC), Spec: Footer, Floating back-to-top button, Spec: Hero (+10 more)

### Community 36 - "Eloquent Models"
Cohesion: 0.24
Nodes (5): Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo, Illuminate\Database\Eloquent\SoftDeletes, Illuminate\Support\Carbon

### Community 37 - "CFP Auth Composable"
Cohesion: 0.11
Nodes (12): axios, loaded, useCfpAuth(), user, error, form, loading, showPassword (+4 more)

### Community 38 - "Event Detail Hub"
Cohesion: 0.12
Nodes (15): cfp, cfpStatus, cfpTalks, event, formatDate(), formatPeriod(), loading, lottery (+7 more)

### Community 39 - "Speakers Spec"
Cohesion: 0.15
Nodes (13): EventParticipants.vue, Spec: Listagem de Palestrantes, SpeakerController (admin, read-only), Speakers.vue, SpeakerService (withCount talks), Cards/Lista view toggle persisted in localStorage, emit, levelLabel (+5 more)

### Community 40 - "Config & Base Factories"
Cohesion: 0.18
Nodes (4): static, UserFactory, Illuminate\Support\Str, Pdo\Mysql

### Community 41 - "Participant CSV Upload"
Cohesion: 0.18
Nodes (12): emit, fileError, fileInput, isDragging, loading, onClose(), onDrop(), onFileChange() (+4 more)

### Community 42 - "Shared Modals"
Cohesion: 0.17
Nodes (11): EventTasks.vue (Kanban board), Users.vue, emit, emit, errors, form, isEditing, loading (+3 more)

### Community 43 - "Schedule & Site Config Models"
Cohesion: 0.17
Nodes (4): EventScheduleItem, EventSiteConfig, Illuminate\Http\Response, Illuminate\Http\Testing\File

### Community 44 - "Update Requests"
Cohesion: 0.15
Nodes (3): UpdateEventRequest, UpdateExpenseRequest, Illuminate\Validation\Rule

### Community 45 - "Delivered Modules Overview"
Cohesion: 0.26
Nodes (12): About — Módulos entregues, Recuperação de senha CFP (CfpPasswordResetController + CfpResetPasswordMail), CFP público (SPA palestrante), Rodapé de patrocínio nas artes (drawSponsorFooter), Middleware EnsureSpeaker, Kanban de Tarefas (5 colunas, DnD HTML5), Lexend estática em 5 pesos (OFL), Site institucional (welcome.blade.php) (+4 more)

### Community 46 - "Admin Auth & Events Spec"
Cohesion: 0.26
Nodes (12): Spec — Autenticação do Admin, Seed do primeiro admin, AdminLoginController, Middleware EnsureAdminRole, Roles: admin, colaborador, palestrante, Model User, Spec — CRUD de Eventos, Model Event (+4 more)

### Community 47 - "Social Assets Controller"
Cohesion: 0.24
Nodes (3): EventSocialAssetController, EventSocialAsset, Symfony\Component\HttpFoundation\StreamedResponse

### Community 49 - "Composer Dev Deps"
Cohesion: 0.17
Nodes (12): require-dev, captainhook/captainhook, fakerphp/faker, larastan/larastan, laravel/pail, laravel/pao, laravel/pint, mockery/mockery (+4 more)

### Community 50 - "Event Store Requests"
Cohesion: 0.22
Nodes (3): StoreEventRequest, UpdateEventSiteRequest, Illuminate\Foundation\Http\FormRequest

### Community 51 - "Theme & Event Site SPA"
Cohesion: 0.22
Nodes (7): vue, isDark, toggle(), useTheme(), rawData, layouts, props

### Community 52 - "Change Password Modal"
Cohesion: 0.20
Nodes (9): emit, errors, form, loading, props, showCurrent, showNew, submit() (+1 more)

### Community 53 - "CFP Module Spec"
Cohesion: 0.31
Nodes (10): Módulo Palestrantes (SpeakerService, SpeakerModal), Spec — Módulo CFP, CfpController, CfpService, Model EventCfp, Model Speaker, Model Talk, Transições de status das palestras (+2 more)

### Community 54 - "Frontend Design Patterns"
Cohesion: 0.22
Nodes (10): Padrões de Front (Design System), Checklist mínimo de acessibilidade, Estratégia de dark mode (Tailwind), Diretrizes mobile-friendly (alvos 40px), Loader "Perainda!" com Suspense @resolve, Loader de progresso (top bar, skeleton, spinner), Tokens semânticos de cor (CSS variables), Padrões de SEO para novas páginas (+2 more)

### Community 55 - "Password Reset Mail"
Cohesion: 0.31
Nodes (6): CfpResetPasswordMail, Illuminate\Bus\Queueable, Illuminate\Mail\Mailable, Illuminate\Mail\Mailables\Content, Illuminate\Mail\Mailables\Envelope, Illuminate\Queue\SerializesModels

### Community 56 - "Talk Review Modal"
Cohesion: 0.22
Nodes (9): actions, apply(), emit, errors, feedback, loading, props, requiresFeedback (+1 more)

### Community 57 - "Speaker Dashboard"
Cohesion: 0.20
Nodes (6): events, loading, router, statCards, stats, { user, fetchUser }

### Community 58 - "CFP Login"
Cohesion: 0.22
Nodes (9): error, form, handleLogin(), loading, redirectAfterLogin(), roleWarning, route, router (+1 more)

### Community 59 - "Security Guidelines"
Cohesion: 0.22
Nodes (9): Skill — Segurança, Laravel Sanctum (autenticação API), Proteção contra mass assignment ($fillable), Rate limiting (throttle), Headers de segurança, Proteção contra SQL injection, Proteção XSS / JSON em Blade, Rota pública GET /{slug} (EventSitePublicController) (+1 more)

### Community 61 - "Rate Limiting Provider"
Cohesion: 0.28
Nodes (4): AppServiceProvider, Illuminate\Cache\RateLimiting\Limit, Illuminate\Support\Facades\RateLimiter, Illuminate\Support\ServiceProvider

### Community 62 - "Composer Metadata"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 63 - "Composer Scripts"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 66 - "Test Bootstrap"
Cohesion: 0.33
Nodes (4): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 67 - "Brand Identity Assets"
Cohesion: 0.22
Nodes (9): Favicon (Elephpant with RAPADURA hat), Cordel / Northeastern Brazilian Visual Identity, Footer Xilogravura (Nordeste Woodcut Banner), PHP com Rapadura White Logo (SVG), Brand Primary Blue #025C98, Elephpant Mascot with Leather Cangaceiro Hat, PHP com Rapadura Color Logo (SVG), Community Group Photo (Meetup at Centro Universitario Estacio) (+1 more)

### Community 68 - "CFP Config Modal"
Cohesion: 0.25
Nodes (7): emit, errors, form, isEditing, loading, props, submit()

### Community 69 - "Reset Password UI"
Cohesion: 0.22
Nodes (7): error, form, loading, route, router, showPassword, success

### Community 70 - "Dashboard Spec"
Cohesion: 0.36
Nodes (8): Spec — Dashboard & Layout do Admin, AdminLayout.vue, AppSidebar.vue (colapsável), DashboardController (stats, next-event, activity), Card "Próximo evento" com countdown, Feed "Atividade recente", Composable useAuth, Padrão withCount sem join

### Community 71 - "Kanban Spec"
Cohesion: 0.46
Nodes (8): Spec: Kanban de Tarefas, EventTask model (soft delete), EventTaskComment model, EventTaskCommentController, EventTaskController, EventTaskService (DnD, soft delete, restore), Kanban columns: a_fazer, em_andamento, em_revisao, impedimento, concluida, Soft delete with Lixeira and restore

### Community 72 - "Public CFP Spec"
Cohesion: 0.36
Nodes (8): Spec: Pagina Publica do CFP, CFP Vue SPA (cfp.js / cfp.blade.php, separate from admin), CfpPublicController, Spec: Submissao de Palestra e Perfil, EnsureSpeaker middleware, SpeakerProfileController, StoreTalkRequest, TalkSubmissionController

### Community 75 - "Docker & Quality Hooks"
Cohesion: 0.39
Nodes (7): CaptainHook quality hooks (Pint, Larastan, Pest, audits), docker service app, docker service mysql, docker service nginx, docker service phpmyadmin, docker service redis, README Suite

### Community 77 - "Database Seeders"
Cohesion: 0.36
Nodes (4): AdminUserSeeder, DatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 78 - "Speaker Registration"
Cohesion: 0.25
Nodes (6): errors, form, loading, route, router, showPassword

### Community 80 - "pestphp/pest-plugin"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 81 - "require"
Cohesion: 0.29
Nodes (7): require, intervention/image, laravel/framework, laravel/sanctum, laravel/tinker, league/flysystem-aws-s3-v3, php

### Community 83 - "CfpMyEvents"
Cohesion: 0.29
Nodes (5): byEvent, LEVEL_LABEL, loading, STATUS_STYLE, talks

### Community 84 - "Controle de Participantes (import CSV)"
Cohesion: 0.47
Nodes (6): Controle de Participantes (import CSV), Spec — Sorteio Digital, Pool de participantes com checked_in = true, EventLotteryController (index, draw, reset), EventLotteryService (draw, reset, obfuscateEmail), Model EventLotteryWinner (unique event_id, participant_id)

### Community 85 - "Spec: Controle de Participantes"
Cohesion: 0.60
Nodes (6): Spec: Controle de Participantes, EventParticipant model, EventParticipantController, EventParticipantService, Sympla CSV import (upsert by event_id+registration_order), UploadParticipantsRequest

### Community 86 - "Spec: CRUD de Usuarios"
Cohesion: 0.60
Nodes (6): Spec: CRUD de Usuarios, Users cannot be deleted, only deactivated; palestrante created via /cfp only, StoreUserRequest, UpdateUserRequest, UserController, UserService

### Community 110 - "autoload"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 111 - "logging"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 112 - "useAuth.js"
Cohesion: 0.60
Nodes (4): fetchUser(), logout(), useAuth(), user

### Community 115 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 116 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 118 - "fetchApprovedTalks()"
Cohesion: 0.67
Nodes (3): fetchApprovedTalks(), openCreateScheduleItem(), openEditScheduleItem()

## Ambiguous Edges - Review These
- `Padrão de CRUDs` → `Proteção multi-tenant por company_id`  [AMBIGUOUS]
  .claude/patterns/crud-patterns.md · relation: rationale_for

## Knowledge Gaps
- **527 isolated node(s):** `$schema`, `name`, `type`, `description`, `keywords` (+522 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 894 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **47 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Padrão de CRUDs` and `Proteção multi-tenant por company_id`?**
  _Edge tagged AMBIGUOUS (relation: rationale_for) - confidence is low._
- **Why does `vue` connect `Theme & Event Site SPA` to `Event Site Admin UI`, `Lottery Draw UI`, `Site JS & Admin Shell`, `Kanban Board UI`, `NPM Dependencies`, `Expenses UI`, `Talk Submission UI`, `Task Modal & Comments UI`, `Admin CFP Review UI`, `Events List UI`, `Social Assets UI`, `Participants UI`, `Expenses Spec`, `Speakers List UI`, `Event Modal Upload`, `Public Layout Classic`, `Public Layout Immersive`, `CFP SPA Shell`, `Speaker Profile UI`, `Public Layout Minimal`, `Admin Router`, `CFP Auth Composable`, `Event Detail Hub`, `Speakers Spec`, `Participant CSV Upload`, `Shared Modals`, `Change Password Modal`, `Talk Review Modal`, `Speaker Dashboard`, `CFP Login`, `CFP Config Modal`, `Reset Password UI`, `Speaker Registration`, `CfpMyEvents`, `useAuth.js`?**
  _High betweenness centrality (0.148) - this node is a cross-community bridge._
- **Why does `Event` connect `Event Module Controllers` to `Social Assets Generation`, `Models & Auth Service`, `Account & Login Controllers`, `Task Comments Backend`, `Controllers & Role Middleware`, `User Management Backend`, `Event & Task Controllers`, `Participant & Schedule Factories`, `CFP & Talk Admin Controllers`, `Lottery & Participants Models`, `Eloquent Models`, `Config & Base Factories`, `Schedule & Site Config Models`, `Social Assets Controller`, `Talk Submission Backend`, `Task Factory`, `Talk Factory`, `Schedule Controller`, `CFP Model & Tests`, `EventSponsorController`, `EventExpenseFactory`, `StoreCfpRequest`, `UpdateCfpRequest`, `DashboardController`, `UploadParticipantsRequest`, `StoreEventSiteRequest`, `StoreTaskRequest`, `Talk`, `EventCfpFactory`, `EventSiteController`, `UpdateTaskRequest`?**
  _High betweenness centrality (0.072) - this node is a cross-community bridge._
- **Why does `axios` connect `CFP Auth Composable` to `Event Site Admin UI`, `Lottery Draw UI`, `Site JS & Admin Shell`, `Kanban Board UI`, `NPM Dependencies`, `Expenses UI`, `Talk Submission UI`, `Task Modal & Comments UI`, `Admin CFP Review UI`, `Events List UI`, `Social Assets UI`, `Participants UI`, `Expenses Spec`, `Speakers List UI`, `Event Modal Upload`, `CFP SPA Shell`, `Speaker Profile UI`, `Event Detail Hub`, `Speakers Spec`, `Participant CSV Upload`, `Shared Modals`, `Change Password Modal`, `Talk Review Modal`, `Speaker Dashboard`, `CFP Login`, `CFP Config Modal`, `Reset Password UI`, `Speaker Registration`, `CfpMyEvents`, `useAuth.js`?**
  _High betweenness centrality (0.072) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _527 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Social Assets Generation` be split into smaller, more focused modules?**
  _Cohesion score 0.08461131676361713 - nodes in this community are weakly interconnected._
- **Should `Database Migrations` be split into smaller, more focused modules?**
  _Cohesion score 0.05136612021857923 - nodes in this community are weakly interconnected._