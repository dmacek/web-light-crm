# ADR 001: Použití PostgreSQL 16 a Shared Schema Multi-Tenancy

## Status
Schváleno

## Kontext
Systém potřebuje efektivně obsluhovat tisíce živnostníků s vysokým výkonem a minimální režií správy databázových spojení.

## Rozhodnutí
Zvolen PostgreSQL 16 s modelem Shared Schema (`WHERE business_id = :tenant_id`) a B-tree indexy na všech tenantních tabulkách.

## Důsledky
- Rychlé fulltextové vyhledávání a JSONB operace.
- Jednoduché zálohování a migrace přes Doctrine Migrations.
- V aplikační vrstvě musí být striktně vynucen filtr `business_id`.
