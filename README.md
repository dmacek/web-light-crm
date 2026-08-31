# Web-Light-CRM

> Mobilní Generátor Webů & Light CRM pro Živnostníky v ČR (Multi-Tenant Headless SaaS)

---

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Node 20+, npm (pro lokální vývoj frontendu)
- PHP 8.3+, Composer 2.x (pro lokální vývoj backendu)
- PostgreSQL 16+ & Redis 7+ (nebo přes Docker)

### Spuštění celého stacku přes Docker
```bash
# 1. Klonování a nastavení prostředí
cp .env.example .env

# 2. Spuštění kontejnerů
docker compose up -d

# 3. Spuštění databázových migrací v backendu
docker compose exec backend-api php bin/console doctrine:migrations:migrate --no-interaction
```

---

## 🏗️ Architektura systému

Systém se skládá ze tří nezávislých aplikačních vrstev obsluhovaných reverzní proxy Caddy:

1. **`admin` (`admin-app`):** Mobile-first Vue 3 + Vite + Tailwind v4 PWA pro anonymní onboarding, správu CRM poptávek a úpravu obsahu webu.
2. **`backend` (`backend-api`):** Nette REST API (PHP 8.3) zajišťující OAuth 2.0 / JWT autentizaci, integraci ARES, správu leadů, rotaci refresh tokenů a nákup domén přes Wedos WAPI.
3. **`renderer` (`public-renderer`):** Nette SSR engine s Chameleon design systémem a Redis HTML cache pro generování responzivních webů živnostníků.
4. **`proxy`:** Caddy 2 s On-Demand TLS certifikáty pro vlastní `.cz` domény a subdomény.

---

## 🧪 Testování a QA

```bash
# Backend testy a statická analýza
cd backend
composer test
composer qa

# Frontend testy a kontrola typů
cd ../admin
npm run test
npm run typecheck
npm run lint
```

---

## 📚 Dokumentace

Detailní dokumentace architektury, doménových modelů, API endpointů a rozhodnutí je k dispozici ve složce [Docs/](./Docs/).
