# ADR 004: Automatické On-Demand TLS přes Caddy Server

## Status
Schváleno

## Kontext
Uživatelé ročního tarifu mohou připojit vlastní `.cz` doménu. Systém musí automaticky a bez restartu kontejnerů vystavit platný SSL certifikát.

## Rozhodnutí
Použít Caddy 2 s direktivou `on_demand_tls { ask http://backend-api:8000/api/v1/domains/check }`.

## Důsledky
- Okamžité vystavení certifikátu při prvním HTTP/HTTPS požadavku.
- Ochrana proti vyčerpání Let's Encrypt limitů dotazem na backend před vydáním certifikátu.
