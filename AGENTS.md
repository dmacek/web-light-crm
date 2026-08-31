# AGENTS.md — Development Rules & Conventions

> **Purpose:** Single source of truth for AI agents and team members working on this codebase.
> **Stack:** PHP 8.3+ (Nette), Vue 3 + Vite + Tailwind v4, Pinia, Pest/Vitest
> **Skills loaded:** php-modernization, php-pro, nette-*, vue-*, vite, tailwind, vueuse-functions, github-actions-templates

---

## 🎯 Core Principles

1. **Type Safety First** — `declare(strict_types=1)` everywhere; PHPStan level 9+ (10 for new code)
2. **Explicit over Implicit** — DTOs/Value Objects over arrays; backed enums over constants
3. **Test-Driven** — Pest (PHP) + Vitest (Vue); ≥80% coverage; mutation testing via Infection
4. **Automate Everything** — Rector (dry-run first), PHP-CS-Fixer (@PER-CS), GitHub Actions CI
5. **Security by Default** — #[SensitiveParameter], SQL injection prevention, XSS protection

---

## 🐘 PHP / Nette Rules

### Strict Types & Modern PHP (php-modernization, php-pro)

```php
<?php
declare(strict_types=1);

namespace App\Module\Product;

use App\DTO\ProductId;
use App\DTO\Money;

// ✅ Backed enum over constants
enum ProductStatus: string {
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}

// ✅ Readonly DTO for immutable data
readonly class ProductDTO {
    public function __construct(
        public ProductId $id,
        public string $name,
        public Money $price,
        public ProductStatus $status,
        public \DateTimeImmutable $createdAt,
    ) {}
}

// ✅ Value Object for domain primitives
final class Money {
    public function __construct(
        public int $amount,      // in cents
        public string $currency = 'CZK',
    ) {}

    public function add(self $other): self {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Currency mismatch');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }
}
```

| Rule | Enforcement |
|------|-------------|
| `strict_types=1` on every file | PHP-CS-Fixer `declare_strict_types` |
| Return + param types on all methods | PHPStan level 9+ |
| DTOs over arrays for boundaries | Code review + phpstan-compliance |
| Backed enums for status/types | Rector `EnumRuleSet` |
| `#[Override]` (PHP 8.3+) | Rector `OverrideAttributeRector` |
| `#[SensitiveParameter]` for secrets | php-pro security rules |
| PSR interfaces in type-hints | PHPat architecture tests |

### Nette Architecture (nette-architecture, nette-configuration)

```neon
# config.neon — DI container with typed services
services:
    - App\Module\Product\ProductFacade
    - App\Module\Product\ProductRepository
    - App\Module\Product\ProductValidator

# ✅ Use factory for presenters with dependencies
- App\Presenters\ProductPresenter(
    productFacade: @App\Module\Product\ProductFacade,
    translator: @Nette\Localization\ITranslator,
)
```

| Rule | Why |
|------|-----|
| Presenters thin — delegate to Facade/Services | Separation of concerns |
| Model layer = Facade + Repository + DTOs + Validators | Testability, SRP |
| Latte templates typed with `{varType}` | IDE support, PHPStan |
| Forms via `Nette\Application\UI\Form` + `addProtection()` | CSRF protection |
| Database via Nette Database Explorer + typed entities | Type safety |

### Static Analysis & Quality (php-modernization)

```bash
# Composer scripts (add to composer.json)
"scripts": {
    "cs:fix": "php-cs-fixer fix --ansi",
    "cs:check": "php-cs-fixer fix --dry-run --diff --ansi",
    "phpstan": "phpstan analyse --no-progress --ansi",
    "rector": "rector --ansi",
    "rector:check": "rector --dry-run --ansi",
    "phpat": "phpat analyse",
    "infection": "infection --threads=4 --min-msi=80 --min-covered-msi=80",
    "test": "pest --parallel --colors=always",
    "qa": ["@cs:check", "@phpstan", "@rector:check", "@phpat", "@test"]
}
```

**PHPStan config (phpstan.neon):**
```neon
parameters:
    level: 9
    paths:
        - app
        - tests
    treatPhpDocTypesAsCertain: false
    checkTooWideReturnTypesInProtectedAndPublicMethods: true
    checkUninitializedProperties: true
    reportUnmatchedIgnoredErrors: true
```

**Rector config (rector.php):**
```php
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $config): void {
    $config->paths([__DIR__ . '/app', __DIR__ . '/tests']);
    $config->sets([
        LevelSetList::UP_TO_PHP_83,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::DEAD_CODE,
        SetList::NAMING,
        SetList::PRIVATIZATION,
    ]);
    $config->skip([
        // Add project-specific skips here
    ]);
    $config->withComposerBased(symfony: true);
};
```

### Testing (php-pro, nette-tester)

```php
// tests/Unit/Product/MoneyTest.php
use App\DTO\Money;
use Pest\Expectation;

it('adds money in same currency', function (): void {
    $a = new Money(1000, 'CZK');
    $b = new Money(500, 'CZK');
    expect($a->add($b))->toEqual(new Money(1500, 'CZK'));
});

it('throws on currency mismatch', function (): void {
    $a = new Money(1000, 'CZK');
    $b = new Money(500, 'EUR');
    $a->add($b);
})->throws(\InvalidArgumentException::class, 'Currency mismatch');
```

| Rule | Target |
|------|--------|
| Pest for all tests | `composer require pestphp/pest --dev` |
| ≥80% coverage (line + mutation) | Infection MSI ≥80% |
| Unit tests for DTOs/VOs/Validators | Every class |
| Integration tests for Facade/Repository | Database layer |
| Arch tests (PHPat) for layer boundaries | No direct Model→Presenter calls |

---

## ⚛️ Vue 3 / Vite / Tailwind v4 Rules

### Component Structure (vue, vue-best-practices)

```vue
<!-- components/ProductCard.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { useCurrency } from '@vueuse/core'
import type { ProductDTO } from '@/types/product'

interface Props {
  product: ProductDTO
  variant?: 'default' | 'compact'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
})

const emit = defineEmits<{
  'add-to-cart': [id: string]
  'view-detail': [id: string]
}>()

// ✅ VueUse for reactive utilities
const { currency } = useCurrency('CZK', { locale: 'cs-CZ' })

const formattedPrice = computed(() =>
  currency.value.format(props.product.price.amount / 100)
)
</script>

<template>
  <article :class="['product-card', variant]">
    <h3>{{ product.name }}</h3>
    <p class="price">{{ formattedPrice }}</p>
    <button @click="$emit('add-to-cart', product.id.value)">
      Do košíku
    </button>
  </article>
</template>

<style scoped>
.product-card { @apply p-4 rounded-lg border border-gray-200; }
.product-card.compact { @apply p-2; }
.price { @apply text-lg font-semibold text-primary-600; }
</style>
```

| Rule | Enforcement |
|------|-------------|
| `<script setup lang="ts">` only | vue-best-practices |
| `defineProps` + `withDefaults` + TS interface | vue + TypeScript |
| `defineEmits` with typed payload | vue |
| Composables for reusable logic | `composables/use*.ts` |
| **Prefer VueUse** over custom code | vueuse-functions skill |
| Scoped styles with `@apply` (Tailwind) | tailwind skill |

### Composables (vue-best-practices, vueuse-functions)

```ts
// composables/useProductFilters.ts
import { computed, ref } from 'vue'
import { useDebounceFn, useArrayFilter } from '@vueuse/core'
import type { ProductDTO } from '@/types/product'

export function useProductFilters(products: Ref<ProductDTO[]>) {
  const search = ref('')
  const status = ref<ProductStatus | 'all'>('all')

  // ✅ VueUse for debounced filtering
  const debouncedSearch = useDebounceFn(() => {}, 300)

  const filtered = computed(() =>
    useArrayFilter(products.value, (p) => {
      const matchesSearch = p.name.toLowerCase().includes(search.value.toLowerCase())
      const matchesStatus = status.value === 'all' || p.status === status.value
      return matchesSearch && matchesStatus
    })
  )

  return { search, status, filtered }
}
```

| VueUse Category | When to Use (AUTO) |
|-----------------|-------------------|
| **State** | `useLocalStorage`, `useAsyncState`, `createGlobalState` |
| **Elements** | `useElementSize`, `useIntersectionObserver` |
| **Browser** | `useColorMode`, `useBreakpoints`, `useClipboard` |
| **Animation** | `useAnimate`, `useTransition`, `useIntervalFn` |
| **Watch** | `watchDebounced`, `whenever`, `until` |
| **Reactivity** | `computedAsync`, `refDebounced`, `toReactive` |

> **EXPLICIT_ONLY:** `toRef`, `get`, `set` — only when explicitly needed  
> **EXTERNAL:** `@Firebase`, `@Head`, `@Integrations`, `@Motion`, `@Router`, `@RxJS` — only if dependency installed

### Tailwind v4 (tailwind skill)

```css
/* src/assets/main.css — ENTRY POINT */
@import "tailwindcss";
@plugin "@tailwindcss/typography";
@plugin "@tailwindcss/forms";

@theme {
  /* ✅ CSS-first config — no tailwind.config.js */
  --color-primary: oklch(55% 0.22 260);
  --color-primary-hover: oklch(50% 0.25 260);
  --color-surface: oklch(98% 0.01 260);
  --radius-card: 0.75rem;

  /* ✅ Semantic tokens — auto dark mode */
  --color-bg: var(--color-surface);
  --color-text: oklch(20% 0.02 260);
  --color-border: oklch(85% 0.02 260);
}

@custom-variant dark (&:where(.dark, .dark *));

/* ✅ Custom utilities via @utility */
@utility container {
  margin-inline: auto;
  padding-inline: 1rem;
  max-width: 80rem;
}

@utility card {
  @apply rounded-[--radius-card] border border-[--color-border] bg-[--color-bg] p-4;
}

/* ✅ Container queries */
@container main {
  .grid-auto { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}

/* ✅ Reference for Vue scoped styles */
@reference "./main.css";
```

```vue
<!-- Vue component using theme -->
<script setup lang="ts">
// types/product.ts exports CSS variable names for reference
</script>

<template>
  <div class="container">
    <div class="@container/main grid-auto">
      <ProductCard v-for="p in filtered" :key="p.id.value" :product="p" />
    </div>
  </div>
</template>

<style scoped>
/* ✅ Use theme tokens in scoped styles via @reference */
.card { background: var(--color-bg); border-color: var(--color-border); }
</style>
```

| Rule | Tailwind v4 Pattern |
|------|---------------------|
| Config in CSS via `@theme` | No `tailwind.config.js` |
| Import via `@import "tailwindcss"` | Replaces `@tailwind base/components/utilities` |
| Custom utilities via `@utility` | Replaces `@layer utilities` |
| Plugins via `@plugin` | Replaces `require()` in config |
| Dark mode via `@custom-variant dark` | Class-based |
| Container queries via `@container` | Built-in |
| Scoped styles via `@reference` | Import theme tokens |

### Testing (vue-testing-best-practices)

```ts
// tests/components/ProductCard.test.ts
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import ProductCard from '@/components/ProductCard.vue'

describe('ProductCard', () => {
  it('renders product name and price', () => {
    const product = {
      id: { value: '1' },
      name: 'Test Product',
      price: { amount: 150000, currency: 'CZK' },
      status: 'published',
      createdAt: new Date(),
    }
    const wrapper = mount(ProductCard, { props: { product } })
    expect(wrapper.text()).toContain('Test Product')
    expect(wrapper.text()).toContain('1 500,00')
  })

  it('emits add-to-cart on button click', async () => {
    const wrapper = mount(ProductCard, { props: { product } })
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('add-to-cart')).toBeTruthy()
  })
})
```

| Rule | Tool |
|------|------|
| Vitest + Vue Test Utils | `npm i -D vitest @vue/test-utils jsdom` |
| Test file: `*.test.ts` next to component | Convention |
| Mount with minimal props | `mount(Component, { props })` |
| Test emissions + user interactions | `trigger`, `emitted()` |

---

## 🎨 Tailwind v4 — Common Mistakes to Avoid (tailwind skill)

| ❌ Wrong | ✅ Correct |
|----------|-----------|
| `tailwind.config.js` | `@theme { ... }` in CSS |
| `hsl(var(--bg))` | `var(--bg)` directly |
| `:root { }` inside `@layer` | Define at root level |
| `@apply .btn` | `@utility btn { ... }` |
| `@theme inline` for multi-theme | `@theme` without `inline` |
| `bg-blue-500` everywhere | Semantic `bg-primary` |
| `require('@tailwindcss/forms')` | `@plugin "@tailwindcss/forms"` |
| `tailwindcss-animate` | `tw-animate-css` |
| `start-*` / `end-*` inset | `inset-s-*` / `inset-e-*` |

---

## 🔄 CI/CD (github-actions-templates)

```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]

jobs:
  php-qa:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3', tools: 'composer' }
      - run: composer install --prefer-dist --no-progress
      - run: composer qa  # runs cs:check, phpstan, rector:check, phpat, test

  frontend-qa:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: '20', cache: 'npm' }
      - run: npm ci
      - run: npm run lint      # eslint + stylelint
      - run: npm run typecheck # tsc --noEmit
      - run: npm run test      # vitest --run
      - run: npm run build     # vite build

  docker:
    needs: [php-qa, frontend-qa]
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v4
      - uses: docker/build-push-action@v5
        with:
          push: true
          tags: ghcr.io/owner/repo:latest
          cache-from: type=gha
          cache-to: type=gha,mode=max
```

| Rule | Practice |
|------|----------|
| Matrix builds for PHP/Node versions | github-actions-templates |
| Dependency caching (composer, npm) | Speed up CI |
| Security scanning (Trivy/Snyk) | github-actions-templates |
| Reusable workflows for DRY | `.github/workflows/_reusable.yml` |
| Deployment approvals for prod | Environment protection rules |

---

## 📝 Git & Commit Conventions

```bash
# Branch naming
feature/PROJ-123-add-product-filter
fix/PROJ-456-memory-leak-in-queue
chore/PROJ-789-update-dependencies

# Commit messages (Conventional Commits)
feat(product): add price filtering with VueUse
fix(api): handle null price in Money::add
refactor(nette): extract ProductValidator from Facade
chore(deps): upgrade PHPStan to 2.0
test(product): add mutation tests for Money VO
docs(readme): update CI badge
```

| Rule | Enforcement |
|------|-------------|
| Conventional Commits | `commitlint` + husky |
| Branch per issue/ticket | GitHub Projects |
| PR template with checklist | `.github/pull_request_template.md` |
| Squash merge to main | GitHub settings |

---

## 🗄️ Database (PostgreSQL + Migrations)

**All projects MUST use PostgreSQL with database migrations.**

| Rule | Implementation |
|------|----------------|
| Migration tool | **Doctrine Migrations** (via `doctrine/migrations` + `symfony/console`) or **Nette Database Migrations** (contributte/console + custom) |
| Migration location | `migrations/` in project root (or `app/Database/Migrations/`) |
| Naming convention | `VersionYYYYMMDDHHMMSS_description.php` (Doctrine) or `YYYY_MM_DD_HHMMSS_description.sql` (SQL-based) |
| Every schema change | Requires a new migration file — no manual `ALTER TABLE` in production |
| Rollback support | Each migration MUST implement `down()` (or provide rollback SQL) |
| CI integration | Run migrations in CI before tests: `php bin/console doctrine:migrations:migrate --no-interaction` |
| Local dev | `docker-compose up -d postgres` + `composer migrate` (or `make migrate`) |
| Seeding | Optional `fixtures/` for dev/test data — separate from migrations |

```php
// Example Doctrine Migration
<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823120000_create_product_table extends AbstractMigration {
    public function getDescription(): string {
        return 'Create product table with price, status, timestamps';
    }

    public function up(Schema $schema): void {
        $this->addSql(<<<'SQL'
            CREATE TABLE product (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                name VARCHAR(255) NOT NULL,
                price_cents INTEGER NOT NULL,
                currency CHAR(3) NOT NULL DEFAULT 'CZK',
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
                updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
            );
            CREATE INDEX idx_product_status ON product(status);
        SQL);
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE product;');
    }
}
```

---

## 📚 Documentation Requirements

**Every project MUST have two documentation layers:**

### 1. `README.md` (Project Root)
**Purpose:** Quick start for new developers / CI / deployment

**Required sections:**
```markdown
# Project Name

> One-line description of what this project does.

## 🚀 Quick Start

### Prerequisites
- PHP 8.3+, Composer 2.x
- Node 20+, npm
- PostgreSQL 16+ (or Docker)
- Make (optional, for shortcuts)

### Local Development
```bash
# 1. Clone & install deps
git clone <repo>
cd <project>
composer install
npm ci

# 2. Start database
docker-compose up -d postgres

# 3. Run migrations
composer migrate

# 4. Start dev servers
composer dev:php    # PHP built-in server / FrankenPHP / Swoole
npm run dev         # Vite dev server
```

### Environment Variables
Copy `.env.example` → `.env` and adjust:
```bash
cp .env.example .env
# Edit DATABASE_URL, APP_SECRET, etc.
```

### Testing
```bash
composer test       # PHP (Pest)
npm run test        # Vue (Vitest)
composer qa         # Full PHP QA (cs, phpstan, rector, phpat, infection)
npm run qa          # Full FE QA (lint, typecheck, test, build)
```

### Deployment
```bash
# Build production assets
npm run build
composer install --no-dev --optimize-autoloader

# Run migrations on target DB
composer migrate:prod

# Deploy via CI/CD (GitHub Actions) or manual
```

### Useful Commands
```bash
composer cs:fix      # Fix code style
composer rector      # Auto-refactor (review first!)
composer phpstan     # Static analysis
npm run lint         # ESLint + Stylelint
```

---

### 2. `Docs/` (Root Folder)
**Purpose:** Deep-dive into functional areas, architecture, decisions

**Required structure:**
```
Docs/
├── architecture.md          # High-level architecture, layer boundaries, DI graph
├── domain/                  # Domain-driven design documentation
│   ├── product.md           # Product aggregate, entities, value objects, events
│   ├── order.md             # Order workflow, state machine
│   └── user.md              # User management, roles, permissions
├── api/                     # API documentation
│   ├── rest.md              # REST endpoints, request/response examples
│   ├── graphql.md           # GraphQL schema (if applicable)
│   └── webhooks.md          # Incoming/outgoing webhook specs
├── frontend/                # Frontend architecture
│   ├── components.md        # Component hierarchy, shared UI library
│   ├── state-management.md  # Pinia stores, VueUse patterns
│   └── routing.md           # Vue Router structure, guards, lazy loading
├── infrastructure/          # Infrastructure & DevOps
│   ├── database.md          # Schema overview, migration strategy, indexing
│   ├── docker.md            # Dockerfile, compose, multi-stage builds
│   ├── ci-cd.md             # GitHub Actions workflows, environments
│   └── monitoring.md        # Logging, metrics, tracing, alerting
├── security/                # Security considerations
│   ├── authentication.md    # Auth flow, JWT, sessions
│   ├── authorization.md     # RBAC/ABAC policies
│   └── data-protection.md   # PII, encryption, GDPR
└── decisions/               # Architecture Decision Records (ADRs)
    ├── 001-use-postgresql.md
    ├── 002-vue-composition-api.md
    └── 003-tailwind-v4-css-first.md
```

**Each `Docs/*.md` MUST include:**
- **Context** — why this exists, what problem it solves
- **Structure** — diagrams (Mermaid), class/interface definitions
- **Usage** — code examples, integration points
- **Conventions** — naming, patterns, gotchas
- **Related** — links to other docs, ADRs, external resources

---

## 🔒 Security Checklist (php-pro)

---

## 📦 Type Sharing (PHP ↔ TypeScript)

```ts
// types/product.ts — Single source of truth for FE/BE
export interface ProductDTO {
  id: { value: string }           // ProductId VO
  name: string
  price: { amount: number; currency: string }  // Money VO
  status: 'draft' | 'published' | 'archived'   // Backed enum
  createdAt: string               // ISO 8601
}
```

```php
// App/DTO/ProductDTO.php — PHP side
readonly class ProductDTO {
    public function __construct(
        public ProductId $id,
        public string $name,
        public Money $price,
        public ProductStatus $status,
        public \DateTimeImmutable $createdAt,
    ) {}

    public function toArray(): array {
        return [
            'id' => ['value' => $this->id->value],
            'name' => $this->name,
            'price' => ['amount' => $this->price->amount, 'currency' => $this->price->currency],
            'status' => $this->status->value,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
```

| Rule | Implementation |
|------|----------------|
| Shared types in `types/` (TS) + `DTO/` (PHP) | Manual sync or codegen |
| API returns DTO→array via `toArray()` | Consistent serialization |
| Enum values match exactly | Backed enum ↔ TS union |

---

## 🛠 Tool Versions (pin in CI)

| Tool | Version | Config |
|------|---------|--------|
| PHP | 8.3+ | `php-version` in CI |
| Composer | 2.x | `composer.json` |
| PHPStan | 2.x (level 9+) | `phpstan.neon` |
| Rector | 2.x | `rector.php` |
| PHP-CS-Fixer | 3.x (@PER-CS) | `.php-cs-fixer.php` |
| PHPat | 1.x | `phpat.neon` |
| Infection | 2.x | `infection.json` |
| Pest | 3.x | `pest.php` |
| Node | 20 LTS | `.nvmrc` |
| Vite | 5.x | `vite.config.ts` |
| Vue | 3.4+ | `package.json` |
| Tailwind | 4.x | `src/assets/main.css` |
| Vitest | 2.x | `vitest.config.ts` |
| ESLint | 9.x | `eslint.config.js` |

---

## 📋 Definition of Done (per PR)

- [ ] `composer qa` passes (cs, phpstan, rector, phpat, test, infection)
- [ ] `npm run qa` passes (lint, typecheck, test, build)
- [ ] Coverage ≥80% (PHP + JS)
- [ ] Mutation score ≥80% (Infection)
- [ ] No PHPStan baseline growth without review
- [ ] Rector ran with `--dry-run` first
- [ ] Conventional commit messages
- [ ] Linked GitHub issue/PR template filled
- [ ] Docs updated if API changed

---

## 🚀 Quick Commands

```bash
# PHP Quality Assurance
composer cs:fix      # Fix code style
composer phpstan     # Static analysis
composer rector      # Auto-refactor (review diff!)
composer test        # Pest tests
composer infection   # Mutation testing

# Frontend
npm run dev          # Vite dev server
npm run build        # Production build
npm run test         # Vitest
npm run lint         # ESLint + Stylelint
npm run typecheck    # tsc --noEmit

# Full QA (run before PR)
composer qa && npm run qa
```

---

*Generated from installed Hermes skills: php-modernization, php-pro, nette-*, vue-*, vite, tailwind, vueuse-functions, github-actions-templates*
*Last updated: 2026-08-23*