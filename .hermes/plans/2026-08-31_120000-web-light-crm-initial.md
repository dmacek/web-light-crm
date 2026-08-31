# Web-Light-CRM — Implementation Plan

> **For Hermes:** This is a multi-track (monorepo) project. Use subagent-driven-development to delegate work per task group. TDD where it makes sense; commit after each task group. Never push to `main` — only `dev`.

**Goal:** Build a multi-tenant headless SaaS that lets Czech tradespeople onboard anonymously, get a public website on a subdomain, manage it from a mobile PWA, and receive CRM leads — all running from a single `docker compose up -d`.

**Architecture:** 3 apps in one monorepo, each its own Docker image, fronted by Caddy reverse proxy. Postgres for shared schema (multi-tenant via `business_id` column), Redis for sessions/cache/rate-limit. Caddy does on-demand TLS for custom `.cz` domains.

**Tech Stack:**
- `admin` — Vue 3 + Vite + Tailwind v4 + Pinia + VueUse (mobile-first PWA, 320–430px)
- `backend` — PHP 8.3 + Nette + Doctrine Migrations + JWT (`firebase/php-jwt`) + Symfony HttpClient (ARES + Wedos WAPI)
- `renderer` — PHP 8.3 + Nette (SSR, JSON profile → HTML)
- `proxy` — Caddy 2 (on-demand TLS)
- `db` — Postgres 16 + `redis:7-alpine`

---

## Repo Layout (target)

```
web-light-crm/
├── docker-compose.yml
├── Caddyfile
├── .env.example
├── README.md
├── Docs/                          # per AGENTS.md §Documentation
│   ├── architecture.md
│   ├── domain/{user-business,website-content,lead}.md
│   ├── api/rest.md
│   ├── infrastructure/{docker,ci-cd,database}.md
│   └── decisions/
├── admin/                         # Vue 3 PWA
│   ├── Dockerfile
│   ├── package.json
│   ├── vite.config.ts
│   ├── src/
│   │   ├── main.ts
│   │   ├── App.vue
│   │   ├── router.ts
│   │   ├── stores/                # Pinia
│   │   ├── composables/           # VueUse-heavy
│   │   ├── pages/{Onboarding,Auth,CrmLeads,ContentEditor,Settings}.vue
│   │   ├── components/
│   │   ├── types/                 # shared with backend (mirrored)
│   │   └── assets/main.css        # Tailwind v4 entry
│   ├── tests/                     # Vitest
│   └── public/                    # PWA manifest, icons
├── backend/                       # Nette REST API
│   ├── Dockerfile
│   ├── composer.json
│   ├── phpstan.neon, rector.php, .php-cs-fixer.php, phpat.neon, infection.json
│   ├── bin/console
│   ├── config/{common.neon,api.neon}
│   ├── app/
│   │   ├── Module/
│   │   │   ├── Onboarding/        # Facade + Service + endpoints
│   │   │   ├── Auth/              # OAuth + magic link
│   │   │   ├── Crm/               # Leads state machine
│   │   │   ├── Website/           # WebContent CRUD
│   │   │   ├── Public/            # /public/site/{...}/lead
│   │   │   ├── Integrations/{Ares,Wedos,Domain}/
│   │   │   └── Domain/            # DTOs / VOs / Entities
│   │   ├── DTO/                   # cross-module DTOs
│   │   ├── Security/              # JWT, RefreshToken, Middleware
│   │   ├── Http/                  # CORS, JSON body, errors
│   │   └── Bootstrap.php
│   ├── migrations/                # Doctrine Migrations
│   ├── tests/                     # Pest
│   └── var/{log,cache}
└── renderer/                      # Nette SSR
    ├── Dockerfile
    ├── composer.json
    ├── app/
    │   ├── Presenters/PublicSitePresenter.php
    │   ├── Template/{hero,pricing,gallery,...}.latte
    │   ├── Template/layout.latte
    │   └── Module/Design/         # mood→tokens, block variants
    └── tests/
```

---

## Workstream A — Foundation (do first, blocks everything else)

### Task A1: Monorepo skeleton + root files
**Files:**
- Create: `docker-compose.yml`, `Caddyfile`, `.env.example`, `.gitignore`, `README.md`
**Steps:**
1. Write `docker-compose.yml` per ASSIGNMENT §2.1 (services: `proxy`, `admin-app`, `backend-api`, `public-renderer`, `postgres`, `redis`; volumes `postgres_data`, `redis_data`, `caddy_data`, `caddy_config`).
2. Write `Caddyfile` per §2.2: `on_demand_tls { ask http://backend-api:8000/api/v1/domains/check }`, `app.tvojeaplikace.cz → admin-app:80`, `api.tvojeaplikace.cz → backend-api:8000`, catch-all `:443 { tls on_demand } → public-renderer:3000`.
3. `.env.example` with `POSTGRES_USER/PASSWORD/DB`, `JWT_SECRET`, `WEDOS_WAPI_KEY`, `ARES_TIMEOUT`.
4. `.gitignore` per memory rule: `.env*`, `vendor/`, `node_modules/`, `*.log`, `var/`, `dist/`, `build/`, `*.sqlite`.
5. Root `README.md` per AGENTS.md template.
6. Verify: `docker compose config -q` (no boot, just config lint).
7. Commit: `chore(repo): add docker-compose, Caddyfile, env template, root docs`.

### Task A2: Postgres schema — first migration
**Files:**
- Create: `backend/composer.json`, `backend/migrations/Version20260831090000_init_schema.php`
**Steps:**
1. Scaffold `backend/composer.json` with: `nette/application`, `nette/http`, `nette/di`, `doctrine/orm`, `doctrine/migrations`, `symfony/http-client`, `firebase/php-jwt`, `ramsey/uuid`; dev: `pestphp/pest`, `phpstan/phpstan`, `rector/rector`, `friendsofphp/php-cs-fixer`, `phpat/phpat`, `infection/infection`.
2. Add composer scripts per AGENTS.md (`cs:fix`, `phpstan`, `rector`, `test`, `qa`).
3. Migration creates:
   - `businesses` (uuid PK, email, phone, ico, company_name, street, city, zip, archetype enum, main_trade_name, subdomain unique, custom_domain nullable, custom_domain_status enum, created_at)
   - `auth_providers` (id PK, business_id FK, provider enum, provider_user_id, linked_at, unique(provider, provider_user_id))
   - `subscriptions` (id PK, business_id FK 1:1, status enum, plan enum nullable, trial_ends_at, current_period_ends_at)
   - `web_contents` (business_id PK/FK, version int, design jsonb, content jsonb, updated_at)
   - `leads` (lead_id PK uuid, business_id FK, sender_name, sender_phone, sender_email, message, status enum, created_at, reminder_at nullable, index on (business_id, status))
   - `onboarding_drafts` (session_draft_id PK, data jsonb, expires_at, index on expires_at)
   - `media` (id PK uuid, business_id FK, image_url, thumbnail_url, caption, created_at)
   - All FKs → `ON DELETE CASCADE`; all tenant tables have B-tree index on `business_id`.
4. Add `up()` + `down()`.
5. Commit: `feat(backend): initial migration with multi-tenant schema`.

### Task A3: Backend bootstrap + skeleton Nette app
**Files:**
- Create: `backend/Dockerfile`, `backend/app/Bootstrap.php`, `backend/config/common.neon`, `backend/bin/console`, `backend/app/Http/CorsMiddleware.php`, `backend/app/Http/JsonBodyMiddleware.php`, `backend/app/Http/ErrorHandler.php`
**Steps:**
1. Multi-stage Dockerfile: `composer install` stage, runtime on `php:8.3-fpm-alpine` with php-ext `pdo_pgsql`, `opcache`, production-only deps.
2. Nette app exposing REST — presentery vrací JSON (single `ApiPresenter` s routovacím prefixem `/api/v1/...`, dispatch podle cesty na `*Controller` třídy v modulech).
3. CORS middleware (allow `app.tvojeaplikace.cz`, credentials true).
4. JSON body parser (`Nette\Http\Request::getRawBody()` → json_decode).
5. Error handler → JSON `{ error: { code, message } }` se správnými status kódy.
6. Smoke test: `GET /api/v1/health` → `{ "status": "ok" }`.
7. Commit: `feat(backend): bootstrap Nette app with JSON middleware + health check`.

---

## Workstream B — Domain layer + auth (blocks onboarding + CRM)

### Task B1: Domain enums + DTOs + VOs
**Files:**
- Create: `backend/app/Domain/{Archetype,Mood,BlockVariant,LeadStatus,DomainStatus,SubscriptionStatus,Plan,AuthProvider}.php` (all backed enums)
- Create: `backend/app/DTO/{BusinessDTO,SubscriptionDTO,WebContentDTO,LeadDTO,ContactDTO,AddressDTO,Money.php,BusinessId.php}`
**Steps:**
1. Each enum: backed string, values exactly per ASSIGNMENT §3.
2. `BusinessId` final VO wrapping Ramsey UUID; `Money` VO (cents int + currency).
3. `*DTO` readonly classes per AGENTS.md example.
4. Each DTO has `toArray()` matching TS interface in `admin/src/types/` (mirror manually for now).
5. Pest test for `Money::add` currency mismatch throws.
6. Commit: `feat(backend): domain enums, DTOs, value objects`.

### Task B2: Repositories
**Files:**
- Create: `backend/app/Module/Onboarding/OnboardingDraftRepository.php`, `backend/app/Module/Auth/AuthProviderRepository.php`, `backend/app/Module/Website/WebContentRepository.php`, `backend/app/Module/Crm/LeadRepository.php`, `backend/app/Module/Domain/BusinessRepository.php`
**Steps:**
1. All repos take `business_id` filter as method param — never implicit.
2. Methods: `find`, `findAll`, `insert`, `update`, `delete` (where applicable).
3. Use Doctrine ORM with typed entity classes (`@Entity` per table).
4. Test: insert → findById, update → re-read, multi-tenant isolation test (repo A's id never returns repo B's data).
5. Commit: `feat(backend): repositories with multi-tenant guards`.

### Task B3: JWT + refresh token + auth middleware
**Files:**
- Create: `backend/app/Security/JwtService.php`, `backend/app/Security/RefreshTokenService.php`, `backend/app/Security/AuthMiddleware.php`, `backend/app/Security/SessionStore.php` (Redis-backed)
**Steps:**
1. `JwtService`: HS256, 15-min access, claims `sub=business_id`, `iat`, `exp`, `jti`.
2. `RefreshTokenService`: 90-day token, stored in Redis with TTL, rotation on use (issue new + invalidate old), stored as `httpOnly`, `secure`, `SameSite=Lax` cookie in production.
3. `AuthMiddleware`: extracts `Authorization: Bearer …`, validates, sets `BusinessId` on request.
4. `SessionStore`: Redis namespaced `session:{sid}` with TTL.
5. Tests: token verify valid/expired/tampered; refresh rotation invalidates old; missing auth → 401.
6. Commit: `feat(backend): JWT, refresh token rotation, auth middleware`.

### Task B4: OAuth providers + magic link
**Files:**
- Create: `backend/app/Module/Auth/Provider/{SeznamProvider,GoogleProvider,AppleProvider,MagicLinkProvider}.php`, `backend/app/Module/Auth/AuthFacade.php`
- Create: `backend/app/Module/Auth/Controller/AuthController.php`
- Create: `backend/app/Module/Auth/Email/Mailer.php` (stub sender — log to file in dev)
**Steps:**
1. Endpoints exactly per ASSIGNMENT §5.
2. Seznam: OAuth 2.0 code exchange on `login.szn.cz`; Google: verify ID token via Google JWKS (`https://www.googleapis.com/oauth2/v3/certs`); Apple: verify JWT against Apple JWKS, allow `privaterelay.appleid.com`.
3. Magic link: 6-digit PIN, store hashed in Redis with 10-min TTL, `POST /verify` consumes it.
4. Response on success: `{ access_token, business_id, subscription: {...} }` + `Set-Cookie: refresh_token=...`.
5. Pest tests for each provider happy path + error (mock HTTP).
6. Commit: `feat(auth): OAuth 2.0 (Seznam/Google/Apple) + magic link fallback`.

---

## Workstream C — Onboarding + ARES + draft claim

### Task C1: ARES integration
**Files:**
- Create: `backend/app/Module/Integrations/Ares/AresClient.php`, `backend/app/Module/Integrations/Ares/Controller/AresController.php`
**Steps:**
1. `GET /api/v1/integrations/ares/{ico}` — validate 8 digits, call `https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/{ico}` via Symfony HttpClient.
2. Map response → `AddressDTO` + `company_name`.
3. Cache result in Redis 24h (per ICO).
4. Acceptance §2: response < 1.5s (cache hit ~ms; cold call must complete in 1.5s — pick timeout 1.2s).
5. Tests: valid 8-digit + known fixture (recorded VCR-style fixture file), invalid format → 422, ARES down → 503.
6. Commit: `feat(integrations): ARES client with Redis cache`.

### Task C2: Onboarding draft flow
**Files:**
- Create: `backend/app/Module/Onboarding/OnboardingFacade.php`, `backend/app/Module/Onboarding/Controller/OnboardingController.php`
- Create: `backend/app/Module/Onboarding/DraftStore.php` (Redis)
**Steps:**
1. Anonym endpoint `POST /api/v1/onboarding/draft` (steps 1–4) — stores draft keyed by `session_draft_id` cookie (anonymous, 7-day TTL).
2. Step 1: `POST /draft/ico` validates and stores (calls AresClient).
3. Step 2: archetype enum + trade autosuggest (static seed of 4 archetypes with default services in DB, simple LIKE on name — YAGNI, no ML).
4. Step 3: validate phone `+420` regex, email RFC-lite.
5. Step 4: photo upload via `POST /draft/photo` (multipart) → store under `var/uploads/draft/{session_draft_id}/`, return signed URL.
6. `POST /onboarding/claim-draft` (auth required): merges draft into new `businesses` row + `web_contents` row + media rows, generates unique `subdomain` (`slug(company_name) + 4 random hex chars` → retry on conflict), starts trial (`trial_ends_at = now + 14d`), deletes draft.
7. Tests: full claim flow E2E (Pest with test DB), conflict retry on subdomain.
8. Commit: `feat(onboarding): 5-step lazy registration with draft claim`.

### Task C3: Admin app — onboarding PWA screens
**Files:**
- Create: `admin/package.json`, `admin/vite.config.ts`, `admin/src/main.ts`, `admin/src/App.vue`, `admin/src/router.ts`, `admin/src/pages/Onboarding/{StepIco,StepArchetype,StepContact,StepMedia,StepPreview}.vue`
- Create: `admin/src/stores/onboarding.ts` (Pinia)
- Create: `admin/src/composables/useApi.ts` (VueUse `useFetch`)
**Steps:**
1. Vue 3 + Vite 5 + Tailwind v4 (CSS-first via `@theme`), Pinia, VueUse, `vite-plugin-pwa`.
2. Mobile-first shell: max-width 430px centered on desktop, dark mode via `@custom-variant`.
3. Each step uses `useApi` + Pinia store; debounced IČO field with VueUse `useDebounceFn(400)`.
4. Step 5 (Preview) renders a scaled iframe of `https://{subdomain}.tvojeaplikace.cz` placeholder.
5. "Spustit web" button opens AuthModal (ProviderButtons + MagicLink).
6. Vitest: store updates on each step, claim flow calls API.
7. Commit: `feat(admin): mobile-first onboarding PWA, steps 1–5`.

---

## Workstream D — Public renderer (SSR web)

### Task D1: Renderer skeleton + Caddy on-demand endpoint
**Files:**
- Create: `renderer/Dockerfile`, `renderer/composer.json`, `renderer/app/Presenters/PublicSitePresenter.php`
- Create: `backend/app/Module/Integrations/Domain/Controller/DomainCheckController.php`
**Steps:**
1. Renderer is Nette app with single presenter, listens on :3000.
2. Resolves host (`Host` header) → look up `businesses.custom_domain` or `businesses.subdomain + '.tvojeaplikace.cz'`.
3. Loads `web_contents` row → 404 if missing.
4. `GET /api/v1/domains/check?domain=...` on backend: checks Postgres for active `custom_domain` matching, returns 200/404 (Caddy `ask` endpoint).
5. Smoke test: render localhost with stub data.
6. Commit: `feat(renderer): SSR host resolver; feat(backend): Caddy on-demand check endpoint`.

### Task D2: Latte templates + Chameleon Engine
**Files:**
- Create: `renderer/app/Template/layout.latte`, `renderer/app/Template/{hero,pricing,gallery,services,contact,vacationBanner}.latte`
- Create: `renderer/app/Module/Design/{MoodResolver,PaletteResolver,BlockVariantResolver}.php`
**Steps:**
1. Layout: mobile-first, semantic HTML, OG tags.
2. Mood → CSS custom properties in `<style>` block: `--color-primary/secondary/background/font-stack`. Resolve palette from `design.color_palette` (10 presets as JSON map).
3. Block variants per ASSIGNMENT §3.2 (hero: 3 variants, pricing: 3, gallery: 3) — each as a separate Latte include picked by `BlockVariantResolver`.
4. Vacation banner: sticky top if `vacation_banner.active`.
5. Image `<picture>` with WebP + `srcset` for responsive.
6. Acceptance §6: visual breakdown test — snapshot all 4×10×3×3×2 = 720 variants via Playwright (render test). Pick subset: 4 moods × 3 hero × 3 pricing × 2 banner = 72 visual snapshots in CI.
7. Commit: `feat(renderer): mood/palette/variant resolver and templates`.

### Task D3: Public lead form
**Files:**
- Create: `backend/app/Module/Public/Controller/PublicLeadController.php`, `backend/app/Module/Public/LeadIntakeService.php`
**Steps:**
1. `POST /api/v1/public/site/{subdomain_or_domain}/lead` — no auth, rate-limited (Redis token bucket, 5/min per IP).
2. Validates name (required, ≤120), phone (required, E.164-ish), email (RFC-lite optional), message (≤2000).
3. Inserts `leads` row with status `NEW`.
4. Renderer form posts here via fetch (or plain POST + redirect to thank-you).
5. Tests: 5 then 6th request → 429, missing subdomain → 404, happy path inserts row.
6. Commit: `feat(public): lead form with rate limiting`.

---

## Workstream E — CRM + content editor

### Task E1: CRM endpoints
**Files:**
- Create: `backend/app/Module/Crm/Controller/LeadController.php`, `backend/app/Module/Crm/LeadFacade.php`
**Steps:**
1. `GET /api/v1/crm/leads?status=...` — auth required, filters by `business_id` from JWT.
2. `PATCH /api/v1/crm/leads/{lead_id}/status` — state machine `NEW → CALL_BACK → RESOLVED` (and back from RESOLVED to CALL_BACK). Reject illegal transitions with 409.
3. `PATCH /api/v1/crm/leads/{lead_id}/reminder` — sets `reminder_at`.
4. Tests: cross-tenant access denied (404, not 403, to avoid existence leak), state machine.
5. Commit: `feat(crm): lead list, status transitions, reminders`.

### Task E2: Admin — CRM screen
**Files:**
- Create: `admin/src/pages/CrmLeads.vue`, `admin/src/components/{LeadCard,StatusPill,ActionSheet}.vue`
- Create: `admin/src/stores/leads.ts`
**Steps:**
1. Use `useInfiniteList` (custom on top of VueUse `useInfiniteScroll`).
2. StatusPill colors: `NEW` red, `CALL_BACK` orange, `RESOLVED` green (Tailwind tokens).
3. One-tap actions: `<a :href="tel:...">` `<a :href="sms:...?body=...">`, plus reminder button → `showDatePicker` (VueUse) → API call.
4. Pull-to-refresh with VueUse `useScroll`.
5. Vitest: filters by status, optimistic status update.
6. Commit: `feat(admin): CRM leads screen with native call/SMS actions`.

### Task E3: Content editor
**Files:**
- Create: `backend/app/Module/Website/Controller/WebsiteController.php`, `backend/app/Module/Website/WebsiteFacade.php`
- Create: `admin/src/pages/ContentEditor/{Services,Pricing,Gallery,Banner,Design}.vue`
- Create: `admin/src/composables/useImagePipeline.ts` (client-side compress to WebP via Canvas)
**Steps:**
1. `GET /api/v1/website/config` → `WebContentDTO`; `PUT` validates & bumps `version`.
2. Max 30 services, max 20 gallery images, max 1 vacation banner.
3. Reorder: PATCH `/website/config/services/reorder` (array of ids in new order).
4. Image upload pipeline: client compresses (Canvas, target 1600px longest edge, WebP q=0.82) → uploads as multipart to `POST /website/media` → returns `image_url` + `thumbnail_url` (server-side 4:3 + 1:1 crops generated on the fly via `intervention/image`).
5. Vitest: ordering persists, max limits enforced, image pipeline keeps aspect.
6. Commit: `feat(website): content editor endpoints and admin screens`.

---

## Workstream F — Billing + domain (last)

### Task F1: Subscription state machine
**Files:**
- Create: `backend/app/Module/Billing/SubscriptionService.php`, `backend/app/Module/Billing/Controller/SubscriptionController.php`
- Create: `backend/app/Module/Billing/PlanRegistry.php` (PLAN_PRICES constant map)
**Steps:**
1. Trial starts at onboarding claim (already in C2). After 14d, cron-equivalent (`bin/console billing:expire-trials` daily) marks `EXPIRED`.
2. Mock payment: `POST /api/v1/billing/subscribe { plan }` flips to `ACTIVE` and sets `current_period_ends_at`. (Real Stripe/Comgate later — YAGNI for now.)
3. Tests: state transitions, expiry job.
4. Commit: `feat(billing): subscription plans and trial expiry`.

### Task F2: Wedos WAPI integration
**Files:**
- Create: `backend/app/Module/Integrations/Wedos/{WedosClient,DomainRegistrar}.php`
- Create: `backend/app/Module/Billing/Controller/DomainController.php`
**Steps:**
1. Wedos WAPI per https://kb.wedos.global/wapi/ — auth header `API-Key`.
2. On annual plan purchase: check domain availability → register → return `PENDING` until whois confirms (poll 5×10s, then mark `ERROR` or `ACTIVE`).
3. Wire `custom_domain_status` state machine: `NONE → PENDING → ACTIVE | ERROR`.
4. Domain check is also used by renderer (D1) to map hostname → business_id.
5. Tests: with mocked WAPI — happy path, taken domain (suggest alt), API down.
6. Commit: `feat(integrations): Wedos WAPI domain registration`.

---

## Workstream G — CI, docs, polish

### Task G1: GitHub Actions CI
**Files:**
- Create: `.github/workflows/ci.yml`
**Steps:**
1. Jobs: `php-qa` (composer qa), `frontend-qa` (npm ci && npm run qa), `docker-build` (build all 3 images, no push).
2. Use `shivammathur/setup-php@v2` (8.3) and `actions/setup-node@v4` (20).
3. Cache composer + npm.
4. Commit: `ci: add PHP + FE + Docker build pipeline`.

### Task G2: Docs
**Files:**
- Create: `Docs/architecture.md`, `Docs/domain/{user-business,website-content,lead}.md`, `Docs/api/rest.md`, `Docs/infrastructure/{docker,ci-cd,database}.md`, `Docs/decisions/001-monorepo-three-apps.md`
**Steps:**
1. Each file: Context / Structure / Usage / Conventions / Related per AGENTS.md template.
2. Mermaid diagrams: container graph, ER diagram, request flow (onboarding → claim → public site).
3. Commit: `docs: architecture, domain, API, infrastructure, ADRs`.

### Task G3: One-command acceptance
**Steps:**
1. `docker compose up -d` from a clean clone.
2. `curl http://localhost/health` (proxy → renderer placeholder) returns 200.
3. `docker compose exec backend bin/console doctrine:migrations:migrate -n` succeeds.
4. Seed script (`bin/console app:seed-demo`) creates 1 demo business with content.
5. Visit `http://<sub>.localhost` (via `/etc/hosts` + Caddy on-demand pointing at backend domain check) renders site.
6. Submit lead via form → appears in CRM.
7. Commit: `chore: seed script and acceptance smoke test`.

---

## Conventions (from AGENTS.md, restated for this plan)

- PHP: `declare(strict_types=1)`, PHPStan lvl 9, backed enums, readonly DTOs, `#[SensitiveParameter]` for secrets, Pest for tests.
- Vue: `<script setup lang="ts">`, VueUse-first, Tailwind v4 CSS-first, semantic tokens (no `bg-blue-500`).
- Git: conventional commits, dev branch only, never push to main.
- Security: zero secrets in repo; `.env` gitignored, GitHub Secrets for CI.

---

## Risks & Tradeoffs

- **Wedos WAPI is a paid external dependency** — stub it behind a `DomainRegistrar` interface so tests don't hit prod; gate live calls behind `WEDOS_WAPI_KEY` env.
- **On-demand TLS in dev** — Caddy needs internet for ACME; for local testing, run with `tls internal` or stub `on_demand_tls { ask }` to a dev backend that always returns 200.
- **CORS in mobile PWA** — same-site via subdomain (`app.tvojeaplikace.cz` vs `api.tvojeaplikace.cz`) is fine; in dev with `localhost:5173 → localhost:8000` we need explicit CORS.
- **Multi-tenant isolation** — every repo method MUST take `business_id` and assert. PHPat architecture test: no presenter/repository method without that param.
- **Scope creep** — Wedos + payment + 720 visual snapshots: each is a subagent-sized chunk; run them in parallel only after D2 ships.

## Open Questions

1. **Payment provider** — ASSIGNMENT says 250/2500 Kč but no provider. Plan mocks it (F1). Confirm OK for MVP, or wire Comgate/Gopay?
2. **Image storage** — local volume vs S3-compatible (MinIO in compose)? Plan uses local volume for MVP.
3. **Cron** — single `backend` container with `bin/console` cron entry, or a separate `worker` service?
4. **Renderer perf** — pure SSR (rebuild HTML per request) or SSR + Redis HTML cache (60s TTL)? Plan uses Redis HTML cache in D2.
