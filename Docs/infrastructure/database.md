# Databázová Architektura (PostgreSQL Multi-Tenancy)

## 1. Principy
- **Shared Schema Multi-Tenancy:** Všechny tabulky obsahují sloupec `business_id: UUID` s B-tree indexem.
- **Transakční bezpečnost:** Použití cizích klíčů s `ON DELETE CASCADE`.
- **JSONB sloupce:** Tabulka `web_contents` uchovává Chameleon design a dynamický obsah v optimalizovaném formátu `JSONB`.

## 2. Tabulky a indexy
- `businesses` (PK `id`, indexy na `subdomain`, `custom_domain`)
- `auth_providers` (index na `business_id`, unikátní index na `(provider, provider_user_id)`)
- `subscriptions` (1:1 s `businesses`)
- `web_contents` (1:1 s `businesses`, `design` JSONB, `content` JSONB, `version` int)
- `leads` (index na `(business_id, status)` a `(business_id, created_at DESC)`)
- `onboarding_drafts` (index na `expires_at`)
- `media` (index na `business_id`)
