# Docker Infrastruktura & Orchestrace

## 1. Skladba kontejnerů (`docker-compose.yml`)

1. **`proxy` (`caddy:2-alpine`):** Porty 80 a 443. Řídí automatické On-Demand TLS certifikáty pro vlastní `.cz` domény a směruje provoz na interní aplikační kontejnery.
2. **`admin-app` (`./admin`):** Statický build Vue 3 SPA/PWA servírovaný Caddy webserverem na portu 80.
3. **`backend-api` (`./backend`):** Stateless PHP 8.3 CLI kontejner na portu 8000.
4. **`public-renderer` (`./renderer`):** Stateless PHP 8.3 SSR kontejner na portu 3000.
5. **`postgres` (`postgres:16-alpine`):** Relační databáze s persistentním volume `postgres_data`.
6. **`redis` (`redis:7-alpine`):** In-memory cache pro HTML stránky, tokeny a rate limiting.

## 2. Spuštění celého stacku
```bash
cp .env.example .env
docker compose up -d
docker compose exec backend-api php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec backend-api php bin/seed-demo.php
```
