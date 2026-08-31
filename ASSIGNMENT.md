# FUNKČNÍ A TECHNICKÁ SPECIFIKACE PRO AGENTNÍ SYSTÉM (PRD FINAL)

**Projekt:** Mobilní Generátor Webů & Light CRM pro Živnostníky (SaaS)  
**Architektura:** Multi-Tenant Headless SaaS (Docker Stack)  
**Cílová skupina:** Řemeslníci, kadeřnice a lokální služby v ČR  

---

## 1. Globální Architektura & Koncept

Aplikace je navržena jako oddělený SaaS systém složený ze tří samostatných aplikačních vrstev a podpůrné infrastruktury:

1. **Mobilní Správcovská Aplikace (`admin-app`):** Mobile-First SPA/PWA rozhraní určené pro telefony (320px–430px). Slouží k anonymnímu onboardingu, správě poptávek (CRM) a úpravě obsahu webu přes formulářové moduly bez vizuálního drag-and-drop editoru.
2. **Backend REST API (`backend-api`):** Bezstavový backend vyřizující autentizaci (OAuth 2.0 / JWT), integraci ARES, CRUD operace obsahu, logiku CRM stavového stroje a rozhraní pro automatický nákup domén.
3. **Public Website Engine (`public-renderer`):** Vysoce výkonný SSR/SSG renderer generující výsledný HTML web živnostníka na základě strukturovaného JSON profilu uloženého v databázi/cachi.

---

## 2. Docker Compose Architektura & Multi-Tenancy

Kompletní prostředí musí být spustitelné příkazem `docker compose up -d`.

### 2.1 Skladba Kontejnerů (`docker-compose.yml`)

| Služba | Obraz / Build | Role & Odpovědnost | Škálování |
|---|---|---|---|
| **`proxy`** | `caddy:2-alpine` | Reverse Proxy (80/443). Vyřizuje automatické **On-Demand TLS** pro vlastní `.cz` domény a wildcard SSL pro subdomény. | 1x Master |
| **`admin-app`** | `./admin` | Statický build mobilní PWA/SPA správcovské aplikace. | **1..N** |
| **`backend-api`** | `./backend` | Bezstavové REST API. | **1..N** |
| **`public-renderer`** | `./renderer` | SSR Engine veřejných webů. | **1..N** |
| **`postgres`** | `postgres:16-alpine` | Relační databáze s persistentním volume (`postgres_data`). | 1x |
| **`redis`** | `redis:7-alpine` | Cache vygenerovaných HTML webů, session storage a rate-limiting. | 1x |

```yaml
version: '3.8'

services:
  proxy:
    image: caddy:2-alpine
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./Caddyfile:/etc/caddy/Caddyfile
      - caddy_data:/data
      - caddy_config:/config
    depends_on:
      - admin-app
      - backend-api
      - public-renderer

  admin-app:
    build:
      context: ./admin
      dockerfile: Dockerfile
    restart: always

  backend-api:
    build:
      context: ./backend
      dockerfile: Dockerfile
    restart: always
    environment:
      DATABASE_URL: "postgres://user:password@postgres:5432/app_db?sslmode=disable"
      REDIS_URL: "redis://redis:6379"
      JWT_SECRET: "${JWT_SECRET}"
    depends_on:
      - postgres
      - redis

  public-renderer:
    build:
      context: ./renderer
      dockerfile: Dockerfile
    restart: always
    environment:
      DATABASE_URL: "postgres://user:password@postgres:5432/app_db?sslmode=disable"
      REDIS_URL: "redis://redis:6379"
    depends_on:
      - postgres
      - redis

  postgres:
    image: postgres:16-alpine
    restart: always
    environment:
      POSTGRES_USER: user
      POSTGRES_PASSWORD: password
      POSTGRES_DB: app_db
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    restart: always
    volumes:
      - redis_data:/data

volumes:
  postgres_data:
  redis_data:
  caddy_data:
  caddy_config:
```

### 2.2 Routing & On-Demand TLS (`Caddyfile`)
```caddy
{
    on_demand_tls {
        ask http://backend-api:8000/api/v1/domains/check
    }
}

# Mobilní Správcovská Aplikace (Admin SPA/PWA)
app.tvojeaplikace.cz {
    reverse_proxy admin-app:80
}

# REST API pro mobilní aplikaci
api.tvojeaplikace.cz {
    reverse_proxy backend-api:8000
}

# Veřejné weby živnostníků (Vlastní .cz domény + Subdomény)
:443 {
    tls {
        on_demand
    }
    reverse_proxy public-renderer:3000
}
```

### 2.3 Databázový přístup (Shared Schema)
* **Všechny tabulky** (`services`, `leads`, `gallery_images`) obsahují sloupec `business_id` s B-tree indexem.
* Backend API i Public Renderer striktně vynucují databázové filtrování `WHERE business_id = :tenant_id`.

---

## 3. Datové Modely (JSON Schemas)

### 3.1 Entity: User & BusinessProfile
```json
{
  "business_id": "uuid",
  "email": "string",
  "phone": "string",
  "created_at": "timestamp",
  "auth_providers": [
    {
      "provider": "SEZNAM | GOOGLE | APPLE | EMAIL_MAGIC_LINK",
      "provider_user_id": "string",
      "linked_at": "timestamp"
    }
  ],
  "subscription": {
    "status": "TRIAL | ACTIVE | EXPIRED | CANCELLED",
    "plan": "MONTHLY | ANNUAL",
    "trial_ends_at": "timestamp",
    "current_period_ends_at": "timestamp"
  },
  "business_profile": {
    "ico": "string",
    "company_name": "string",
    "street": "string",
    "city": "string",
    "zip": "string",
    "archetype": "PROVOZOVNA | VYJEZDOVE_REMESLO | ZAKAZKOVA_VYROBA | OSTATNI",
    "main_trade_name": "string",
    "subdomain": "string",
    "custom_domain": "string | null",
    "custom_domain_status": "NONE | PENDING | ACTIVE | ERROR"
  }
}
```

### 3.2 Entity: WebContent & DesignSystem
```json
{
  "business_id": "uuid",
  "version": "int",
  "design": {
    "mood": "MODERN | TRADITIONAL | BOLD | ELEGANT",
    "color_palette": {
      "primary": "#HEX",
      "secondary": "#HEX",
      "background": "#HEX"
    },
    "block_variants": {
      "hero": "FULL_IMAGE_OVERLAY | SPLIT_TEXT_IMAGE | COMPACT_CARD",
      "pricing": "LIST_DOTS | CARDS_GRID | COMPACT_TABLE",
      "gallery": "GRID_2X2 | CAROUSEL_SLIDER | FEATURED_HERO"
    }
  },
  "content": {
    "vacation_banner": { "active": "boolean", "text": "string" },
    "services": [
      { "id": "uuid", "title": "string", "description": "string", "price_text": "string", "order": "int" }
    ],
    "gallery": [
      { "id": "uuid", "image_url": "string", "thumbnail_url": "string", "caption": "string" }
    ],
    "opening_hours": "string",
    "contact": { "phone": "string", "email": "string", "address_visible": "boolean" }
  }
}
```

### 3.3 Entity: Lead (CRM Poptávka)
```json
{
  "lead_id": "uuid",
  "business_id": "uuid",
  "sender_name": "string",
  "sender_phone": "string",
  "sender_email": "string",
  "message": "string",
  "status": "NEW | CALL_BACK | RESOLVED",
  "created_at": "timestamp",
  "reminder_at": "timestamp | null"
}
```

---

## 4. Funkční Moduly & Business Logika

### Modul 1: Onboarding s Lazy Registrací
* **Principy:** Kroky 1 až 4 probíhají anonymně s ukládáním do `session_draft_id`. Účet se vytváří až v Kroku 5.
* **Krok 1 (IČO Validation):** Zadané IČO (8 číslic) > volání ARES API (`GET /api/v1/integrations/ares/{ico}`) > dotažení názvu a adresy.
* **Krok 2 (Archetyp & Obor):** Výběr ze 4 dlaždic archetypu + našeptávač oboru. Automatické předvyplnění vzorových služeb podle shody v DB.
* **Krok 3 (Kontakty):** Telefon a e-mail. Validace českého čísla (`+420...`).
* **Krok 4 (Logo, Fotky provozovny):** Nahrání fotky z mobilu. Komprese na klientovi, uložení do dočasného ústřižku. Možnost přeskočit tento krok.
* **Krok 5 (Náhled & Registrační Modal):** Zobrazení živého náhledu webu + tlačítko *"Spustit web na 14 dní zdarma"*. Klepnutí otevře registrační modal. Po autentizaci systém sloučí draft data pod nový `business_id`, vygeneruje poddoménu a spustí 14denní trial.

### Modul 2: Autentizační Modul (OAuth 2.0 & Session Management)
* **Seznam.cz OAuth 2.0:** Integrace přes `login.szn.cz` (získání e-mailu a ID).
* **Google Sign-In:** Ověření Google ID Tokenu na backendu.
* **Apple ID Sign-In:** Podpora Sign in with Apple včetně anonymních e-mailů (`@privaterelay.appleid.com`).
* **Fallback Magic Link:** Zaslání jednorázového 6-místného PIN kódu na e-mail.
* **Session Management:** Vystavení **JWT Access Tokenu** (platnost 15 minut) a **Refresh Tokenu** (httpOnly cookie / secure storage, platnost 90 dní pro trvalé přihlášení na mobilu).

### Modul 3: Light CRM (Domovská Obrazovka PWA)
* **Statistiky:** Unikátní návštěvníci (Dnes / Tento týden), počet poptávek.
* **Seznam poptávek:** Chronologicky seřazeno s indikátory stavu: `NEW` (Červená), `CALL_BACK` (Oranžová), `RESOLVED` (Zelená).
* **Nativní akce na 1 klepnutí:**
  * **Volat:** Nativní vyvolání `tel:{sender_phone}`.
  * **SMS:** Nativní SMS aplikaci `sms:{sender_phone}?body=Dobrý den...`.
  * **Připomenout:** Odložená notifikace za zadaný čas.

### Modul 4: Správa Obsahu & Chameleon Engine
* **Formulářový editor:** Bez visual canvasu.
  1. *Služby a Ceník:* CRUD operace (max 30 položek), změna pořadí.
  2. *Fotogalerie:* Multi-file upload, automatický ořez (4:3 / 1:1), WebP konverze, max 20 fotek.
  3. *Stavový Banner:* Toggle vypínač + text (*"Mám dovolenou do DD.MM."*).
  4. *Design & Styl:* Volba ze 4 Moodů (`MODERN`, `TRADITIONAL`, `BOLD`, `ELEGANT`), 10 barevných palet a přepínačů layoutů jednotlivých bloků.

### Modul 5: Billing & Správa Domén
* **Trial Period:** 14 dní zdarma na poddoméně.
* **Měsíční paušál:** 250 Kč / měsíc (hosting, SSL, CRM, správa z mobilu).
* **Roční paušál:** 2 500 Kč / rok (sleva 500 Kč + **.cz doména v ceně zdarma**).
* **Asistovaná migrace:** 2 000 Kč jednorázově za ruční přeregistraci domény a DNS.
* **Registrátor API:** Při volbě ročního tarifu backend nakoupí `.cz` domény na 1 kliknutí přes API registrátora (WAPI služby Wedos - https://kb.wedos.global/wapi/).

---

## 5. API Endpoint Contract (REST API Specifikace)

```http
/* Integrace ARES */
GET /api/v1/integrations/ares/{ico}

/* OAuth 2.0 & Autentizace */
POST /api/v1/auth/provider/seznam
POST /api/v1/auth/provider/google
POST /api/v1/auth/provider/apple
POST /api/v1/auth/magic-link/request
POST /api/v1/auth/magic-link/verify
POST /api/v1/auth/refresh-token

/* Onboarding Finish & Draft Claiming */
POST /api/v1/onboarding/claim-draft

/* CRM - Poptávky */
GET /api/v1/crm/leads?status=NEW|CALL_BACK|RESOLVED
PATCH /api/v1/crm/leads/{lead_id}/status

/* Správa Obsahu Webu */
GET /api/v1/website/config
PUT /api/v1/website/config

/* Veřejný Poptávkový Formulář (Voláno z veřejného webu) */
POST /api/v1/public/site/{subdomain_or_domain}/lead

/* Internal Caddy On-Demand TLS Check */
GET /api/v1/domains/check?domain=maly-elektro.cz
```

---

## 6. Akceptační Kritéria (Acceptance Criteria)

1. **One-Command Launch:** Celý stack (`proxy`, `admin-app`, `backend-api`, `public-renderer`, `postgres`, `redis`) vyvstane bez chyb po zadání `docker compose up -d`.
2. **ARES Response Time:** Vyplnění IČO automaticky doplňuje adresu a název do 1.5 sekundy.
3. **Lazy Registration Consistency:** Data vytvořená v anonymním průvodci (IČO, fotky, vybrané služby) se po OAuth přihlášení (Seznam, Google, Apple) bez ztráty přenesou pod nově vytvořeného uživatele.
4. **Zero-Downtime SSL:** Nově připojená `.cz` doména získá SSL certifikát přes Caddy On-Demand TLS do 3 sekund od prvního požadavku bez jakéhokoliv restartu kontejnerů nebo zásahu do konfigurace.
5. **Robust Mobile Session:** Přihlášení v mobilní PWA aplikaci zůstává aktivní minimálně 90 dní díky automatické rotaci refresh tokenů.
6. **Zero Visual Breakdown:** Vygenerovaný web z `public-rendereru` vypadá bezchybně a responzivně při jakékoliv volbě barevné palety, fontu či rozvržení bloků.
