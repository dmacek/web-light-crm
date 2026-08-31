# ADR 003: Tailwind CSS v4 CSS-First Konfigurace

## Status
Schváleno

## Kontext
Tailwind v4 přináší CSS-first přístup s definicí `@theme` v CSS souboru bez nutnosti `tailwind.config.js`.

## Rozhodnutí
Použít `@tailwindcss/vite` s konfigurací přes `@theme` a `@utility` v `src/assets/main.css`.

## Důsledky
- Blesková kompilace a přímé využití CSS proměnných pro dynamické motivy.
